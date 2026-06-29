<?php
/**
 * Proxy Seguro para la API de Zafronix (Mundial 2026)
 * Diseñado para integrarse en entornos WordPress.
 * Evita exponer la API Key en el frontend de miravospais.com.
 */

// Configuración de cabeceras CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key");
header("Access-Control-Allow-Methods: GET, OPTIONS");

// Manejo de peticiones preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Carga de credenciales: archivo secreto local (fuera del repo público).
// Crear "secret-config.php" junto a este archivo en el servidor con:
//   <?php putenv('ZAFRONIX_API_KEY=tu_clave_real');
// Esto funciona aunque el proxy se ejecute standalone (sin cargar WordPress).
if (file_exists(__DIR__ . '/secret-config.php')) {
    require __DIR__ . '/secret-config.php';
}

// Configuración de Zafronix (orden: env var del servidor → archivo secreto vía putenv → error)
define('ZAFRONIX_API_KEY', getenv('ZAFRONIX_API_KEY') ?: ($_SERVER['ZAFRONIX_API_KEY'] ?? 'TU_API_KEY_AQUI'));

// RELAY (opcional): el VPS de producción no puede conectar directo a api.zafronix.com
// (connection refused / bloqueo upstream). Si se define una URL de relay (Cloudflare
// Worker), todas las llamadas pasan por ahí. Dejar vacío ('') para conexión directa.
define('ZAFRONIX_RELAY_URL', 'https://wc-relay.miravos-tuc.workers.dev/fifa/worldcup/v1');

define('ZAFRONIX_BASE_URL', ZAFRONIX_RELAY_URL !== '' ? ZAFRONIX_RELAY_URL : 'https://api.zafronix.com/fifa/worldcup/v1');

// Configuración de Caché
define('CACHE_DIR', __DIR__ . '/cache');
define('CACHE_ENABLED', true);

// Tiempos de vida del caché en segundos (TTL).
// matches usa TTL ADAPTATIVO (ver computeMatchesTTL): refresco rápido solo si hay
// partido en vivo; caché largo cuando no hay nada en juego. Esto minimiza el consumo.
define('CACHE_TTL_MATCHES_LIVE', 180);   // 3 min cuando hay al menos un partido en vivo
define('CACHE_TTL_MATCHES_IDLE', 1800);  // 30 min cuando no hay partidos en curso
define('CACHE_TTL_TEAM', 86400);     // 24 horas para equipos
define('CACHE_TTL_ROSTER', 86400);   // 24 horas para plantillas
define('CACHE_TTL_PLAYER', 86400);   // 24 horas para perfiles de jugadores

// CIRCUIT BREAKER: tope duro de llamadas reales a Zafronix por día (UTC).
// Al alcanzarlo, el proxy deja de llamar a la API y sirve sólo caché el resto del día.
// Garantiza no pasarse del plan contratado. AJUSTAR a ~70-80% del límite real del plan
// (deja margen para picos). Ej.: plan de 5000/día → poner ~3500 acá.
define('MAX_DAILY_API_CALLS', 3500); // 70% de 5000/día — deja margen para picos

// Determinar el endpoint solicitado
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'matches';

