document.addEventListener('DOMContentLoaded', function() {
    // Активация текущей даты в календаре (если будет добавлен)
    const currentDate = new Date();
    const dayElements = document.querySelectorAll('.calendar-day');
    
    dayElements.forEach(day => {
        if (day.dataset.date === currentDate.toISOString().split('T')[0]) {
            day.classList.add('today');
        }
    });
    
    // Инициализация графиков (если будут добавлены)
    const progressChart = document.getElementById('progressChart');
    
    if (progressChart) {
        new Chart(progressChart, {
            type: 'line',
            data: {
                labels: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн'],
                datasets: [{
                    label: 'Вес (кг)',
                    data: [85, 83, 82, 81, 80, 79],
                    borderColor: '#3b82f6',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }
});