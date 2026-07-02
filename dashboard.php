<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$db = getConnection();

$stat = $db->query("SELECT * FROM v_statistik")->fetch();

$peminjaman_terbaru = $db->query("
    SELECT p.*, a.nama AS anggota, b.judul AS buku 
    FROM peminjaman p
    JOIN anggota a ON p.id_anggota = a.id_anggota
    JOIN buku b ON p.id_buku = b.id_buku
    ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

$stok_menipis = $db->query("
    SELECT * FROM buku WHERE stok <= 2 ORDER BY stok ASC LIMIT 5
")->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-grid-1x2"></i> Dashboard</h4>
    <span style="font-size:0.82rem;color:#6b7280;"><?= date('d M Y') ?></span>
</div>

<div class="stats-grid">
    <div class="stat-item">
        <div class="stat-icon blue"><i class="bi bi-book"></i></div>
        <div class="stat-info">
            <div class="num"><?= $stat['total_buku'] ?></div>
            <div class="lbl">Total Buku</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon green"><i class="bi bi-people"></i></div>
        <div class="stat-info">
            <div class="num"><?= $stat['total_anggota_aktif'] ?></div>
            <div class="lbl">Anggota Aktif</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon orange"><i class="bi bi-arrow-right-circle"></i></div>
        <div class="stat-info">
            <div class="num"><?= $stat['buku_dipinjam'] ?></div>
            <div class="lbl">Sedang Dipinjam</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="stat-info">
            <div class="num"><?= $stat['peminjaman_terlambat'] ?></div>
            <div class="lbl">Terlambat</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem;">

<div class="card-simple">
    <div class="card-head">
        <span>Peminjaman Terbaru</span>
        <a href="<?= BASE_URL ?>/peminjaman/index.php" class="btn btn-sm btn-outline">Lihat</a>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Anggota</th><th>Buku</th><th>Pinjam</th><th>Jatuh Tempo</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($peminjaman_terbaru as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['anggota']) ?></td>
                        <td><?= htmlspecialchars($p['buku']) ?></td>
                        <td><?= tgl_indo($p['tgl_pinjam']) ?></td>
                        <td><?= tgl_indo($p['tgl_jatuh_tempo']) ?></td>
                        <td><?= status_badge($p['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($peminjaman_terbaru)): ?>
                    <tr><td colspan="5" class="empty-state">Belum ada peminjaman</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card-simple">
    <div class="card-head">Stok Hampir Habis</div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Buku</th><th>Stok</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($stok_menipis as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['judul']) ?></td>
                        <td><span class="badge bg-danger"><?= $b['stok'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stok_menipis)): ?>
                    <tr><td colspan="2" class="empty-state">Semua stok aman</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
