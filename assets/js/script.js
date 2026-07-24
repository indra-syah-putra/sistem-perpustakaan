document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    }

    document.querySelectorAll('form').forEach(function(form) {
        var submitted = false;
        form.addEventListener('submit', function() {
            if (submitted) return;
            submitted = true;
            var btn = this.querySelector('button[type="submit"]');
            if (btn && btn.name && !this.querySelector('input[type="hidden"][name="' + btn.name + '"]')) {
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
