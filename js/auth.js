// Показать/скрыть пароль
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Валидация формы в реальном времени
    const form = document.querySelector('.auth-form');
    
    if (form) {
        form.addEventListener('input', function(e) {
            if (e.target.name === 'email') {
                validateEmail(e.target);
            } else if (e.target.name === 'password') {
                validatePassword(e.target);
            }
        });
    }
    
    function validateEmail(input) {
        const value = input.value.trim();
        const inputGroup = input.closest('.input-group');
        
        if (!value.includes('@') || !value.includes('.')) {
            showError(inputGroup, 'Введите корректный email');
        } else {
            clearError(inputGroup);
        }
    }
    
    function validatePassword(input) {
        const value = input.value;
        const inputGroup = input.closest('.input-group');
        
        if (value.length < 6) {
            showError(inputGroup, 'Пароль должен быть не менее 6 символов');
        } else {
            clearError(inputGroup);
        }
    }
    
    function showError(inputGroup, message) {
        inputGroup.classList.add('error');
        
        let errorText = inputGroup.querySelector('.error-text');
        if (!errorText) {
            errorText = document.createElement('p');
            errorText.className = 'error-text';
            inputGroup.appendChild(errorText);
        }
        
        errorText.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    }
    
    function clearError(inputGroup) {
        inputGroup.classList.remove('error');
        const errorText = inputGroup.querySelector('.error-text');
        if (errorText) {
            errorText.remove();
        }
    }
});