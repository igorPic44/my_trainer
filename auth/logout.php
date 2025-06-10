<?php
session_start();
session_destroy();

// Перенаправление на страницу входа
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'login.php';
header("Location: $redirect");
exit;
?>