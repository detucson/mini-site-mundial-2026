<?php
/**
 * Proxy Seguro para la API de Zafronix (Mundial 2026)
 * Diseñado para integrarse en entornos WordPress.
 * Evita exponer la API Key en el frontend de miravospais.com.
 * Incorpora un sistema de caché de archivos locales con fallback resiliente.
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
define('CACHE_TTL_MATCHES', 300);   // 5 minutos para partidos (el SSE provee actualizaciones en vivo)
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

// Función auxiliar para obtener el nombre del archivo de caché único
function getCacheFilename($url) {
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0755, true);
    }
    return CACHE_DIR . '/' . md5($url) . '.json';
}

// Función principal para obtener y servir datos con caché y resiliencia
function fetchAndOutputJson($url, $ttl) {
    $cache_file = getCacheFilename($url);
    $cache_exists = file_exists($cache_file);
    
    // 1. Si la caché está activa y es válida (no ha expirado), servirla directamente (HIT)
    if (CACHE_ENABLED && $cache_exists && (time() - filemtime($cache_file) < $ttl)) {
        header("Content-Type: application/json; charset=utf-8");
        header("X-Cache: HIT");
        readfile($cache_file);
        exit(0);
    }
    
    // 2. Si no hay caché válida, hacer la consulta a Zafronix
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Límite de 10 segundos para no bloquear al servidor
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . ZAFRONIX_API_KEY,
        'Authorization: Bearer ' . ZAFRONIX_API_KEY,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 3. Si la respuesta es HTTP 200 y es un JSON válido sin claves de error, actualizar caché y servir (MISS)
    if ($http_code === 200 && $response) {
        $json_data = json_decode($response);
        if ($json_data !== null && !isset($json_data->error)) {
            if (CACHE_ENABLED) {
                file_put_contents($cache_file, $response);
            }
            header("Content-Type: application/json; charset=utf-8");
            header("X-Cache: MISS");
            echo $response;
            exit(0);
        }
    }
    
    // 4. Si la llamada a la API falla (límite superado, 500, timeout, etc.) y existe caché previa,
    // actuar como salvaguarda sirviendo los datos viejos (HIT-FALLBACK)
    if ($cache_exists) {
        header("Content-Type: application/json; charset=utf-8");
        header("X-Cache: HIT-FALLBACK");
        header("X-Cache-Error-Code: " . $http_code);
        readfile($cache_file);
        exit(0);
    }
    
    // 5. Si no hay datos en caché ni se pudo consultar la API, retornar error controlado
    header("Content-Type: application/json; charset=utf-8");
    http_response_code($http_code ? $http_code : 500);
    echo json_encode([
        "error" => "api_failure",
        "message" => "No se pudo obtener información de la API y no hay datos en caché disponibles.",
        "http_code" => $http_code
    ]);
    exit(0);
}
