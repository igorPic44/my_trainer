<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /my_trainer/auth/login.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Калькулятор калорий</title>
    <link rel="stylesheet" href="/my_trainer/css/nutrition.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <h1>Калькулятор калорий</h1>

    <form id="calc-form">
        <label for="product">Продукт:</label>
        <select id="product" name="product">
            <?php foreach ($products as $product): ?>
                <option value="<?= $product['id'] ?>"
                        data-calories="<?= $product['calories'] ?>"
                        data-protein="<?= $product['protein'] ?>"
                        data-fat="<?= $product['fat'] ?>"
                        data-carbs="<?= $product['carbs'] ?>">
                    <?= htmlspecialchars($product['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="amount">Количество (г):</label>
        <input type="number" id="amount" name="amount" value="100">

        <button type="button" onclick="calculate()">Рассчитать</button>
    </form>

    <div id="result" class="calc-result">
        <p>Калории: <span id="res-calories">0</span></p>
        <p>Белки: <span id="res-protein">0</span> г</p>
        <p>Жиры: <span id="res-fat">0</span> г</p>
        <p>Углеводы: <span id="res-carbs">0</span> г</p>
    </div>
</div>

<script>
function calculate() {
    const selected = document.querySelector('#product option:checked');
    const amount = parseFloat(document.getElementById('amount').value) || 0;

    const calories = selected.dataset.calories * amount / 100;
    const protein = selected.dataset.protein * amount / 100;
    const fat = selected.dataset.fat * amount / 100;
    const carbs = selected.dataset.carbs * amount / 100;

    document.getElementById('res-calories').textContent = calories.toFixed(1);
    document.getElementById('res-protein').textContent = protein.toFixed(1);
    document.getElementById('res-fat').textContent = fat.toFixed(1);
    document.getElementById('res-carbs').textContent = carbs.toFixed(1);
}
</script>
</body>
</html>
