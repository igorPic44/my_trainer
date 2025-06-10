<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Проверка авторизации
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// Обработка добавления воды
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['amount'])) {
        // Добавление воды
        $amount = (int)$_POST['amount'];
        
        if ($amount > 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO water_intake (user_id, amount, date, time) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$userId, $amount, $today]);
            } catch (PDOException $e) {
                die("Ошибка при добавлении воды: " . $e->getMessage());
            }
        }
    } elseif (isset($_POST['water_goal'])) {
        // Обновление лимита воды
        $waterGoal = (int)$_POST['water_goal'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO user_settings (user_id, water_goal) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE
                water_goal = VALUES(water_goal)
            ");
            $stmt->execute([$userId, $waterGoal]);
        } catch (PDOException $e) {
            die("Ошибка при обновлении лимита воды: " . $e->getMessage());
        }
    }
    
    // Перенаправляем на трекер
    header("Location: tracker.php");
    exit;
}

// Получаем данные о воде за сегодня
try {
    $stmt = $pdo->prepare("SELECT * FROM water_intake WHERE user_id = ? AND date = ? ORDER BY time DESC");
    $stmt->execute([$userId, $today]);
    $waterRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Считаем общее количество воды за сегодня
    $totalWater = 0;
    foreach ($waterRecords as $record) {
        $totalWater += $record['amount'];
    }
    
    // Получаем лимит воды из user_settings
    $stmt = $pdo->prepare("SELECT water_goal FROM user_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $waterGoal = $stmt->fetchColumn() ?? 2000; // Значение по умолчанию
    
} catch (PDOException $e) {
    die("Ошибка при получении данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Трекер воды | Мой Тренер</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-light: #93c5fd;
            --primary-lighter: #dbeafe;
            --secondary: #10b981;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #94a3b8;
            --danger: #ef4444;
            --water-blue: #60a5fa;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8fafc;
            color: var(--dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .water-container {
            max-width: 480px;
            margin: 0 auto;
            padding: 20px;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .water-header {
            text-align: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .water-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(90deg, var(--primary), #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .water-date {
            color: var(--gray);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        /* Основной контент */
        .water-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Карточка прогресса */
        .water-progress-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            text-align: center;
        }

        .water-progress-container {
            position: relative;
            height: 200px;
            margin: 1rem auto;
            width: 200px;
        }

        .water-progress-circle {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .water-progress-bg {
            fill: none;
            stroke: var(--primary-lighter);
            stroke-width: 10;
        }

        .water-progress-fill {
            fill: none;
            stroke: var(--water-blue);
            stroke-width: 10;
            stroke-linecap: round;
            stroke-dasharray: 565;
            stroke-dashoffset: calc(565 - (565 * <?= min($totalWater / $waterGoal, 1) ?>));
            transition: stroke-dashoffset 0.8s ease;
        }

        .water-progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .water-amount {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
        }

        .water-goal {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Форма настроек */
        .water-settings-form {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .water-goal-input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
        }

        .water-goal-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .water-goal-btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .water-goal-btn:hover {
            background: #0d9488;
        }

        /* Кнопки добавления */
        .water-buttons-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .water-buttons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .water-btn {
            border: none;
            background: var(--primary);
            color: white;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 80px;
        }

        .water-btn:hover {
            background: #2563eb;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
        }

        .water-btn i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        /* Форма ввода */
        .water-custom-form {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .water-custom-input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
        }

        .water-custom-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .water-submit-btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .water-submit-btn:hover {
            background: #0d9488;
        }

        /* История */
        .water-history-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .water-history-title {
            font-size: 1.2rem;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .water-records {
            list-style: none;
            padding: 0;
            margin: 0;
            flex: 1;
            overflow-y: auto;
            max-height: 200px;
        }

        .water-record {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
        }

        .water-record:hover {
            background: #e2e8f0;
        }

        .record-amount {
            font-weight: 600;
            color: var(--primary);
        }

        .record-time {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .delete-btn {
            color: var(--gray);
            transition: all 0.2s;
            padding: 0.5rem;
            border-radius: 50%;
        }

        .delete-btn:hover {
            color: var(--danger);
            background: #fee2e2;
        }

        .water-empty {
            color: var(--gray);
            text-align: center;
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Анимации */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .water-pulse {
            animation: pulse 0.5s ease;
        }

        /* Адаптивность */
        @media (max-width: 480px) {
            .water-container {
                padding: 15px;
            }
            
            .water-buttons-grid {
                grid-template-columns: 1fr;
            }
            
            .water-btn {
                height: 60px;
                flex-direction: row;
                justify-content: center;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="water-container">
        <div class="water-header">
            <h1 class="water-title">Трекер воды</h1>
            <p class="water-date">Сегодня, <?= date('d.m.Y') ?></p>
        </div>
        
        <div class="water-content">
            <!-- Карточка прогресса -->
            <div class="water-progress-card">
                <div class="water-progress-container">
                    <svg class="water-progress-circle" viewBox="0 0 200 200">
                        <circle class="water-progress-bg" cx="100" cy="100" r="90"></circle>
                        <circle class="water-progress-fill" cx="100" cy="100" r="90"></circle>
                    </svg>
                    <div class="water-progress-text">
                        <div class="water-amount"><?= $totalWater ?> мл</div>
                        <div class="water-goal">из <?= $waterGoal ?> мл</div>
                    </div>
                </div>
                
                <!-- Форма изменения лимита -->
                <form method="post" class="water-settings-form">
                    <input type="number" name="water_goal" min="500" max="5000" value="<?= $waterGoal ?>" class="water-goal-input" placeholder="Новый лимит">
                    <button type="submit" class="water-goal-btn">OK</button>
                </form>
            </div>
            
            <!-- Карточка кнопок -->
            <div class="water-buttons-card">
                <div class="water-buttons-grid">
                    <form method="post" class="water-form">
                        <input type="hidden" name="amount" value="250">
                        <button type="submit" class="water-btn">
                            <i class="fas fa-glass-water"></i>
                            250 мл
                        </button>
                    </form>
                    
                    <form method="post" class="water-form">
                        <input type="hidden" name="amount" value="500">
                        <button type="submit" class="water-btn">
                            <i class="fas fa-bottle-water"></i>
                            500 мл
                        </button>
                    </form>
                    
                    <form method="post" class="water-form">
                        <input type="hidden" name="amount" value="1000">
                        <button type="submit" class="water-btn">
                            <i class="fas fa-jug"></i>
                            1000 мл
                        </button>
                    </form>
                </div>
                
                <form method="post" class="water-custom-form">
                    <input type="number" name="amount" min="1" placeholder="Другое количество" class="water-custom-input" required>
                    <button type="submit" class="water-submit-btn">+</button>
                </form>
            </div>
            
            <!-- Карточка истории -->
            <div class="water-history-card">
                <h3 class="water-history-title"><i class="fas fa-history"></i> История</h3>
                
                <?php if (!empty($waterRecords)): ?>
                    <ul class="water-records">
                        <?php foreach ($waterRecords as $record): ?>
                            <li class="water-record">
                                <span class="record-amount"><?= $record['amount'] ?> мл</span>
                                <span class="record-time"><?= date('H:i', strtotime($record['time'])) ?></span>
                                <a href="delete.php?id=<?= $record['id'] ?>" class="delete-btn" title="Удалить">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="water-empty">
                        <i class="fas fa-tint" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Вы ещё не пили воду сегодня</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>

    <script>
        // Анимация при добавлении воды
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.water-form, .water-custom-form, .water-settings-form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const progressFill = document.querySelector('.water-progress-fill');
                    if (progressFill) {
                        progressFill.classList.add('water-pulse');
                        
                        setTimeout(() => {
                            progressFill.classList.remove('water-pulse');
                        }, 500);
                    }
                });
            });
        });
    </script>
</body>
</html>