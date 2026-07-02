document.addEventListener('DOMContentLoaded', function() {
    // Input search otomatis submit
    var searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    }

    // Loading state pada tombol submit form
    document.querySelectorAll('form').forEach(function(form) {
        var submitted = false;
        form.addEventListener('submit', function() {
            if (submitted) return;
            submitted = true;
            var btn = this.querySelector('button[type="submit"]');
            if (btn) {
                var h = document.createElement('input');
                h.type = 'hidden';
                h.name = btn.name;
                h.value = btn.value;
                this.appendChild(h);
                btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Proses...';
            }
        });
    });
});
