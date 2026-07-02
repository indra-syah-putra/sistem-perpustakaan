<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_role(['admin']);
require_once __DIR__ . '/../includes/header.php';

$db = getConnection();
$users = $db->query("SELECT id_user, username, nama_lengkap, role FROM user ORDER BY id_user")->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-people"></i> Pengguna</h4>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah</a>
</div>

<div class="card-simple">
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Username</th><th>Nama Lengkap</th><th>Role</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                        <td><span class="badge bg-<?= $u['role']=='admin'?'danger':'primary' ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td>
                            <a href="edit.php?id=<?= $u['id_user'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
