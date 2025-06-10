<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /my_trainer/auth/login.php');
    exit;
}

// Обработка удаления тренировки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_workout'])) {
    $workoutId = $_POST['workout_id'];
    $userId = $_SESSION['user_id'];
    
    try {
        // Проверяем, что тренировка принадлежит пользователю
        $stmt = $pdo->prepare("SELECT user_id FROM workouts WHERE id = ?");
        $stmt->execute([$workoutId]);
        $workout = $stmt->fetch();
        
        if ($workout && $workout['user_id'] == $userId) {
            // Удаляем связанные записи из календаря тренировок
            $stmt = $pdo->prepare("DELETE FROM workout_calendar WHERE workout_id = ?");
            $stmt->execute([$workoutId]);
            
            // Удаляем связанные упражнения
            $stmt = $pdo->prepare("DELETE FROM workout_exercises WHERE workout_id = ?");
            $stmt->execute([$workoutId]);
            
            // Удаляем саму тренировку
            $stmt = $pdo->prepare("DELETE FROM workouts WHERE id = ?");
            $stmt->execute([$workoutId]);
            
            // Перенаправляем без сообщения об успехе
            header("Location: list.php");
            exit;
        }
    } catch (PDOException $e) {
        die("Ошибка при удалении тренировки: " . $e->getMessage());
    }
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT w.*, COUNT(we.id) as exercise_count 
        FROM workouts w
        LEFT JOIN workout_exercises we ON w.id = we.workout_id
        WHERE w.user_id = ?
        GROUP BY w.id
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при получении тренировок: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои тренировки | Мой Тренер</title>
    <link rel="stylesheet" href="/my_trainer/css/workouts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .list-workout-actions {
            display: flex;
            gap: 8px;
        }
        
        .list-btn-view {
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            background: #4f46e5;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        
        .list-btn-view:hover {
            background-color: #4338ca;
            transform: translateY(-2px);
        }
        
        .list-btn-delete {
            background-color: #ef4444;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .list-btn-delete:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }
        
        .list-delete-form {
            margin: 0;
        }
        
        /* Стили для карусели */
        .list-workouts-carousel {
            display: flex;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            gap: 20px;
            padding-bottom: 20px;
            scrollbar-width: none;
        }
        
        .list-workouts-carousel::-webkit-scrollbar {
            display: none;
        }
        
        .list-workout-card {
            scroll-snap-align: start;
            min-width: 300px;
            flex: 0 0 auto;
        }
        
        .list-carousel-nav {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }
        
        .list-carousel-nav button {
            background: #e2e8f0;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .list-carousel-nav button:hover {
            background: #cbd5e0;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="list-workouts-container">
        <div class="list-workouts-header">
            <h1 class="list-workouts-title">Мои тренировки</h1>
            <a href="create.php" class="list-btn-new-workout">
                <i class="fas fa-plus"></i> Новая тренировка
            </a>
        </div>

        <?php if (!empty($workouts)): ?>
            <div class="list-workouts-carousel" id="workoutsCarousel">
                <?php foreach ($workouts as $workout): ?>
                    <div class="list-workout-card">
                        <div class="list-workout-card-header">
                            <h3><?= htmlspecialchars($workout['name']) ?></h3>
                            <span class="list-workout-badge">
                                <?= $workout['exercise_count'] ?> <?= pluralize($workout['exercise_count'], ['упражнение', 'упражнения', 'упражнений']) ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($workout['description'])): ?>
                            <div class="list-workout-card-description">
                                <p><?= htmlspecialchars($workout['description']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="list-workout-card-exercises">
                            <?php 
                            $stmt = $pdo->prepare("
                                SELECT e.name, e.muscle_group, we.sets, we.reps 
                                FROM workout_exercises we
                                JOIN exercises e ON we.exercise_id = e.id
                                WHERE we.workout_id = ?
                                LIMIT 3
                            ");
                            $stmt->execute([$workout['id']]);
                            $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($exercises as $exercise): ?>
                                <div class="list-workout-exercise">
                                    <span class="list-exercise-name"><?= htmlspecialchars($exercise['name']) ?></span>
                                    <span class="list-exercise-sets-reps">
                                        <?= $exercise['sets'] ?>x<?= $exercise['reps'] ?>
                                    </span>
                                    <span class="list-exercise-muscle-group">
                                        <?= htmlspecialchars($exercise['muscle_group']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if ($workout['exercise_count'] > 3): ?>
                                <div class="list-more-exercises">
                                    + ещё <?= $workout['exercise_count'] - 3 ?> <?= pluralize($workout['exercise_count'] - 3, ['упражнение', 'упражнения', 'упражнений']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="list-workout-card-footer">
                            <span class="list-workout-date">
                                Создано: <?= date('d.m.Y', strtotime($workout['created_at'])) ?>
                            </span>
                            <div class="list-workout-actions">
                                <a href="view.php?id=<?= $workout['id'] ?>" class="list-btn-view" title="Просмотр">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="post" class="list-delete-form" onsubmit="return confirm('Вы уверены, что хотите удалить эту тренировку?')">
                                    <input type="hidden" name="workout_id" value="<?= $workout['id'] ?>">
                                    <button type="submit" name="delete_workout" class="list-btn-delete" title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="list-carousel-nav">
                <button onclick="scrollCarousel(-300)" title="Предыдущая"><i class="fas fa-chevron-left"></i></button>
                <button onclick="scrollCarousel(300)" title="Следующая"><i class="fas fa-chevron-right"></i></button>
            </div>
        <?php else: ?>
            <div class="list-empty-state">
                <div class="list-empty-state-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <p class="list-empty-state-text">У вас пока нет тренировок</p>
                <a href="create.php" class="list-btn-create-first">
                    Создать первую тренировку
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script>
    function scrollCarousel(offset) {
        const carousel = document.getElementById('workoutsCarousel');
        carousel.scrollBy({
            left: offset,
            behavior: 'smooth'
        });
    }
    
    // Автоматическая прокрутка каждые 5 секунд
    let autoScroll = setInterval(() => {
        scrollCarousel(300);
    }, 5000);
    
    // Остановка автоскролла при взаимодействии
    document.getElementById('workoutsCarousel').addEventListener('mouseenter', () => {
        clearInterval(autoScroll);
    });
    
    document.getElementById('workoutsCarousel').addEventListener('mouseleave', () => {
        autoScroll = setInterval(() => {
            scrollCarousel(300);
        }, 5000);
    });
    </script>
</body>
</html>