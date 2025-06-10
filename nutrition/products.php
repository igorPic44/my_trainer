<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

$stmt = $pdo->query("SELECT * FROM products ORDER BY name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>База продуктов</title>
    <link rel="stylesheet" href="/my_trainer/css/nutrition.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <h1>База продуктов</h1>
    <table>
        <thead>
            <tr>
                <th>Название</th>
                <th>Ккал</th>
                <th>Белки</th>
                <th>Жиры</th>
                <th>Углеводы</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= $p['calories'] ?></td>
                    <td><?= $p['protein'] ?></td>
                    <td><?= $p['fat'] ?></td>
                    <td><?= $p['carbs'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
