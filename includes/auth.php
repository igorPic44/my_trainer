<?php
session_start();

// Проверка авторизации пользователя
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit;
    }
}

// Проверка ролей пользователя (если нужно)
function checkRole($requiredRole) {
    if ($_SESSION['role'] !== $requiredRole) {
        header('Location: ../index.php');
        exit;
    }
}
?>