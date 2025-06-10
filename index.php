<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Получение данных пользователя
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Получение статистики
$stats = [
    'workouts' => getWorkoutsCount($userId),
    'water' => getTodayWaterIntake($userId),
    'calories' => getTodayCalories($userId),
    'progress' => getProgress($userId)
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная | Мой Тренер</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Боковая панель -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-dumbbell"></i>
                <span>Мой Тренер</span>
            </div>
            
            <nav class="nav">
                <ul>
                    <li class="active">
                        <a href="index.php">
                            <i class="fas fa-home"></i>
                            <span>Главная</span>
                        </a>
                    </li>
                    <li>
                        <a href="workouts/list.php">
                            <i class="fas fa-dumbbell"></i>
                            <span>Тренировки</span>
                        </a>
                    </li>
                    <li>
                        <a href="calendar/view.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Календарь</span>
                        </a>
                    </li>
                    <li>
                        <a href="nutrition/diary.php">
                            <i class="fas fa-utensils"></i>
                            <span>Питание</span>
                        </a>
                    </li>
                    <li>
                        <a href="water/tracker.php">
                            <i class="fas fa-tint"></i>
                            <span>Вода</span>
                        </a>
                    </li>
                    <li>
                        <a href="progress/measurements.php">
                            <i class="fas fa-chart-line"></i>
                            <span>Прогресс</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="user-profile">
                <div class="avatar">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <span class="username"><?= htmlspecialchars($user['username']) ?></span>
                    <a href="auth/logout.php?redirect=login.php" class="logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Основное содержимое -->
        <main class="content">
            <header class="header">
                <h1>Добро пожаловать, <?= htmlspecialchars($user['username']) ?>!</h1>
                <div class="date">Сегодня <?= date('d.m.Y') ?></div>
            </header>
            
            <!-- Карточки статистики -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #6366f1;">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $stats['workouts'] ?></span>
                        <span class="stat-label">Тренировки</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #10b981;">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $stats['water'] ?> мл</span>
                        <span class="stat-label">Воды сегодня</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #8e44ad;">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $stats['calories'] ?></span>
                        <span class="stat-label">Ккал сегодня</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #ef4444;">
                        <i class="fas fa-weight"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?= $stats['progress']['weight'] ?> кг</span>
                        <span class="stat-label">Текущий вес</span>
                    </div>
                </div>
            </div>
            
            <!-- Ближайшие тренировки -->
            <section class="section">
                <h2>Ближайшие тренировки</h2>
                <div class="workouts-list">
                   <?php $upcomingWorkouts = getUpcomingWorkouts($userId); ?>
<?php if (!empty($upcomingWorkouts)): ?>
    <?php foreach ($upcomingWorkouts as $workout): ?>
        <div class="workout-card">
            <div class="workout-date">
                <?php if (!empty($workout['date'])): ?>
                    <span class="day"><?= date('d', strtotime($workout['date'])) ?></span>
                    <span class="month"><?= russianMonth(date('m', strtotime($workout['date']))) ?></span>
                <?php else: ?>
                    <span class="day">--</span>
                    <span class="month">---</span>
                <?php endif; ?>
            </div>
            <div class="workout-info">
                <h3><?= htmlspecialchars($workout['name']) ?></h3>
                <p><?= htmlspecialchars($workout['description']) ?></p>
                <div class="exercises-count">
                    <i class="fas fa-list-ol"></i>
                    <?= $workout['exercise_count'] ?> упражнений
                </div>
            </div>
            <a href="workouts/view.php?id=<?= $workout['id'] ?>" class="workout-link">
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-calendar-plus"></i>
        <p>У вас нет запланированных тренировок</p>
        <a href="workouts/create.php" class="btn">Создать тренировку</a>
    </div>
<?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>