if ($endpoint === 'matches') {
    // 1. Obtener la lista de partidos del Mundial 2026 (TTL adaptativo según haya partidos en vivo)
    $url = ZAFRONIX_BASE_URL . '/matches?year=2026';
    $ttl = computeMatchesTTL(getCacheFilename($url));
    fetchAndOutputJson($url, $ttl);

} elseif ($endpoint === 'team') {
    // 2. Obtener detalles de un país/equipo
    $name = isset($_GET['name']) ? urlencode($_GET['name']) : '';
    $url = ZAFRONIX_BASE_URL . '/teams/' . $name;
    fetchAndOutputJson($url, CACHE_TTL_TEAM);

} elseif ($endpoint === 'roster') {
    // 3. Obtener el plantel (roster) de un equipo para el 2026
    $name = isset($_GET['name']) ? urlencode($_GET['name']) : '';
    $url = ZAFRONIX_BASE_URL . '/teams/' . $name . '/roster?year=2026';
    fetchAndOutputJson($url, CACHE_TTL_ROSTER);

} elseif ($endpoint === 'player') {
    // 4. Obtener perfil e historial de un jugador
    $name = isset($_GET['name']) ? urlencode($_GET['name']) : '';
    $url = ZAFRONIX_BASE_URL . '/players/' . $name;
    fetchAndOutputJson($url, CACHE_TTL_PLAYER);

} elseif ($endpoint === 'stream') {
    // 5. Retransmisión de Server-Sent Events (SSE) en tiempo real
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // Evita caché en Nginx/Cloudflare
    
    // Deshabilitar el almacenamiento en búfer de salida de PHP
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', '1');
    }
    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', false);
    
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    ob_implicit_flush(true);
    
    $stream_url = ZAFRONIX_BASE_URL . '/matches/stream';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $stream_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . ZAFRONIX_API_KEY,
        'Authorization: Bearer ' . ZAFRONIX_API_KEY,
        'Accept: text/event-stream'
    ]);
    
    // Callback de escritura cURL para SSE
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
        echo $data;
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
        return strlen($data);
    });
    
    set_time_limit(0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    
    curl_exec($ch);
    curl_close($ch);
    exit(0);
} elseif ($endpoint === 'usage') {
    // 6. Monitoreo: cuántas llamadas reales a Zafronix lleva el día (UTC) y el tope configurado.
    header("Content-Type: application/json; charset=utf-8");
    $used = getDailyUsage();
    echo json_encode([
        "date_utc"   => gmdate('Y-m-d'),
        "used"       => $used,
        "cap"        => MAX_DAILY_API_CALLS,
        "remaining"  => max(0, MAX_DAILY_API_CALLS - $used),
        "capped"     => $used >= MAX_DAILY_API_CALLS
    ]);
    exit(0);

} elseif ($endpoint === 'diag') {
    // 7. Diagnóstico de conectividad SALIENTE desde el servidor.
    // Prueba varios destinos para distinguir: ¿bloquea TODO lo saliente el hosting,
    // o solo a Zafronix? Útil para decidir si hay que pedir whitelist al soporte.
    header("Content-Type: application/json; charset=utf-8");
    $targets = [
        "zafronix_https"   => "https://api.zafronix.com/fifa/worldcup/v1/matches?year=2026",
        "zafronix_ip_443"  => "https://74.208.142.121/",
        "google_https"     => "https://www.google.com/",
        "github_api_https" => "https://api.github.com/",
        "example_http_80"  => "http://example.com/",
    ];
    $results = [];
    foreach ($targets as $label => $turl) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $turl);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_NOBODY, true);          // solo HEAD/conexión
        curl_setopt($c, CURLOPT_TIMEOUT, 8);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 6);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false); // diag: ignorar SSL para aislar conectividad
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
        curl_exec($c);
        $results[$label] = [
            "http_code"   => curl_getinfo($c, CURLINFO_HTTP_CODE),
            "connect_ms"  => round(curl_getinfo($c, CURLINFO_CONNECT_TIME) * 1000),
            "remote_ip"   => curl_getinfo($c, CURLINFO_PRIMARY_IP),
            "curl_errno"  => curl_errno($c),
            "curl_error"  => curl_error($c),
        ];
        curl_close($c);
    }
    echo json_encode([
        "php_version"   => PHP_VERSION,
        "curl_version"  => curl_version()['version'] ?? null,
        "ssl_version"   => curl_version()['ssl_version'] ?? null,
        "server_ip"     => $_SERVER['SERVER_ADDR'] ?? null,
        "http_proxy_env"=> getenv('http_proxy') ?: getenv('HTTP_PROXY') ?: null,
        "results"       => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit(0);

} else {
    header("Content-Type: application/json");
    http_response_code(400);
    echo json_encode([
        "error" => "invalid_endpoint",
        "message" => "El endpoint solicitado no es válido."
    ]);
    exit(0);
}

// Función auxiliar para cURL JSON con soporte de caché
function getCacheFilename($url) {
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0755, true);
    }
    return CACHE_DIR . '/' . md5($url) . '.json';
}

// --- Contador de uso diario (circuit breaker) ---
// Cuenta las llamadas reales a Zafronix por día UTC. Se resetea solo al cambiar el día.
function usageFile() {
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0755, true);
    }
    return CACHE_DIR . '/usage_' . gmdate('Ymd') . '.json';
}
function getDailyUsage() {
    $f = usageFile();
    if (!file_exists($f)) return 0;
    $d = json_decode(file_get_contents($f), true);
    return (is_array($d) && isset($d['count'])) ? (int)$d['count'] : 0;
}
function incrementDailyUsage() {
    $f = usageFile();
    $count = getDailyUsage() + 1;
    file_put_contents($f, json_encode(['count' => $count, 'date' => gmdate('Y-m-d\TH:i:s\Z')]), LOCK_EX);
    return $count;
}

