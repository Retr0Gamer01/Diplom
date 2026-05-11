<?php
session_start();
require_once 'includes/db.php'; // подключение к базе

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['group_name'] = $user['group_name'];

        if ($user['role'] === 'student') {
            header('Location: student.php');
        } else {
            header('Location: curator.php');
        }
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="shortcut icon" href="assets/icon//free-icon-graduation-hat-8276809.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <title>Вход</title>
</head>

<body>
    <div class="container_index  <?= isset($_GET['error']) ? 'error-active' : '' ?>">
        <h2 class="container_h2">Вход</h2>
        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
            <p class="error_login" style="color: red; text-align: center; font-family: 'Calibri'; margin-bottom: 15px">Неверный логин или пароль</p>
        <?php endif; ?>
        <form class="form_login" action="auth.php" method="post">
            <div class="input_box">
                <input class="login" type="text" placeholder="Логин" require name="login">
            </div>
            <div class="input_box">
                <input class="input_password" type="password" placeholder="Пароль" require name="password">
            </div>
            <button type="submit" class="btn">Войти</button>
        </form>
    </div>
</body>

</html>