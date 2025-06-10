<?php
require_once 'db.php';

// Функция для получения всех упражнений
function getAllExercises() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM exercises");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Функция для создания тренировки
function createWorkout($userId, $name, $description, $exercises) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Вставляем тренировку
        $stmt = $pdo->prepare("INSERT INTO workouts (user_id, name, description) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $name, $description]);
        $workoutId = $pdo->lastInsertId();
        
        // Вставляем упражнения для тренировки
        $stmt = $pdo->prepare("INSERT INTO workout_exercises (workout_id, exercise_id, sets, reps) VALUES (?, ?, ?, ?)");
        
        foreach ($exercises as $exercise) {
            $stmt->execute([$workoutId, $exercise['id'], $exercise['sets'], $exercise['reps']]);
        }
        
        $pdo->commit();
        return $workoutId;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}


function getWorkoutsCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM workouts WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function getTodayWaterIntake($userId) {
    global $pdo;
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM water_intake WHERE user_id = ? AND date = ?");
    $stmt->execute([$userId, $today]);
    return $stmt->fetchColumn();
}

function getTodayCalories($userId) {
    global $pdo;
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(p.calories * fi.amount / 100), 0) 
                          FROM food_intake fi
                          JOIN products p ON fi.product_id = p.id
                          WHERE fi.user_id = ? AND fi.date = ?");
    $stmt->execute([$userId, $today]);
    return round($stmt->fetchColumn());
}

function getProgress($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT weight FROM body_measurements 
                          WHERE user_id = ? 
                          ORDER BY date DESC LIMIT 1");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    return [
        'weight' => $result ? $result['weight'] : '—'
    ];
}

function getUpcomingWorkouts($userId) {
    global $pdo;
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT w.*, wc.workout_date AS date, COUNT(we.id) as exercise_count
                          FROM workout_calendar wc
                          JOIN workouts w ON wc.workout_id = w.id
                          LEFT JOIN workout_exercises we ON w.id = we.workout_id
                          WHERE w.user_id = ? AND wc.workout_date >= ?
                          GROUP BY wc.id, w.id
                          ORDER BY wc.workout_date ASC
                          LIMIT 3");
    $stmt->execute([$userId, $today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function russianMonth($monthNumber) {
    $months = [
        '01' => 'янв', '02' => 'фев', '03' => 'мар', 
        '04' => 'апр', '05' => 'май', '06' => 'июн',
        '07' => 'июл', '08' => 'авг', '09' => 'сен',
        '10' => 'окт', '11' => 'ноя', '12' => 'дек'
    ];
    return $months[$monthNumber];
}
function pluralize($number, $titles) {
    $cases = [2, 0, 1, 1, 1, 2];
    return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}
?>