    </div> <!-- Закрытие main-container -->

    <!-- Подключение JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Проверка загрузки CSS
        $(document).ready(function() {
            // Если CSS не загрузился, покажем fallback-версию
            if ($('body').css('font-family') !== 'Poppins, sans-serif') {
                $('.fallback-container').show();
                $('.main-container').hide();
            }
            
            // Анимация появления
            $('.register-card').hide().fadeIn(600);
        });
    </script>
</body>
</html>