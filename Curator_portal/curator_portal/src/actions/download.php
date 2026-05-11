<?php
require_once '../../includes/db.php';

if (!isset($_GET['id'])) {
    exit('Не указан ID документа');
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$id]);
$document = $stmt->fetch();

if (!$document) {
    exit('Документ не найден');
}

$filepath = __DIR__ . '/../../uploads/' . $document['filename'];

if (!file_exists($filepath)) {
    exit('Файл не найден');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($document['original_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
