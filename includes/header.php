<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Мой Тренер' ?></title>
    
    <!-- Основные CSS -->
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Иконки Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <a href="../index.php">
                    <i class="fas fa-dumbbell"></i>
                    <span>Мой Тренер</span>
                </a>
            </div>
            
            <nav class="main-nav">
                <ul>
                    
                    <li><a href="../workouts/list.php"><i class="fas fa-dumbbell"></i> Тренировки</a></li>
                    <li><a href="../calendar/view.php"><i class="fas fa-calendar-alt"></i> Календарь</a></li>
                    <li><a href="../progress/measurements.php"><i class="fas fa-chart-line"></i> Прогресс</a></li>
                    <li><a href="../water/tracker.php"><i class="fas fa-tint"></i> Вода </a></li>
                    <li><a href="../nutrition/diary.php"><i class="fas fa-utensils"></i> Питание </a></li>
                                
                </ul>
            </nav>
            
            <div class="user-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="../auth/logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                    <span class="username">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Профиль') ?>
                    </span>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <main class="container">