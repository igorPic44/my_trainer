<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Валидация
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Введите пароль';
    }
    
    // Проверка учетных данных
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                // Проверяем, нужно ли запомнить пользователя
                if (isset($_POST['remember'])) {
                    // Создаем куки на 30 дней
                    $cookieValue = json_encode([
                        'user_id' => $user['id'],
                        'token' => bin2hex(random_bytes(16))
                    ]);
                    setcookie('remember_me', $cookieValue, time() + 60 * 60 * 24 * 30, '/');
                }
                
                header('Location: ../index.php');
                exit;
            } else {
                $errors['general'] = 'Неверный email или пароль';
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Ошибка при авторизации. Попробуйте позже.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Отображение сообщения об успешной регистрации
$registered = isset($_GET['registered']) ? true : false;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | Мой Тренер</title>
    <link rel="stylesheet" href="../css/auth-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-logo">
            <i class="fas fa-dumbbell"></i>
            <span>Мой Тренер</span>
        </div>
        
        <div class="auth-card">
            <h1>Вход в аккаунт</h1>
            <p class="subtitle">Продолжите свой фитнес-путь</p>
            
            <?php if ($registered): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    Регистрация прошла успешно! Теперь вы можете войти.
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="auth-form">
                <div class="input-group <?= !empty($errors['email']) ? 'error' : '' ?>">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($email ?? '') ?>" 
                               placeholder="example@mail.com">
                    </div>
                    <?php if (!empty($errors['email'])): ?>
                        <p class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="input-group <?= !empty($errors['password']) ? 'error' : '' ?>">
                    <label for="password">Пароль</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="Ваш пароль">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="error-text"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['password']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Запомнить меня
                    </label>
                    <a href="forgot-password.php" class="forgot-password">Забыли пароль?</a>
                </div>
                
                <button type="submit" class="btn primary">
                    <i class="fas fa-sign-in-alt"></i> Войти
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Ещё нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
            </div>
        </div>
    </div>

    <script src="../js/auth.js"></script>
</body>
</html>