<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$db = getConnection();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';

$w = []; $p = [];
if ($search) { $w[] = "(a.nama LIKE :s OR b.judul LIKE :s2 OR a.no_anggota LIKE :s3)"; $p[':s']="%$search%"; $p[':s2']="%$search%"; $p[':s3']="%$search%"; }
if ($filter_status) { $w[] = "p.status = :st"; $p[':st'] = $filter_status; }
$wc = $w ? 'WHERE '.implode(' AND ', $w) : '';

$c = $db->prepare("SELECT COUNT(*) FROM peminjaman p JOIN anggota a ON p.id_anggota=a.id_anggota JOIN buku b ON p.id_buku=b.id_buku $wc");
$c->execute($p);
$total = $c->fetchColumn();
$total_pages = ceil($total / $limit);

$sql = "SELECT p.*, a.nama AS anggota, a.no_anggota, b.judul AS buku FROM peminjaman p JOIN anggota a ON p.id_anggota=a.id_anggota JOIN buku b ON p.id_buku=b.id_buku $wc ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($p);
$peminjaman = $stmt->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-arrow-left-right"></i> Peminjaman</h4>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <a href="pinjam.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Pinjam</a>
        <a href="kembali.php" class="btn btn-info"><i class="bi bi-arrow-return-left"></i> Kembalikan</a>
    </div>
</div>

<div class="card-simple">
    <div class="card-head">
        <span>Riwayat Peminjaman</span>
        <form method="GET" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <input type="text" name="search" class="form-control" style="width:auto;min-width:180px;" placeholder="Cari peminjaman..." value="<?= htmlspecialchars($search) ?>">
            <select name="status" class="form-select" style="width:auto;" onchange="this.form.submit()">
                <option value="">Semua</option>
                <option value="dipinjam" <?= $filter_status=='dipinjam'?'selected':'' ?>>Dipinjam</option>
                <option value="dikembalikan" <?= $filter_status=='dikembalikan'?'selected':'' ?>>Dikembalikan</option>
                <option value="terlambat" <?= $filter_status=='terlambat'?'selected':'' ?>>Terlambat</option>
            </select>
            <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
            <?php if ($search || $filter_status): ?><a href="index.php" class="btn btn-sm btn-outline"><i class="bi bi-x-lg"></i></a><?php endif; ?>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Anggota</th><th>Buku</th><th>Pinjam</th><th>Jatuh Tempo</th><th>Kembali</th><th>Status</th><th>Denda</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($peminjaman as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['anggota']) ?></td>
                        <td><?= htmlspecialchars($p['buku']) ?></td>
                        <td><?= tgl_indo($p['tgl_pinjam']) ?></td>
                        <td><?= tgl_indo($p['tgl_jatuh_tempo']) ?></td>
                        <td><?= tgl_indo($p['tgl_kembali']) ?></td>
                        <td><?= status_badge($p['status']) ?></td>
                        <td><?= $p['denda']>0 ? rupiah($p['denda']) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($peminjaman)): ?>
                    <tr><td colspan="7" class="empty-state">Belum ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1rem;font-size:0.85rem;">
        <div style="color:#6b7280;">Halaman <?= $page ?> dari <?= $total_pages ?> (<?= $total ?> data)</div>
        <div style="display:flex;gap:0.25rem;">
            <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>" class="btn btn-sm btn-outline">&laquo;</a><?php endif; ?>
            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>" class="btn btn-sm <?= $i==$page?'btn-primary':'btn-outline' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>" class="btn btn-sm btn-outline">&raquo;</a><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
