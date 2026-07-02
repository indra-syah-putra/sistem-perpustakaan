<?php
require_once __DIR__ . '/../config/database.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ' . (empty(BASE_URL) ? '' : BASE_URL) . '/login.php');
    exit;
}

$db = getConnection();
$id = $_GET['id'] ?? 0;

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_buku = :id AND status = 'dipinjam'");
    $stmt->execute([':id' => $id]);
    $aktif = $stmt->fetchColumn();
    
    if ($aktif > 0) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Buku tidak bisa dihapus karena sedang dipinjam'];
    } else {
        $stmt = $db->prepare("DELETE FROM buku WHERE id_buku = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Buku berhasil dihapus'];
    }
} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus: ' . $e->getMessage()];
}

header('Location: index.php');
exit;
