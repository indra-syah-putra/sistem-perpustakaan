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
    $where = "WHERE a.nama LIKE :search OR a.no_anggota LIKE :search2 OR a.email LIKE :search3";
    $params = [':search' => "%$search%", ':search2' => "%$search%", ':search3' => "%$search%"];
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM anggota a $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$total_pages = ceil($total / $limit);

$stmt = $db->prepare("SELECT a.*, k.nama_kelas FROM anggota a LEFT JOIN kelas k ON a.id_kelas = k.id_kelas $where ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$anggota = $stmt->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-people"></i> Anggota</h4>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah</a>
</div>

<div class="card-simple">
    <div class="card-head">
        <span>Daftar Anggota</span>
        <form method="GET" class="search-box">
            <input type="text" name="search" class="form-control" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>No</th><th>No Anggota</th><th>NISN</th><th>Nama</th><th>Kelas</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php $no = $offset + 1; foreach ($anggota as $a): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($a['no_anggota']) ?></td>
                        <td><?= htmlspecialchars($a['nisn'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($a['nama']) ?></td>
                        <td><?= htmlspecialchars($a['nama_kelas'] ?: '-') ?></td>
                        <td><?= status_badge($a['status']) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $a['id_anggota'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                            <a href="hapus.php?id=<?= $a['id_anggota'] ?>" class="btn btn-sm btn-danger" data-confirm="Hapus anggota ini?"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($anggota)): ?>
                    <tr><td colspan="7" class="empty-state">Data kosong</td></tr>
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
