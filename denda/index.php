<?php
require_once __DIR__ . '/../config/database.php';
session_start();

$db = getConnection();

// Bayar denda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bayar'])) {
    verify_csrf();
    $db->prepare("UPDATE peminjaman SET denda_lunas = 1 WHERE id_peminjaman = :id")->execute([':id' => $_POST['bayar']]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Denda berhasil dibayar'];
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// Buku terlambat (dipinjam & lewat jatuh tempo)
$terlambat = $db->query("SELECT p.id_peminjaman, a.nama AS anggota, a.no_anggota, a.nisn, a.kelas, b.judul AS buku, p.tgl_pinjam, p.tgl_jatuh_tempo, DATEDIFF(CURDATE(), p.tgl_jatuh_tempo) AS hari_telat FROM peminjaman p JOIN anggota a ON p.id_anggota = a.id_anggota JOIN buku b ON p.id_buku = b.id_buku WHERE p.status='dipinjam' AND p.tgl_jatuh_tempo < CURDATE() ORDER BY p.tgl_jatuh_tempo")->fetchAll();

// Denda belum bayar
$denda_belum = $db->query("SELECT p.id_peminjaman, a.nama AS anggota, a.no_anggota, b.judul AS buku, p.tgl_jatuh_tempo, p.tgl_kembali, p.denda, DATEDIFF(p.tgl_kembali, p.tgl_jatuh_tempo) AS hari_telat FROM peminjaman p JOIN anggota a ON p.id_anggota = a.id_anggota JOIN buku b ON p.id_buku = b.id_buku WHERE p.denda > 0 AND p.denda_lunas = 0 AND p.status IN ('terlambat','dikembalikan') ORDER BY p.tgl_kembali DESC")->fetchAll();

// Denda sudah bayar
$denda_lunas = $db->query("SELECT p.id_peminjaman, a.nama AS anggota, a.no_anggota, b.judul AS buku, p.tgl_jatuh_tempo, p.tgl_kembali, p.denda, DATEDIFF(p.tgl_kembali, p.tgl_jatuh_tempo) AS hari_telat FROM peminjaman p JOIN anggota a ON p.id_anggota = a.id_anggota JOIN buku b ON p.id_buku = b.id_buku WHERE p.denda > 0 AND p.denda_lunas = 1 ORDER BY p.tgl_kembali DESC LIMIT 20")->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-cash"></i> Manajemen Denda</h4>
</div>

<?php if (!empty($terlambat)): ?>
<div class="card-simple mb-3" style="border-left:4px solid #dc2625;">
    <div class="card-head" style="color:#dc2625;"><i class="bi bi-exclamation-triangle"></i> Buku Terlambat — <?= count($terlambat) ?> buku belum dikembalikan</div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Anggota</th><th>Kelas</th><th>Buku</th><th>Pinjam</th><th>Jatuh Tempo</th><th>Telat</th><th>Estimasi Denda</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($terlambat as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['anggota']) ?> <small style="color:#9ca3af;">(<?= htmlspecialchars($t['no_anggota']) ?>)</small></td>
                        <td><?= htmlspecialchars($t['kelas']) ?></td>
                        <td><?= htmlspecialchars($t['buku']) ?></td>
                        <td><?= tgl_indo($t['tgl_pinjam']) ?></td>
                        <td><?= tgl_indo($t['tgl_jatuh_tempo']) ?></td>
                        <td><span class="badge bg-danger"><?= $t['hari_telat'] ?> hari</span></td>
                        <td><?= rupiah($t['hari_telat'] * (int)setting('denda_per_hari', DENDA_PER_HARI)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

<div class="card-simple">
    <div class="card-head">Denda Belum Dibayar</div>
    <?php if (empty($denda_belum)): ?>
    <div class="card-body" style="text-align:center;color:#9ca3af;">Tidak ada denda</div>
    <?php else: ?>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Anggota</th><th>Buku</th><th>Telat</th><th>Denda</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($denda_belum as $d): ?>
                    <tr class="click-row" data-confirm="Tandai denda sudah dibayar?">
                        <td><?= htmlspecialchars($d['anggota']) ?></td>
                        <td><?= htmlspecialchars($d['buku']) ?></td>
                        <td><?= $d['hari_telat'] ?> hari</td>
                        <td><span class="badge bg-danger"><?= rupiah($d['denda']) ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" name="bayar" value="<?= $d['id_peminjaman'] ?>" class="btn btn-sm btn-success" data-confirm="Tandai denda sudah dibayar?"><i class="bi bi-check-lg"></i> Bayar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card-simple">
    <div class="card-head">Riwayat Pembayaran Denda</div>
    <?php if (empty($denda_lunas)): ?>
    <div class="card-body" style="text-align:center;color:#9ca3af;">Belum ada pembayaran</div>
    <?php else: ?>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Anggota</th><th>Buku</th><th>Telat</th><th>Denda</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($denda_lunas as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['anggota']) ?></td>
                        <td><?= htmlspecialchars($d['buku']) ?></td>
                        <td><?= $d['hari_telat'] ?> hari</td>
                        <td><span class="badge bg-success"><?= rupiah($d['denda']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
