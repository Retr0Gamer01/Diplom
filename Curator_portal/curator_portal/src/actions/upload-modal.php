<?php
session_start();
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Неверный метод запроса');
}

if (
    empty($_POST['student']) ||
    empty($_POST['group']) ||
    empty($_FILES['document'])
) {
    exit('Заполните все поля');
}

$studentId = $_POST['student'];
$groupName = $_POST['group'];
$file = $_FILES['document'];

$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
$maxFileSize = 10 * 1024 * 1024; // 10 MB

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions)) {
    exit('Неразрешенный формат файла');
}

if ($file['size'] > $maxFileSize) {
    exit('Слишком большой файл');
}

$uniqueName = uniqid() . '_' . basename($file['name']);
$targetPath = '../../uploads/' . $uniqueName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $stmt = $pdo->prepare("INSERT INTO documents (user_id, group_name, original_name, filename, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$studentId, $groupName, $file['name'], $uniqueName]);
    exit('Файл успешно загружен');
} else {
    exit('Ошибка при сохранении файла');
}