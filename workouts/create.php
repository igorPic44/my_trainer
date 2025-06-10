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

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Обработка формы создания тренировки
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $exercises = $_POST['exercises'] ?? [];

    if (empty($name)) {
        $error = 'Название тренировки обязательно';
    } elseif (count($exercises) === 0) {
        $error = 'Добавьте хотя бы одно упражнение';
    } else {
        try {
            $pdo->beginTransaction();

            // Создаем тренировку
            $stmt = $pdo->prepare("INSERT INTO workouts (user_id, name, description) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $name, $description]);
            $workoutId = $pdo->lastInsertId();

            // Добавляем упражнения
            foreach ($exercises as $exerciseId => $data) {
                $sets = (int)$data['sets'];
                $reps = (int)$data['reps'];
                
                $stmt = $pdo->prepare("INSERT INTO workout_exercises (workout_id, exercise_id, sets, reps) VALUES (?, ?, ?, ?)");
                $stmt->execute([$workoutId, $exerciseId, $sets, $reps]);
            }

            $pdo->commit();
            
            // Перенаправляем на список тренировок
            header("Location: list.php");
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Ошибка при создании тренировки: " . $e->getMessage();
        }
    }
}

// Получаем список всех упражнений
try {
    $stmt = $pdo->query("SELECT * FROM exercises ORDER BY name");
    $allExercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка при получении упражнений: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать тренировку | Мой Тренер</title>
    <link rel="stylesheet" href="/my_trainer/css/workouts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .create-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 80px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .create-title {
            color: #2d3748;
            font-size: 24px;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .create-form-group {
            margin-bottom: 25px;
        }
        
        .create-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2d3748;
        }
        
        .create-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .create-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .create-exercise-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
        }
        
        .create-exercise-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .create-checkbox {
            margin-right: 10px;
        }
        
        .create-exercise-title {
            font-weight: 500;
            flex-grow: 1;
        }
        
        .create-sets-reps {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .create-small-input {
            width: 80px;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .create-btn {
            background: #4f46e5;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .create-btn:hover {
            background: #4338ca;
        }
        
        .create-error {
            color: #ef4444;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="create-container">
        <h1 class="create-title">Создать новую тренировку</h1>
        
        <?php if ($error): ?>
            <div class="create-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="create-form-group">
                <label for="name" class="create-label">Название тренировки</label>
                <input type="text" id="name" name="name" class="create-input" required>
            </div>
            
            <div class="create-form-group">
                <label for="description" class="create-label">Описание (необязательно)</label>
                <textarea id="description" name="description" class="create-input create-textarea"></textarea>
            </div>
            
            <div class="create-form-group">
                <h3>Добавьте упражнения</h3>
                
                <?php foreach ($allExercises as $exercise): ?>
                    <div class="create-exercise-card">
                        <div class="create-exercise-header">
                            <input type="checkbox" 
                                   id="ex-<?= $exercise['id'] ?>" 
                                   name="exercises[<?= $exercise['id'] ?>][selected]" 
                                   class="create-checkbox exercise-checkbox">
                            <label for="ex-<?= $exercise['id'] ?>" class="create-exercise-title">
                                <?= htmlspecialchars($exercise['name']) ?>
                                <span style="color: #64748b; font-style: italic;">
                                    (<?= htmlspecialchars($exercise['muscle_group']) ?>)
                                </span>
                            </label>
                        </div>
                        
                        <div class="exercise-details" style="display: none;">
                            <div class="create-sets-reps">
                                <div>
                                    <label>Подходы</label>
                                    <input type="number" 
                                           name="exercises[<?= $exercise['id'] ?>][sets]" 
                                           class="create-small-input" 
                                           min="1" 
                                           max="20" 
                                           value="3">
                                </div>
                                <div>
                                    <label>Повторения</label>
                                    <input type="number" 
                                           name="exercises[<?= $exercise['id'] ?>][reps]" 
                                           class="create-small-input" 
                                           min="1" 
                                           max="50" 
                                           value="10">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" class="create-btn">Создать тренировку</button>
        </form>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
    // Показываем/скрываем детали упражнения при выборе
    document.querySelectorAll('.exercise-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const details = this.closest('.create-exercise-card').querySelector('.exercise-details');
            details.style.display = this.checked ? 'block' : 'none';
        });
    });
    </script>
</body>
</html>