<?php

$date = date('dMYHis');
$imageData = $_POST['cat'];
$filename = isset($_POST['filename']) ? $_POST['filename'] : 'cam'.$date.'.png';

if (!empty($_POST['cat'])) {
    error_log("Received image: " . $filename . "\r\n", 3, "Log.log");
}

$filteredData = substr($imageData, strpos($imageData, ",")+1);
$unencodedData = base64_decode($filteredData);

// Create photos directory if it doesn't exist
if (!is_dir('photos')) {
    mkdir('photos', 0755, true);
}

$fp = fopen('photos/' . $filename, 'wb');
fwrite($fp, $unencodedData);
fclose($fp);

// Log successful save
file_put_contents('photo_log.txt', date('Y-m-d H:i:s') . " - {$filename}\n", FILE_APPEND);

echo json_encode(['status' => 'success', 'message' => 'Photo saved: ' . $filename]);

exit();
?>