<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image']) && isset($_POST['filename'])) {
    $imageData = $_POST['image'];
    $filename = $_POST['filename'];
    
    $imageData = str_replace('data:image/png;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    
    $imageBinary = base64_decode($imageData);
    
    if (!is_dir('photos')) {
        mkdir('photos', 0755, true);
    }
    
    $filePath = 'photos/' . $filename;
    if (file_put_contents($filePath, $imageBinary)) {
        file_put_contents('photo_log.txt', date('Y-m-d H:i:s') . " - {$filename}\n", FILE_APPEND);
        echo json_encode(['status' => 'success', 'message' => 'Photo saved']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save photo']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>