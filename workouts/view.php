<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /my_trainer/auth/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$workoutId = $_GET['id'];
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT w.*, u.username 
    FROM workouts w
    JOIN users u ON w.user_id = u.id
    WHERE w.id = ? AND w.user_id = ?
");
$stmt->execute([$workoutId, $userId]);
$workout = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$workout) {
    header('Location: list.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT e.*, we.sets, we.reps
    FROM workout_exercises we
    JOIN exercises e ON we.exercise_id = e.id
    WHERE we.workout_id = ?
    ORDER BY e.name
");
$stmt->execute([$workoutId]);
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Просмотр тренировки</title>
    <link rel="stylesheet" href="/my_trainer/css/workouts.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="view-workout-container">
        <h1><?php echo htmlspecialchars($workout['name']); ?></h1>
        <p class="view-workout-meta">Создано: <?php echo date('d.m.Y', strtotime($workout['created_at'])); ?></p>
        
        <?php if (!empty($workout['description'])): ?>
            <div class="view-workout-description">
                <?php echo nl2br(htmlspecialchars($workout['description'])); ?>
            </div>
        <?php endif; ?>
        
        <button id="startWorkout" class="view-btn-start">Начать тренировку</button>

        <div id="exerciseStep" class="view-exercise-step" style="display: none;">
            <h3 id="currentExerciseName"></h3>
            <p id="currentExerciseInfo"></p>
            <button id="nextExerciseBtn" class="view-btn-next">Следующее упражнение</button>
        </div>

        <div id="workoutComplete" class="view-workout-complete" style="display: none;">
            <h2>Молодец, продолжай в том же духе!</h2>
            <a href="/my_trainer/index.php" class="view-btn-main">На главную</a>
        </div>
        
        <div id="exerciseList" class="view-exercises-container">
            <h2>Упражнения (<?php echo count($exercises); ?>)</h2>
            <div class="view-exercises-list">
                <?php foreach ($exercises as $exercise): ?>
                    <div class="view-exercise-item">
                        <h3><?php echo htmlspecialchars($exercise['name']); ?></h3>
                        <div class="view-exercise-meta">
                            <span>Группа: <?php echo htmlspecialchars($exercise['muscle_group']); ?></span>
                            <span>Подходы: <?php echo $exercise['sets']; ?></span>
                            <span>Повторения: <?php echo $exercise['reps']; ?></span>
                        </div>
                        <?php if (!empty($exercise['description'])): ?>
                            <p class="view-exercise-description">
                                <?php echo nl2br(htmlspecialchars($exercise['description'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script>
        const exercises = <?php echo json_encode($exercises); ?>;
        let current = 0;

        const startBtn = document.getElementById('startWorkout');
        const stepBlock = document.getElementById('exerciseStep');
        const nameField = document.getElementById('currentExerciseName');
        const infoField = document.getElementById('currentExerciseInfo');
        const nextBtn = document.getElementById('nextExerciseBtn');
        const completeMsg = document.getElementById('workoutComplete');
        const exerciseList = document.getElementById('exerciseList');

        startBtn.addEventListener('click', () => {
            startBtn.style.display = 'none';
            exerciseList.style.display = 'none';
            showExercise();
        });

        nextBtn.addEventListener('click', () => {
            current++;
            if (current < exercises.length) {
                showExercise();
            } else {
                stepBlock.style.display = 'none';
                completeMsg.style.display = 'block';
            }
        });

        function showExercise() {
            stepBlock.style.display = 'block';
            nameField.innerText = exercises[current].name;
            infoField.innerText = `Подходы: ${exercises[current].sets}, Повторения: ${exercises[current].reps}`;
        }
    </script>
</body>
</html>