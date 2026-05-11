<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'curator') {
    header('Location: ../../index.php');
    exit;
}

require_once '../../includes/db.php';

// Проверка наличия данных
if (
    empty($_POST['student_id']) ||
    empty($_POST['group_name']) ||
    !isset($_FILES['document'])
) {
    die('Ошибка: отсутствуют обязательные данные.');
}

$studentId = (int) $_POST['student_id'];
$groupName = trim($_POST['group_name']);
$file = $_FILES['document'];

// Проверка на ошибки
if ($file['error'] !== UPLOAD_ERR_OK) {
    die('Ошибка при загрузке файла.');
}

// Проверка расширения
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions)) {
    die('Недопустимый формат файла.');
}

// Сохраняем файл
$uploadDir = '../../uploads/';
$uniqueName = uniqid() . '_' . basename($file['name']);
$targetPath = $uploadDir . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    die('Не удалось сохранить файл.');
}

// Запись в базу данных
$stmt = $pdo->prepare("INSERT INTO documents (user_id, original_name, filename, uploaded_at) VALUES (?, ?, ?, NOW())");
$stmt->execute([$studentId, $file['name'], $uniqueName]);

header('Location: ../../curator.php');
exit;