// TTL adaptativo para matches: 3 min si hay algún partido en vivo, 30 min si no.
// Prioriza el campo 'status' de la API (más confiable que ventana horaria).
// Fallback: compara now con kickoff + 2.5h en UTC estricto.
function computeMatchesTTL($cache_file) {
    if (!file_exists($cache_file)) return CACHE_TTL_MATCHES_LIVE;
    $data = json_decode(file_get_contents($cache_file), true);
    if (!is_array($data)) return CACHE_TTL_MATCHES_LIVE;

    // Normalizar estructura de respuesta (Zafronix puede devolver distintas envolturas).
    $matches = [];
    if (isset($data['data']) && is_array($data['data']))           $matches = $data['data'];
    elseif (isset($data['matches']) && is_array($data['matches'])) $matches = $data['matches'];
    elseif (isset($data['results']) && is_array($data['results'])) $matches = $data['results'];
    elseif (isset($data[0]))                                        $matches = $data;

    if (empty($matches)) return CACHE_TTL_MATCHES_IDLE;

    // Estados que Zafronix devuelve para partidos en curso.
    $live_statuses = ['live', 'in_play', 'in play', 'inplay', 'halftime', 'ht', 'paused', '1h', '2h', 'et', 'ongoing', 'in_progress'];

    $now = time();
    foreach ($matches as $m) {
        // 1) Campo status directo (más fiable).
        $st = isset($m['status']) ? strtolower(trim($m['status'])) : '';
        if ($st !== '' && in_array($st, $live_statuses, true)) {
            return CACHE_TTL_MATCHES_LIVE;
        }

        // 2) Fallback: ventana horaria en UTC.
        // Zafronix devuelve kickoffUtc O bien date + kickoff ("HH:MM").
        if (!empty($m['kickoffUtc'])) {
            $kickoffStr = $m['kickoffUtc'];
        } elseif (!empty($m['date']) && !empty($m['kickoff'])) {
            $kickoffStr = $m['date'] . 'T' . $m['kickoff'] . ':00Z';
        } elseif (!empty($m['date'])) {
            continue; // solo fecha sin hora, no sirve para detección horaria
        } else {
            continue;
        }

        // Forzar sufijo UTC si el string no lleva timezone.
        if (substr($kickoffStr, -1) !== 'Z' && !preg_match('/[+-]\d{2}:?\d{2}$/', $kickoffStr)) {
            $kickoffStr .= 'Z';
        }

        // strtotime() no entiende milisegundos (".000Z"). Los removemos antes de parsear.
        // Sin esto, "2026-06-24T19:00:00.000Z" devuelve false y el TTL siempre queda en IDLE.
        $kickoffStr = preg_replace('/\.\d+Z$/', 'Z', $kickoffStr);

        $kickoff = strtotime($kickoffStr);
        if ($kickoff === false || $kickoff < 0) continue;

        if ($now >= $kickoff && $now <= $kickoff + 9000) { // ventana 2.5 h
            return CACHE_TTL_MATCHES_LIVE;
        }
    }
    return CACHE_TTL_MATCHES_IDLE;
}

function fetchAndOutputJson($url, $ttl) {
    $cache_file = getCacheFilename($url);

    // Impedir que el navegador y Cloudflare cacheen el JSON de partidos.
    // Sin esto, Cloudflare sirve el marcador congelado aunque el proxy ya tenga datos nuevos.
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

    // Si la caché está activa, el archivo existe y no ha vencido, retornarlo directamente
    if (CACHE_ENABLED && file_exists($cache_file) && (time() - filemtime($cache_file) < $ttl)) {
        header("Content-Type: application/json; charset=utf-8");
        header("X-Cache: HIT");
        readfile($cache_file);
        exit(0);
    }

    // CIRCUIT BREAKER: si ya alcanzamos el tope diario de llamadas, NO consultar a Zafronix.
    // Servir el último caché (aunque esté vencido) para no romper el sitio ni gastar más cuota.
    if (getDailyUsage() >= MAX_DAILY_API_CALLS) {
        if (CACHE_ENABLED && file_exists($cache_file)) {
            header("Content-Type: application/json; charset=utf-8");
            header("X-Cache: STALE-CAP");
            readfile($cache_file);
            exit(0);
        }
        header("Content-Type: application/json; charset=utf-8");
        header("X-Cache: CAP");
        http_response_code(429);
        echo json_encode([
            "error" => "local_daily_cap",
            "message" => "Tope diario de la aplicación alcanzado. Reintentar más tarde."
        ]);
        exit(0);
    }

    // Si no, hacer la consulta a Zafronix (contabilizar la llamada real)
    incrementDailyUsage();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . ZAFRONIX_API_KEY,
        'Authorization: Bearer ' . ZAFRONIX_API_KEY,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Éxito: guardar en caché y servir
    if ($http_code === 200 && $response) {
        if (CACHE_ENABLED) {
            file_put_contents($cache_file, $response);
        }
        header("Content-Type: application/json; charset=utf-8");
        header("X-Cache: MISS");
        echo $response;
        exit(0);
    }

    // Fallo (límite diario agotado, caída de Zafronix, timeout, etc.):
    // si hay un caché previo —aunque esté vencido— servirlo (STALE) en vez de
    // propagar el error. Así el sitio sigue mostrando los últimos datos buenos.
    if (CACHE_ENABLED && file_exists($cache_file)) {
        header("Content-Type: application/json; charset=utf-8");
        header("X-Cache: STALE");
        readfile($cache_file);
        exit(0);
    }

    // No hay caché de respaldo: propagar el error real CON diagnóstico de cURL
    // (errno + mensaje) para poder identificar fallos de SSL/conexión del servidor.
    header("Content-Type: application/json; charset=utf-8");
    header("X-Cache: MISS");
    http_response_code($http_code ? $http_code : 500);
    if ($response) {
        echo $response;
    } else {
        echo json_encode([
            "error"        => "upstream_fetch_failed",
            "message"      => "No se pudo obtener datos de Zafronix desde el servidor.",
            "http_code"    => $http_code,
            "curl_errno"   => $curl_errno,
            "curl_error"   => $curl_error,
            "url"          => $url
        ]);
    }
    exit(0);
}
