<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audio'])) {
    $audioFile = $_FILES['audio'];
    
    if (!is_dir('audio')) {
        mkdir('audio', 0755, true);
    }
    
    $filename = 'audio/' . $audioFile['name'];
    
    if (move_uploaded_file($audioFile['tmp_name'], $filename)) {
        file_put_contents('audio_log.txt', date('Y-m-d H:i:s') . " - {$filename}\n", FILE_APPEND);
        echo json_encode(['status' => 'success', 'message' => 'Audio saved']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save audio']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No audio file received']);
}
?>