<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input && isset($input['type']) && isset($input['data'])) {
        $type = $input['type'];
        $data = $input['data'];
        $timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');
        
        $filename = "data_{$type}_" . date('Ymd_His') . ".json";
        $logEntry = [
            'timestamp' => $timestamp,
            'type' => $type,
            'data' => $data
        ];
        
        file_put_contents($filename, json_encode($logEntry, JSON_PRETTY_PRINT));
        file_put_contents("all_data.log", json_encode($logEntry) . "\n", FILE_APPEND);
        
        echo json_encode(['status' => 'success', 'message' => 'Data saved successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid data format']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>