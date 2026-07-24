<?php
require_once __DIR__ . '/../config/database.php';
session_start();
if (!isset($_SESSION['user'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$db = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_peminjaman'])) {
    verify_csrf();
    $id_peminjaman = (int)$_POST['id_peminjaman'];
    if ($id_peminjaman <= 0) { header('Location: kembali.php'); exit; }
    try {
        $stmt = $db->prepare("SELECT id_buku, tgl_jatuh_tempo, status FROM peminjaman WHERE id_peminjaman = :id");
        $stmt->execute([':id' => $id_peminjaman]);
        $pinjam = $stmt->fetch();
        if (!$pinjam || $pinjam['status'] != 'dipinjam') {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Peminjaman tidak ditemukan atau sudah dikembalikan'];
        } else {
            $st = $db->prepare("SELECT GREATEST(0, DATEDIFF(CURDATE(), :due)) AS telat");
            $st->execute([':due' => $pinjam['tgl_jatuh_tempo']]);
            $telat = (int)$st->fetchColumn();
            $denda = $telat * (int)setting('denda_per_hari', DENDA_PER_HARI);
            $status_baru = $denda > 0 ? 'terlambat' : 'dikembalikan';

            $db->beginTransaction();
            $db->prepare("UPDATE peminjaman SET tgl_kembali = CURDATE(), status = :status, denda = :denda WHERE id_peminjaman = :id")->execute([':status' => $status_baru, ':denda' => $denda, ':id' => $id_peminjaman]);
            $db->prepare("UPDATE buku SET stok = stok + 1 WHERE id_buku = :id")->execute([':id' => $pinjam['id_buku']]);
            $db->commit();
            if ($denda > 0) {
                $_SESSION['flash'] = ['type' => 'warning', 'message' => "Dikembalikan dengan denda " . rupiah($denda) . ". Silakan lakukan pembayaran di halaman Denda."];
                header('Location: ' . BASE_URL . '/denda/index.php');
                exit;
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Dikembalikan tepat waktu'];
            header('Location: kembali.php');
            exit;
        }
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Terjadi kesalahan. Silakan coba lagi.'];
    }
    header('Location: kembali.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$cari = $_GET['cari'] ?? '';
if ($cari) {
    $stmt = $db->prepare("SELECT p.id_peminjaman, a.nama AS anggota, a.no_anggota, b.judul AS buku, p.tgl_pinjam, p.tgl_jatuh_tempo, DATEDIFF(CURDATE(),p.tgl_jatuh_tempo) AS telat FROM peminjaman p JOIN anggota a ON p.id_anggota=a.id_anggota JOIN buku b ON p.id_buku=b.id_buku WHERE p.status='dipinjam' AND (a.nama LIKE :s OR a.no_anggota LIKE :s2 OR b.judul LIKE :s3) ORDER BY p.tgl_jatuh_tempo");
    $stmt->execute([':s'=>"%$cari%", ':s2'=>"%$cari%", ':s3'=>"%$cari%"]);
    $list = $stmt->fetchAll();
} else {
    $list = $db->query("SELECT p.id_peminjaman, a.nama AS anggota, a.no_anggota, b.judul AS buku, p.tgl_pinjam, p.tgl_jatuh_tempo, DATEDIFF(CURDATE(),p.tgl_jatuh_tempo) AS telat FROM peminjaman p JOIN anggota a ON p.id_anggota=a.id_anggota JOIN buku b ON p.id_buku=b.id_buku WHERE p.status='dipinjam' ORDER BY p.tgl_jatuh_tempo")->fetchAll();
}
?>

<div class="page-header">
    <h4><i class="bi bi-arrow-return-left"></i> Pengembalian</h4>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="card-simple mb-3">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:0.5rem;">
            <input type="text" name="cari" class="form-control" style="max-width:350px;" placeholder="Cari pengembalian..." value="<?= htmlspecialchars($cari) ?>">
            <button class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
            <?php if ($cari): ?><a href="kembali.php" class="btn btn-outline"><i class="bi bi-x-lg"></i></a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card-simple">
    <div class="card-head">
        <span>Buku Dipinjam</span>
        <span class="badge bg-warning" style="font-size:0.8rem;"><?= count($list) ?> aktif</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Anggota</th><th>Buku</th><th>Pinjam</th><th>Jatuh Tempo</th><th>Denda</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $l): 
                        $telat = max(0, $l['telat']);
                        $denda = $telat * (int)setting('denda_per_hari', DENDA_PER_HARI);
                    ?>
                    <tr class="<?= $telat>0?'bg-danger-subtle':'' ?>" style="<?= $telat>0?'background:#fef2f2;':'' ?>">
                        <td><?= htmlspecialchars($l['anggota']) ?></td>
                        <td><?= htmlspecialchars($l['buku']) ?></td>
                        <td><?= tgl_indo($l['tgl_pinjam']) ?></td>
                        <td><?= tgl_indo($l['tgl_jatuh_tempo']) ?></td>
                        <td><?= $denda>0 ? rupiah($denda) : '-' ?></td>
                        <td>
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_peminjaman" value="<?= $l['id_peminjaman'] ?>">
                                <button class="btn btn-sm btn-success" data-confirm="Kembalikan?"><i class="bi bi-check-lg"></i> Kembali</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($list)): ?>
                    <tr><td colspan="6" class="empty-state"><?= $cari ? 'Tidak ditemukan' : 'Tidak ada buku dipinjam' ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
