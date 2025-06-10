document.addEventListener('DOMContentLoaded', function() {
    // Подтверждение удаления тренировки
    const deleteButtons = document.querySelectorAll('.btn-danger');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите удалить эту тренировку?')) {
                e.preventDefault();
            }
        });
    });
    
    // Анимация карточек
    const workoutCards = document.querySelectorAll('.workout-card');
    
    workoutCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });
});