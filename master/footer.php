</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        
        $(document).ready(function () {
            // 🚀 1. TOGGLE DEL MENU (REFORZADO)
            $(document).on('click', '#btnToggle', function(e) {
                e.preventDefault();
                if ($(window).width() > 768) {
                    $('#sidebar').toggleClass('collapsed');
                    $('#content').toggleClass('expanded');
                } else {
                    $('#sidebar').toggleClass('mobile-show');
                }
            });

            // 🚀 2. CERRAR SIDEBAR EN MÓVIL AL TOCAR FUERA
            $(document).on('mouseup', function(e) {
                var container = $("#sidebar");
                if (!container.is(e.target) && container.has(e.target).length === 0 && $(window).width() <= 768) {
                    container.removeClass('mobile-show');
                }
            });

            // 🚀 3. INICIALIZAR SELECT2 SI EXISTE
            if ($('.select2').length > 0) {
                $('.select2').select2({ theme: 'bootstrap-5' });
            }
        });
    </script>
</body>
</html>