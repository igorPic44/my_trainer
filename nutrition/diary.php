<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /my_trainer/auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$date = $_GET['date'] ?? date('Y-m-d');

// Обработка добавления продукта
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        // Обновление настроек
        $calorieLimit = (int)$_POST['calorie_limit'];
        
        $stmt = $pdo->prepare("
            INSERT INTO user_settings (user_id, calorie_limit) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE
            calorie_limit = VALUES(calorie_limit)
        ");
        $stmt->execute([$userId, $calorieLimit]);
        
        header("Location: diary.php?date=$date");
        exit;
    } else {
        // Добавление продукта
        $productId = $_POST['product_id'];
        $amount = $_POST['amount'];
        $mealType = $_POST['meal_type'];

        $stmt = $pdo->prepare("INSERT INTO food_intake (user_id, product_id, date, amount, meal_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $productId, $date, $amount, $mealType]);
        header("Location: diary.php?date=$date");
        exit;
    }
}

// Получаем настройки пользователя
try {
    $stmt = $pdo->prepare("SELECT calorie_limit FROM user_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $calorieLimit = $stmt->fetchColumn() ?? 2000;
} catch (PDOException $e) {
    $calorieLimit = 2000;
}

// Список продуктов
$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Список приёмов пищи
$stmt = $pdo->prepare("
    SELECT fi.*, p.name, p.calories, p.protein, p.fat, p.carbs
    FROM food_intake fi
    JOIN products p ON fi.product_id = p.id
    WHERE fi.user_id = ? AND fi.date = ?
    ORDER BY fi.meal_type
");
$stmt->execute([$userId, $date]);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Рассчет суммарных значений
$totalCalories = 0;
$totalProtein = 0;
$totalFat = 0;
$totalCarbs = 0;

foreach ($entries as $e) {
    $totalCalories += $e['calories'] * $e['amount'] / 100;
    $totalProtein += $e['protein'] * $e['amount'] / 100;
    $totalFat += $e['fat'] * $e['amount'] / 100;
    $totalCarbs += $e['carbs'] * $e['amount'] / 100;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дневник питания</title>
    <link rel="stylesheet" href="/my_trainer/css/nutrition.css?v=<?= time() ?>">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="spacious-container">
    <div class="main-content">
        <h1 class="main-title">Дневник питания</h1>

        <form method="get" class="date-selector section-spacing">
            <label for="date">Дата:</label>
            <input type="date" name="date" value="<?= $date ?>">
            <button type="submit">Показать</button>
        </form>

        <div class="section-spacing">
            <h2 class="section-title">Добавить продукт</h2>
            <form method="post" class="add-product-form">
                <input type="hidden" name="date" value="<?= $date ?>">
                
                <div class="form-row">
                    <label>Продукт:</label>
                    <select name="product_id">
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <label>Количество (г):</label>
                    <input type="number" name="amount" value="100" required>
                </div>
                
                <div class="form-row">
                    <label>Тип приёма пищи:</label>
                    <select name="meal_type">
                        <option value="breakfast">Завтрак</option>
                        <option value="lunch">Обед</option>
                        <option value="dinner">Ужин</option>
                        <option value="snack">Перекус</option>
                    </select>
                </div>

                <button type="submit" class="submit-button">Добавить</button>
            </form>
        </div>

        <div class="section-spacing">
            <h2 class="section-title">Список продуктов за <?= date('d.m.Y', strtotime($date)) ?></h2>
            <?php if (count($entries) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Приём пищи</th>
                                <th>Продукт</th>
                                <th>Грамм</th>
                                <th>Ккал</th>
                                <th>Белки</th>
                                <th>Жиры</th>
                                <th>Углеводы</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $e): ?>
                                <tr>
                                    <td><?= ucfirst($e['meal_type']) ?></td>
                                    <td><?= htmlspecialchars($e['name']) ?></td>
                                    <td><?= $e['amount'] ?></td>
                                    <td><?= round($e['calories'] * $e['amount'] / 100, 1) ?></td>
                                    <td><?= round($e['protein'] * $e['amount'] / 100, 1) ?></td>
                                    <td><?= round($e['fat'] * $e['amount'] / 100, 1) ?></td>
                                    <td><?= round($e['carbs'] * $e['amount'] / 100, 1) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-message">Нет записей за выбранную дату.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nutrition-sidebar">
        <!-- Карточка калорий с круговым индикатором -->
        <div class="calorie-card">
            <h3>Баланс калорий</h3>
            <div class="calorie-progress-circle">
                <svg class="calorie-circle" viewBox="0 0 100 100">
                    <circle class="calorie-circle-bg" cx="50" cy="50" r="45"></circle>
                    <circle class="calorie-circle-fill" cx="50" cy="50" r="45" 
                            stroke-dasharray="<?= min(($totalCalories/$calorieLimit)*283, 283) ?> 283"></circle>
                    <div class="calorie-info">
                        <div class="calorie-amount"><?= round($totalCalories) ?></div>
                        <div class="calorie-goal">из <?= $calorieLimit ?> ккал</div>
                    </div>
                </svg>
            </div>
            
            <div class="macros">
                <div class="macro-item">
                    <div class="macro-name">Белки</div>
                    <div class="macro-value"><?= round($totalProtein) ?>г</div>
                </div>
                <div class="macro-item">
                    <div class="macro-name">Жиры</div>
                    <div class="macro-value"><?= round($totalFat) ?>г</div>
                </div>
                <div class="macro-item">
                    <div class="macro-name">Углеводы</div>
                    <div class="macro-value"><?= round($totalCarbs) ?>г</div>
                </div>
            </div>
        </div>

        <!-- Настройки -->
        <div class="settings-card">
            <h3>Мои настройки</h3>
            <form method="post">
                <input type="hidden" name="update_settings" value="1">
                
                <div class="setting-row">
                    <label>Лимит калорий:</label>
                    <input type="number" name="calorie_limit" value="<?= $calorieLimit ?>">
                </div>
                
                <button type="submit" class="save-settings-button">Сохранить</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>