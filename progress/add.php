<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Обработка формы добавления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Валидация данных
        $date = $_POST['date'] ?? '';
        $weight = floatval($_POST['weight'] ?? 0);
        $chest = floatval($_POST['chest'] ?? 0);
        $waist = floatval($_POST['waist'] ?? 0);
        $hips = floatval($_POST['hips'] ?? 0);
        $biceps = floatval($_POST['biceps'] ?? 0);

        if (empty($date)) {
            throw new Exception("Укажите дату замера");
        }

        // Добавление в базу данных
        $stmt = $pdo->prepare("
            INSERT INTO body_measurements 
            (user_id, date, weight, chest, waist, hips, biceps) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $date, $weight, $chest, $waist, $hips, $biceps]);
        
        $success = "Замеры успешно сохранены!";
        $_POST = []; // Очищаем поля формы
        
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить замеры | Мой Тренер</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #e0e7ff;
            --secondary: #10b981;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #94a3b8;
            --danger: #ef4444;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .measurement-form {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .form-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            background: linear-gradient(90deg, var(--primary), #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-input {
            width: 100%;
            padding: 0.8rem 0.2rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }

        .form-actions {
            grid-column: span 2;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(79, 70, 229, 0.2);
        }

        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: var(--primary);
            color: var(--primary);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #16a34a;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .input-unit {
            position: relative;
        }

        .input-unit .unit {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .form-actions {
                grid-column: span 1;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="measurement-form">
            <div class="form-header">
                <h1 class="form-title">Добавить новые замеры</h1>
                <a href="measurements.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Назад к замерам
                </a>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php elseif ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="date" class="form-label">Дата замера</label>
                        <input type="text" id="date" name="date" class="form-input" 
                               value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight" class="form-label">Вес</label>
                        <div class="input-unit">
                            <input type="number" id="weight" name="weight" class="form-input" 
                                   step="0.1" min="0" value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>" required>
                            <span class="unit">кг</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="chest" class="form-label">Грудь</label>
                        <div class="input-unit">
                            <input type="number" id="chest" name="chest" class="form-input" 
                                   step="0.1" min="0" value="<?= htmlspecialchars($_POST['chest'] ?? '') ?>" required>
                            <span class="unit">см</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="waist" class="form-label">Талия</label>
                        <div class="input-unit">
                            <input type="number" id="waist" name="waist" class="form-input" 
                                   step="0.1" min="0" value="<?= htmlspecialchars($_POST['waist'] ?? '') ?>" required>
                            <span class="unit">см</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="hips" class="form-label">Бедра</label>
                        <div class="input-unit">
                            <input type="number" id="hips" name="hips" class="form-input" 
                                   step="0.1" min="0" value="<?= htmlspecialchars($_POST['hips'] ?? '') ?>" required>
                            <span class="unit">см</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="biceps" class="form-label">Бицепс</label>
                        <div class="input-unit">
                            <input type="number" id="biceps" name="biceps" class="form-input" 
                                   step="0.1" min="0" value="<?= htmlspecialchars($_POST['biceps'] ?? '') ?>">
                            <span class="unit">см</span>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Сбросить
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить замеры
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    <script>
        // Инициализация календаря
        flatpickr("#date", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            locale: "ru",
            disableMobile: true
        });

        // Фокус на первое поле при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('weight').focus();
        });
    </script>
</body>
</html>