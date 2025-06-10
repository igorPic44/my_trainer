<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Валидация
    $errors = [];
    
    if (empty($username)) {
        $errors['username'] = 'Введите имя пользователя';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Имя должно быть не менее 3 символов';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Пароль должен быть не менее 6 символов';
    }
    
    if ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Пароли не совпадают';
    }
    
    // Проверка уникальности email и username
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->fetch()) {
            $errors['email'] = 'Пользователь с таким email или именем уже существует';
        }
    }
    
    // Регистрация пользователя
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $pdo->beginTransaction();
            
            // Создаем пользователя
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);
            
            // Получаем ID нового пользователя
            $newUserId = $pdo->lastInsertId();
            
            // Создаем настройки по умолчанию
            $pdo->prepare("INSERT INTO user_settings (user_id, water_goal) VALUES (?, 2000)")
                ->execute([$newUserId]);
            
            $pdo->commit();
            
            // Авторизуем пользователя
            session_start();
            $_SESSION['user_id'] = $newUserId;
            
            header('Location: /water/tracker.php');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Ошибка при регистрации: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | Мой Тренер</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Создать аккаунт</h1>
                <p>Присоединяйтесь к сообществу "Мой Тренер"</p>
            </div>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?= $errors['general'] ?></div>
            <?php endif; ?>
            
            <form method="post" class="auth-form">
                <div class="form-group <?= !empty($errors['username']) ? 'has-error' : '' ?>">
                    <label for="username">Имя пользователя</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" 
                           placeholder="Придумайте имя пользователя" required>
                    <?php if (!empty($errors['username'])): ?>
                        <span class="error-message"><?= $errors['username'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group <?= !empty($errors['email']) ? 'has-error' : '' ?>">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" 
                           placeholder="Ваш email" required>
                    <?php if (!empty($errors['email'])): ?>
                        <span class="error-message"><?= $errors['email'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group <?= !empty($errors['password']) ? 'has-error' : '' ?>">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Не менее 6 символов" required>
                    <?php if (!empty($errors['password'])): ?>
                        <span class="error-message"><?= $errors['password'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group <?= !empty($errors['password_confirm']) ? 'has-error' : '' ?>">
                    <label for="password_confirm">Подтвердите пароль</label>
                    <input type="password" id="password_confirm" name="password_confirm" 
                           placeholder="Повторите пароль" required>
                    <?php if (!empty($errors['password_confirm'])): ?>
                        <span class="error-message"><?= $errors['password_confirm'] ?></span>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
            </form>
            
            <div class="auth-footer">
                <p>Уже есть аккаунт? <a href="login.php">Войдите</a></p>
            </div>
        </div>
        
        <div class="auth-image">
            <img src="../images/auth-bg.jpg" alt="Фитнес">
        </div>
    </div>
</body>
</html>