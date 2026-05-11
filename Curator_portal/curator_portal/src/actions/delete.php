<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Нет доступа');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

if (!isset($_GET['id'])) {
    exit('Не указан ID документа');
}

$id = $_GET['id'];

// Получаем документ по ID
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    exit('Документ не найден');
}

// Проверка прав доступа
if (
    $userRole === 'curator' ||
    ($userRole === 'student' && $doc['user_id'] == $userId)
) {
    $filePath = __DIR__ . '/../../uploads/' . $doc['filename'];

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
    $stmt->execute([$id]);

    // После успешного удаления:
    $query = $_SERVER['QUERY_STRING'];
    parse_str($query, $params);
    unset($params['id']); // убираем id, он нам больше не нужен

    // Строим URL с возвратом фильтров
    if ($userRole === 'student') {
        $backUrl = '../../student.php';
    } else {
        $backUrl = '../../curator.php';
    }

    // Добавляем сохранённые параметры фильтрации
    if (!empty($params)) {
        $backUrl .= '?' . http_build_query($params);
    }

    header("Location: $backUrl");
    exit;
} else {
    http_response_code(403);
    exit('У вас нет прав для удаления этого файла');
}