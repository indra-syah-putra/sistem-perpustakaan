<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
$user = $_SESSION['user'];
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=3">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="topbar">
    <div style="display:flex;align-items:center;gap:0.5rem;">
        <button class="hamburger" onclick="toggleSidebar()">&#9776;</button>
        <a href="<?= BASE_URL ?>/dashboard.php" class="topbar-brand">
            <i class="bi bi-book-half"></i> SIPerpus
        </a>
    </div>
    <div class="topbar-right">
        <div class="topbar-user dropdown-toggle" onclick="toggleUserMenu()">
            <i class="bi bi-person-circle"></i>
            <span class="topbar-user-name"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
            <i class="bi bi-chevron-down" style="font-size:0.7rem;color:#9ca3af;"></i>
        </div>
        <div class="user-menu" id="userMenu">
            <a href="<?= BASE_URL ?>/ganti_password.php"><i class="bi bi-key"></i> Ganti Password</a>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" class="topbar-logout"><i class="bi bi-box-arrow-right"></i> Keluar</a>
    </div>
</div>

<nav class="sidebar" id="sidebar">
    <ul class="sidebar-menu">
        <li><a href="<?= BASE_URL ?>/dashboard.php"><span class="icon"><i class="bi bi-grid-1x2"></i></span> Dashboard</a></li>

        <?php if (has_role(['admin', 'petugas'])): ?>
        <li class="menu-label">Master Data</li>
        <li><a href="<?= BASE_URL ?>/anggota/index.php"><span class="icon"><i class="bi bi-people"></i></span> Anggota</a></li>
        <li><a href="<?= BASE_URL ?>/buku/index.php"><span class="icon"><i class="bi bi-book"></i></span> Buku</a></li>
        <li><a href="<?= BASE_URL ?>/kategori/index.php"><span class="icon"><i class="bi bi-tags"></i></span> Kategori</a></li>

        <li class="menu-label">Transaksi</li>
        <li><a href="<?= BASE_URL ?>/peminjaman/index.php"><span class="icon"><i class="bi bi-arrow-left-right"></i></span> Peminjaman</a></li>
        <li><a href="<?= BASE_URL ?>/peminjaman/pinjam.php"><span class="icon"><i class="bi bi-plus-circle"></i></span> Pinjam Buku</a></li>
        <li><a href="<?= BASE_URL ?>/peminjaman/kembali.php"><span class="icon"><i class="bi bi-arrow-return-left"></i></span> Pengembalian</a></li>

        <li class="menu-label">Laporan</li>
        <li><a href="<?= BASE_URL ?>/laporan/index.php"><span class="icon"><i class="bi bi-file-text"></i></span> Laporan</a></li>
        <li><a href="<?= BASE_URL ?>/denda/index.php"><span class="icon"><i class="bi bi-cash"></i></span> Denda</a></li>

        <?php endif; ?>

        <?php if (has_role('admin')): ?>
        <li class="menu-label">Administrasi</li>
        <li><a href="<?= BASE_URL ?>/pengaturan/index.php"><span class="icon"><i class="bi bi-gear"></i></span> Pengaturan</a></li>
        <li><a href="<?= BASE_URL ?>/users/index.php"><span class="icon"><i class="bi bi-people"></i></span> Pengguna</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="main-content">

<?php if (isset($_SESSION['flash'])): ?>
<div class="modal-overlay" id="flashModal">
    <div class="modal-popup flash-popup flash-<?= $_SESSION['flash']['type'] ?>">
        <div class="flash-icon-wrap">
            <div class="flash-icon">
                <?php if ($_SESSION['flash']['type'] === 'success'): ?>
                <i class="bi bi-check-lg"></i>
                <?php elseif ($_SESSION['flash']['type'] === 'danger'): ?>
                <i class="bi bi-x"></i>
                <?php else: ?>
                <i class="bi bi-exclamation"></i>
                <?php endif; ?>
            </div>
        </div>
        <div class="flash-title">
            <?php if ($_SESSION['flash']['type'] === 'success'): ?>Berhasil
            <?php elseif ($_SESSION['flash']['type'] === 'danger'): ?>Gagal
            <?php else: ?>Perhatian
            <?php endif; ?>
        </div>
        <div class="flash-message"><?= htmlspecialchars($_SESSION['flash']['message']) ?></div>
        <button class="flash-btn" onclick="document.getElementById('flashModal').remove()">OK</button>
    </div>
</div>
<script>
setTimeout(function(){var m=document.getElementById('flashModal');if(m)m.remove();},5000);
document.getElementById('flashModal').addEventListener('click',function(e){if(e.target===this)this.remove();});
</script>
<?php unset($_SESSION['flash']); endif; ?>

<!-- Modal konfirmasi -->
<div class="modal-overlay" id="confirmModal" style="display:none;">
    <div class="modal-popup confirm-popup">
        <div class="confirm-icon-wrap">
            <div class="confirm-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        </div>
        <div class="confirm-title">Konfirmasi</div>
        <div class="modal-message" id="confirmMsg"></div>
        <div class="confirm-actions">
            <button class="confirm-btn confirm-btn-no" id="confirmNo">Batal</button>
            <button class="confirm-btn confirm-btn-yes" id="confirmYes">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<script>
var confirmCallback = null;
document.getElementById('confirmYes').addEventListener('click', function() {
    document.getElementById('confirmModal').style.display = 'none';
    if (confirmCallback) confirmCallback(true);
});
document.getElementById('confirmNo').addEventListener('click', function() {
    document.getElementById('confirmModal').style.display = 'none';
    if (confirmCallback) confirmCallback(false);
});
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
        if (confirmCallback) confirmCallback(false);
    }
});

function modConfirm(msg, cb) {
    document.getElementById('confirmMsg').textContent = msg;
    document.getElementById('confirmModal').style.display = 'flex';
    confirmCallback = cb;
}

document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;
    e.preventDefault();
    var msg = el.getAttribute('data-confirm');
    modConfirm(msg, function(ok) {
        if (ok) {
            if (el.tagName === 'A') {
                window.location.href = el.href;
            } else {
                var f = el.closest('form') || el.querySelector('form');
                if (f) f.requestSubmit();
            }
        }
    });
});

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('show');
    document.querySelector('.topbar-user').classList.toggle('show');
}
document.addEventListener('click', function(e) {
    var menu = document.getElementById('userMenu');
    var toggle = document.querySelector('.topbar-user');
    if (menu.classList.contains('show') && !toggle.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.remove('show');
        toggle.classList.remove('show');
    }
});
</script>
