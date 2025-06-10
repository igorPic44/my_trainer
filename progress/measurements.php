<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM body_measurements WHERE user_id = ? ORDER BY date DESC");
    $stmt->execute([$userId]);
    $measurements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $lastMeasurement = $measurements[0] ?? null;
} catch (PDOException $e) {
    die("Ошибка при получении данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои замеры | Мой Тренер</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Обновленные стили для контейнера */
        .measurements-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .measurements-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .measurements-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .measurements-content {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }

        .current-measurements {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        .measurement-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .measurement-stat {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid #4f46e5;
            transition: transform 0.3s ease;
        }

        .measurement-stat:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .stat-label {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-date {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 1rem;
            text-align: center;
        }

        .add-measurement-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #4f46e5;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .add-measurement-btn:hover {
            background: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }

        .progress-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        .chart-container {
            height: 300px;
            margin-top: 1.5rem;
            position: relative;
        }

        .history-section {
            grid-column: span 2;
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        .history-table th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
        }

        .history-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .history-table tr:last-child td {
            border-bottom: none;
        }

        .history-table tr:hover {
            background: #f8fafc;
        }

        .action-btns {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #64748b;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .action-btn.delete:hover {
            background: #fee2e2;
            color: #ef4444;
        }

        @media (max-width: 1024px) {
            .measurements-content {
                grid-template-columns: 1fr;
            }
            
            .history-section {
                grid-column: span 1;
            }
        }

        @media (max-width: 768px) {
            .measurement-stats {
                grid-template-columns: 1fr;
            }
            
            .measurements-container {
                padding: 1rem;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="measurements-container">
        <div class="measurements-header">
            <h1 class="measurements-title">Мои замеры</h1>
            <a href="add.php" class="add-measurement-btn">
                <i class="fas fa-plus"></i> Новый замер
            </a>
        </div>
        
        <div class="measurements-content">
            <div class="current-measurements">
                <h2>Текущие показатели</h2>
                <?php if ($lastMeasurement): ?>
                    <div class="measurement-stats">
                        <div class="measurement-stat">
                            <div class="stat-label">Вес</div>
                            <div class="stat-value"><?= htmlspecialchars($lastMeasurement['weight']) ?> кг</div>
                        </div>
                        <div class="measurement-stat">
                            <div class="stat-label">Грудь</div>
                            <div class="stat-value"><?= htmlspecialchars($lastMeasurement['chest']) ?> см</div>
                        </div>
                        <div class="measurement-stat">
                            <div class="stat-label">Талия</div>
                            <div class="stat-value"><?= htmlspecialchars($lastMeasurement['waist']) ?> см</div>
                        </div>
                        <div class="measurement-stat">
                            <div class="stat-label">Бедра</div>
                            <div class="stat-value"><?= htmlspecialchars($lastMeasurement['hips']) ?> см</div>
                        </div>
                        <div class="stat-date">
                            Дата последнего замера: <?= date('d.m.Y', strtotime($lastMeasurement['date'])) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p>У вас пока нет сохраненных замеров.</p>
                <?php endif; ?>
            </div>
            
            <div class="progress-section">
                <h2>Динамика изменений</h2>
                <div class="chart-container">
                    <canvas id="progressChart"></canvas>
                </div>
            </div>
            
            <div class="history-section">
                <h2>История замеров</h2>
                <?php if (!empty($measurements)): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Вес</th>
                                <th>Грудь</th>
                                <th>Талия</th>
                                <th>Бедра</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($measurements as $measurement): ?>
                                <tr>
                                    <td><?= date('d.m.Y', strtotime($measurement['date'])) ?></td>
                                    <td><?= htmlspecialchars($measurement['weight']) ?> кг</td>
                                    <td><?= htmlspecialchars($measurement['chest']) ?> см</td>
                                    <td><?= htmlspecialchars($measurement['waist']) ?> см</td>
                                    <td><?= htmlspecialchars($measurement['hips']) ?> см</td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="edit.php?id=<?= $measurement['id'] ?>" class="action-btn" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $measurement['id'] ?>" class="action-btn delete" title="Удалить" onclick="return confirm('Вы уверены?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>У вас пока нет сохраненных замеров.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('progressChart').getContext('2d');
            const measurements = <?= json_encode($measurements) ?>;
            
            if (measurements.length > 0) {
                const dates = measurements.map(m => new Date(m.date).toLocaleDateString()).reverse();
                const weights = measurements.map(m => parseFloat(m.weight)).reverse();
                const chest = measurements.map(m => parseFloat(m.chest)).reverse();
                const waist = measurements.map(m => parseFloat(m.waist)).reverse();
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [
                            {
                                label: 'Вес (кг)',
                                data: weights,
                                borderColor: 'rgb(79, 70, 229)',
                                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Грудь (см)',
                                data: chest,
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Талия (см)',
                                data: waist,
                                borderColor: 'rgb(239, 68, 68)',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                tension: 0.3,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.raw;
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                document.getElementById('progressChart').style.display = 'none';
                document.querySelector('.progress-chart').innerHTML += 
                    '<p class="empty-chart">Нет данных для построения графика</p>';
            }
        });
    </script>
</body>
</html>