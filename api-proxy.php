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

// Configuración de Zafronix
define('ZAFRONIX_API_KEY', getenv('ZAFRONIX_API_KEY') ?: 'TU_API_KEY_AQUI');
define('ZAFRONIX_BASE_URL', 'https://api.zafronix.com/fifa/worldcup/v1');

// Configuración de Caché
define('CACHE_DIR', __DIR__ . '/cache');
define('CACHE_ENABLED', true);

// Tiempos de vida del caché en segundos (TTL)
define('CACHE_TTL_MATCHES', 300);   // 5 minutos para partidos
define('CACHE_TTL_TEAM', 86400);     // 24 horas para equipos
define('CACHE_TTL_ROSTER', 86400);   // 24 horas para plantillas
define('CACHE_TTL_PLAYER', 86400);   // 24 horas para perfiles de jugadores

// Determinar el endpoint solicitado
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'matches';

if ($endpoint === 'matches') {
    // 1. Obtener la lista de partidos del Mundial 2026
    $url = ZAFRONIX_BASE_URL . '/matches?year=2026';
    fetchAndOutputJson($url, CACHE_TTL_MATCHES);
    
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

function fetchAndOutputJson($url, $ttl) {
    $cache_file = getCacheFilename($url);
    
    // Si la caché está activa, el archivo existe y no ha vencido, retornarlo directamente
    if (CACHE_ENABLED && file_exists($cache_file) && (time() - filemtime($cache_file) < $ttl)) {
        header("Content-Type: application/json; charset=utf-8");
        header("X-Cache: HIT");
        readfile($cache_file);
        exit(0);
    }
    
    // Si no, hacer la consulta a Zafronix
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
    curl_close($ch);
    
    // Guardar en caché si la respuesta es válida (HTTP 200) y tiene contenido
    if ($http_code === 200 && $response) {
        if (CACHE_ENABLED) {
            file_put_contents($cache_file, $response);
        }
    }
    
    header("Content-Type: application/json; charset=utf-8");
    header("X-Cache: MISS");
    http_response_code($http_code ? $http_code : 500);
    echo $response;
    exit(0);
}
