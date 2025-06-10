<?php
session_start();
include '../includes/db.php';
include '../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $exercises = $_POST['exercises'] ?? [];
    
    try {
        // Создаем тренировку
        $stmt = $pdo->prepare("INSERT INTO workouts (user_id, name, description) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $name, $description]);
        $workout_id = $pdo->lastInsertId();
        
        // Добавляем упражнения
        foreach ($exercises as $exercise_id) {
            $stmt = $pdo->prepare("INSERT INTO workout_exercises (workout_id, exercise_id) VALUES (?, ?)");
            $stmt->execute([$workout_id, $exercise_id]);
        }
        
        $_SESSION['message'] = "Тренировка успешно создана!";
        header("Location: list.php");
        exit();
    } catch (PDOException $e) {
        $error = "Ошибка создания тренировки: " . $e->getMessage();
    }
}

// Получаем список всех упражнений
$exercises = $pdo->query("SELECT * FROM exercises")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Создать новую тренировку</h2>
<?php if (isset($error)): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<form method="post">
    <div>
        <label for="name">Название тренировки:</label>
        <input type="text" id="name" name="name" required>
    </div>
    <div>
        <label for="description">Описание:</label>
        <textarea id="description" name="description"></textarea>
    </div>
    
    <h3>Выберите упражнения:</h3>
    <div class="exercise-list">
        <?php foreach ($exercises as $exercise): ?>
            <div class="exercise-item">
                <input type="checkbox" id="ex-<?php echo $exercise['id']; ?>" 
                       name="exercises[]" value="<?php echo $exercise['id']; ?>">
                <label for="ex-<?php echo $exercise['id']; ?>">
                    <?php echo htmlspecialchars($exercise['name']); ?>
                    <span class="muscle-group">(<?php echo $exercise['muscle_group']; ?>)</span>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
    
    <button type="submit">Создать тренировку</button>
</form>

<?php include '../includes/footer.php'; ?>