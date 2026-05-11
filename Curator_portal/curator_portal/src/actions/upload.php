<?php
session_start();
require_once '../../includes/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student', 'curator'])) {
    http_response_code(403);
    exit('Нет доступа');
}

// Проверка файла
if (!isset($_FILES['document']) || $_FILES['document']['error'] !== 0) {
    http_response_code(400);
    exit('Файл не получен или повреждён');
}

// Разрешённые типы и ограничения
$allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
$maxSize = 15 * 1024 * 1024; // 15MB

$file = $_FILES['document'];
$originalName = $file['name'];
$tmpName = $file['tmp_name'];
$size = $file['size'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// Проверка типа
if (!in_array($ext, $allowedTypes)) {
    exit('Неразрешённый тип файла');
}

// Проверка размера
if ($size > $maxSize) {
    exit('Файл слишком большой');
}

// Генерация уникального имени
$storageName = uniqid() . '_' . basename($originalName);
$uploadDir = '../../uploads/';
$uploadPath = $uploadDir . $storageName;

if (!move_uploaded_file($tmpName, $uploadPath)) {
    exit('Не удалось сохранить файл');
}

// === Определяем user_id в зависимости от роли ===
if ($_SESSION['role'] === 'student') {
    $userId = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'curator') {
    if (empty($_POST['student_id'])) {
        exit('Ошибка: не выбран студент');
    }
    $userId = $_POST['student_id'];
} else {
    exit('Ошибка роли');
}

// === Запись в БД ===
$stmt = $pdo->prepare("
    INSERT INTO documents (user_id, filename, original_name, uploaded_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([
    $userId,
    $storageName,
    $originalName
]);

// Перенаправление обратно с сохранением фильтров
$base = ($_SESSION['role'] === 'curator') ? '../../curator.php' : '../../student.php';

$query = $_SERVER['QUERY_STRING'];
parse_str($query, $params);
unset($params['student_id'], $params['group_name']); // удалить внутренние поля формы
$redirectUrl = $base . '?' . http_build_query($params);

header("Location: $redirectUrl");
exit;
