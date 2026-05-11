<?php
session_start();
session_unset();      // Очищает все переменные сессии
session_destroy();    // Уничтожает сессию

header("Location: ../../student.php"); // Перенаправляем на форму входа
exit;
?>