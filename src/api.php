<?php
/**
 * デモ API エンドポイント
 * 
 * 利用可能なエンドポイント:
 * - GET /api/hello  : 挨拶メッセージを返す
 * - GET /api/status : サーバー状態を返す
 * - GET /api/time   : 現在時刻を返す
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

$endpoint = $_GET['endpoint'] ?? '';

$response = match($endpoint) {
    'hello' => [
        'success' => true,
        'message' => 'こんにちは！デモ API へようこそ！',
        'endpoint' => '/api/hello',
        'method' => $_SERVER['REQUEST_METHOD']
    ],
    
    'status' => [
        'success' => true,
        'status' => 'オンライン',
        'server' => 'Apache/' . apache_get_version(),
        'php_version' => PHP_VERSION,
        'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
        'uptime' => '稼働中'
    ],
    
    'time' => [
        'success' => true,
        'timestamp' => time(),
        'datetime' => date('Y年m月d日 H:i:s'),
        'timezone' => date_default_timezone_get(),
        'iso8601' => date('c')
    ],
    
    default => [
        'success' => false,
        'error' => 'エンドポイントが見つかりません',
        'available_endpoints' => [
            '/api/hello',
            '/api/status', 
            '/api/time'
        ]
    ]
};

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
