<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$db = getConnection();
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

$where = '';
$params = [];
if ($search) {
    $where = "WHERE b.judul LIKE :s OR b.pengarang LIKE :s2 OR b.isbn LIKE :s3";
    $params = [':s'=>"%$search%", ':s2'=>"%$search%", ':s3'=>"%$search%"];
}

$count = $db->prepare("SELECT COUNT(*) FROM buku b $where");
$count->execute($params);
$total = $count->fetchColumn();
$total_pages = ceil($total / $limit);

$sql = "SELECT b.*, GROUP_CONCAT(k.nama_kategori SEPARATOR ', ') AS kategori FROM buku b LEFT JOIN buku_kategori bk ON b.id_buku = bk.id_buku LEFT JOIN kategori k ON bk.id_kategori = k.id_kategori $where GROUP BY b.id_buku ORDER BY b.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$buku = $stmt->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-book"></i> Buku</h4>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah</a>
</div>

<div class="card-simple">
    <div class="card-head">
        <span>Daftar Buku</span>
        <form method="GET" class="search-box">
            <input type="text" name="search" class="form-control" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Judul</th><th>Pengarang</th><th>Kategori</th><th>Stok</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($buku as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['judul']) ?></td>
                        <td><?= htmlspecialchars($b['pengarang']) ?></td>
                        <td><?= htmlspecialchars($b['kategori'] ?: '-') ?></td>
                        <td><span class="badge bg-<?= $b['stok']<=2?'danger':($b['stok']<=5?'warning':'success') ?>"><?= $b['stok'] ?></span></td>
                        <td>
                            <a href="edit.php?id=<?= $b['id_buku'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                            <a href="hapus.php?id=<?= $b['id_buku'] ?>" class="btn btn-sm btn-danger" data-confirm="Hapus buku ini?"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($buku)): ?>
                    <tr><td colspan="5" class="empty-state">Data kosong</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem 1rem;font-size:0.85rem;">
        <div style="color:#6b7280;">Halaman <?= $page ?> dari <?= $total_pages ?> (<?= $total ?> data)</div>
        <div style="display:flex;gap:0.25rem;">
            <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" class="btn btn-sm btn-outline">&laquo;</a><?php endif; ?>
            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="btn btn-sm <?= $i==$page?'btn-primary':'btn-outline' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" class="btn btn-sm btn-outline">&raquo;</a><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
