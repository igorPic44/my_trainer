// Общие функции
document.addEventListener('DOMContentLoaded', function() {
    // Выбор упражнений для тренировки
    const exerciseCards = document.querySelectorAll('.exercise-card');
    exerciseCards.forEach(card => {
        card.addEventListener('click', function() {
            this.classList.toggle('selected');
            const formGroups = this.querySelectorAll('.form-group');
            formGroups.forEach(group => group.classList.toggle('d-none'));
            
            updateExercisesInput();
        });
    });
    
    // Обновление скрытого поля с выбранными упражнениями
    function updateExercisesInput() {
        const selectedExercises = [];
        document.querySelectorAll('.exercise-card.selected').forEach(card => {
            const exerciseId = card.getAttribute('data-id');
            const sets = card.querySelector('.sets').value;
            const reps = card.querySelector('.reps').value;
            
            selectedExercises.push({
                id: exerciseId,
                sets: sets,
                reps: reps
            });
        });
        
        document.getElementById('exercises-input').value = JSON.stringify(selectedExercises);
    }
    
    // Обработчики для полей подходов и повторений
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('sets') || e.target.classList.contains('reps')) {
            updateExercisesInput();
        }
    });
});

// Календарь (js/calendar.js)
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: [
            <?php foreach ($calendarEntries as $entry): ?>
            {
                title: '<?= addslashes($entry['workout_name']) ?>',
                start: '<?= $entry['date'] ?>',
                extendedProps: {
                    notes: '<?= addslashes($entry['notes']) ?>'
                }
            },
            <?php endforeach; ?>
        ],
        eventClick: function(info) {
            alert('Заметки: ' + info.event.extendedProps.notes);
        }
    });
    calendar.render();
    
    // Сохранение тренировки в календарь
    document.getElementById('save-workout').addEventListener('click', function() {
        const date = document.getElementById('workout-date').value;
        const workoutId = document.getElementById('workout-select').value;
        const notes = document.getElementById('workout-notes').value;
        
        fetch('../includes/functions.php?action=add_to_calendar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                date: date,
                workout_id: workoutId,
                notes: notes
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        });
    });
});

// Продолжение в следующих файлах...