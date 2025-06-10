<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Получаем все тренировки пользователя
$workouts = $pdo->prepare("SELECT id, name FROM workouts WHERE user_id = ?");
$workouts->execute([$userId]);
$workouts = $workouts->fetchAll(PDO::FETCH_ASSOC);

// Получаем запланированные тренировки
$scheduled = $pdo->prepare("
    SELECT wc.id, wc.workout_date, w.name 
    FROM workout_calendar wc
    JOIN workouts w ON wc.workout_id = w.id
    WHERE wc.user_id = ? AND wc.workout_date >= CURDATE()
    ORDER BY wc.workout_date
");
$scheduled->execute([$userId]);
$scheduledWorkouts = $scheduled->fetchAll(PDO::FETCH_ASSOC);

// Обработка добавления тренировки в календарь
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['workout_id'], $_POST['workout_date'])) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO workout_calendar (user_id, workout_id, workout_date)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $_POST['workout_id'], $_POST['workout_date']]);
        header("Location: calendar.php?success=1");
        exit;
    } catch (PDOException $e) {
        $error = "Ошибка при сохранении: " . $e->getMessage();
    }
}

// Обработка удаления из календаря
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM workout_calendar WHERE id = ? AND user_id = ?");
        $stmt->execute([$_GET['delete'], $userId]);
        
        // Для AJAX-запросов возвращаем JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        
        header("Location: calendar.php");
        exit;
    } catch (PDOException $e) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
        $error = "Ошибка при удалении: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Календарь тренировок | Мой Тренер</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/my_trainer/css/workouts.css">
    <link rel="stylesheet" href="/my_trainer/css/calendar.css">
    <style>
      .flatpickr-input {
    padding: 12px 1px !important;
    border: 2px solid #e2e8f0 !important; 
}
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <main class="container">
        <div class="main-container">
            <div class="page-header">
                <h1 class="page-title">Календарь тренировок</h1>
                <p>Планируйте свои тренировки и следите за расписанием</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php elseif (isset($_GET['success'])): ?>
                <div class="alert alert-success">Тренировка успешно запланирована!</div>
            <?php endif; ?>
            
            <div class="calendar-layout">
                <div class="schedule-card">
                    <h2 class="schedule-title">Новая тренировка</h2>
                    <form method="post">
                        <div class="form-group">
                            <label class="form-label">Выберите тренировку</label>
                            <select name="workout_id" class="form-select" required>
                                <option value="">-- Выберите тренировку --</option>
                                <?php foreach ($workouts as $workout): ?>
                                    <option value="<?= $workout['id'] ?>"><?= htmlspecialchars($workout['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Дата тренировки</label>
                            <input type="text" name="workout_date" id="datepicker" class="form-input" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Запланировать
                        </button>
                    </form>
                </div>
                
                <div class="calendar-content">
                    <h2 class="calendar-title">Предстоящие тренировки</h2>
                    
                    <?php if (empty($scheduledWorkouts)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <p class="empty-text">У вас нет запланированных тренировок</p>
                        </div>
                    <?php else: ?>
                        <div class="workout-list" id="workout-list">
                            <?php foreach ($scheduledWorkouts as $event): ?>
                                <div class="workout-item" id="workout-<?= $event['id'] ?>">
                                    <div>
                                        <span class="workout-date">
                                            <?= date('d.m.Y', strtotime($event['workout_date'])) ?>
                                        </span>
                                        <span class="workout-name"><?= htmlspecialchars($event['name']) ?></span>
                                    </div>
                                    <button class="btn-delete" 
                                            data-workout-id="<?= $event['id'] ?>"
                                            title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    <script>
        $(document).ready(function() {
            // Плавное появление контента
            $('.main-container').hide().fadeIn(500);
            
            // Инициализация календаря
            flatpickr("#datepicker", {
                dateFormat: "Y-m-d",
                minDate: "today",
                locale: "ru",
                disableMobile: true
            });
            
            // Обработчик удаления тренировки
           $(document).on('click', '.btn-delete', function() {
    const workoutId = $(this).data('workout-id');
    const workoutElement = $('#workout-' + workoutId);
    
    if (confirm('Вы уверены, что хотите удалить эту тренировку?')) {
        workoutElement.addClass('fade-out');
        
        $.ajax({
            url: '?delete=' + workoutId,  // Относительный URL
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                setTimeout(function() {
                    workoutElement.remove();
                    checkIfWorkoutListEmpty();
                }, 300);
            },
            error: function(xhr) {
                workoutElement.removeClass('fade-out');
                alert('Ошибка при удалении: ' + (xhr.responseJSON?.error || 'Сервер не доступен'));
            }
        });
    }
});
            
            // Проверка пустого списка тренировок
            function checkIfWorkoutListEmpty() {
                if ($('#workout-list .workout-item').length === 0) {
                    $('#workout-list').html(`
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <p class="empty-text">У вас нет запланированных тренировок</p>
                        </div>
                    `).hide().fadeIn(300);
                }
            }
        });
    </script>
</body>
</html>