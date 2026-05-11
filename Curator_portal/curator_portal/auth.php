<?php
session_start();
require_once 'includes/db.php'; // путь зависит от структуры

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['login']);        // ← исправлено
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Сохраняем данные в сессию
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['group_name'] = $user['group_name'];

        // Перенаправление по роли
        if ($user['role'] === 'student') {
            header('Location: ./student.php');
        } elseif ($user['role'] === 'curator') {
            header('Location: ./curator.php');
        } else {
            header('Location: ./index.php?error=1'); // неизвестная роль
        }
        exit;
    } else {
        // Неверный логин или пароль
        header('Location: ./index.php?error=1');
        exit;
    }
}
