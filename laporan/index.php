<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$db = getConnection();
$tgl_awal = preg_replace('/[^0-9\-]/', '', $_GET['tgl_awal'] ?? date('Y-m-d', strtotime('-60 days')));
$tgl_akhir = preg_replace('/[^0-9\-]/', '', $_GET['tgl_akhir'] ?? date('Y-m-d'));

$stmt = $db->prepare("SELECT p.id_peminjaman, a.no_anggota, a.nisn, a.nama AS anggota, b.judul AS buku, p.tgl_pinjam, p.tgl_jatuh_tempo, p.tgl_kembali, p.status, p.denda FROM peminjaman p JOIN anggota a ON p.id_anggota = a.id_anggota JOIN buku b ON p.id_buku = b.id_buku WHERE p.tgl_pinjam BETWEEN :awal AND :akhir ORDER BY p.tgl_pinjam DESC");
$stmt->execute([':awal' => $tgl_awal, ':akhir' => $tgl_akhir]);
$laporan = $stmt->fetchAll();

$export = $_GET['export'] ?? '';

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_peminjaman_' . $tgl_awal . '_' . $tgl_akhir . '.csv"');
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // BOM UTF-8
    fputcsv($output, ['No Anggota', 'NISN', 'Nama', 'Buku', 'Tgl Pinjam', 'Tgl Jatuh Tempo', 'Tgl Kembali', 'Status', 'Denda']);
    foreach ($laporan as $l) {
        fputcsv($output, [
            $l['no_anggota'],
            $l['nisn'],
            $l['anggota'],
            $l['buku'],
            $l['tgl_pinjam'],
            $l['tgl_jatuh_tempo'],
            $l['tgl_kembali'] ?? '',
            $l['status'],
            $l['denda'] > 0 ? number_format($l['denda'], 0, ',', '.') : '0',
        ]);
    }
    fclose($output);
    exit;
}

if ($export === 'pdf') {
    require_once __DIR__ . '/../lib/fpdf.php';
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Laporan Peminjaman Perpustakaan', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, "Periode: $tgl_awal s/d $tgl_akhir", 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 8);
    $w = [20, 20, 40, 55, 23, 23, 23, 23, 20];
    $h = 7;
    $header = ['No Anggota', 'NISN', 'Nama', 'Buku', 'Tgl Pinjam', 'Jatuh Tempo', 'Tgl Kembali', 'Status', 'Denda'];
    foreach ($header as $i => $col) {
        $pdf->Cell($w[$i], $h, $col, 1, 0, 'C');
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 7);
    foreach ($laporan as $l) {
        $pdf->Cell($w[0], $h, $l['no_anggota'], 1);
        $pdf->Cell($w[1], $h, $l['nisn'], 1);
        $pdf->Cell($w[2], $h, substr($l['anggota'], 0, 25), 1);
        $pdf->Cell($w[3], $h, substr($l['buku'], 0, 35), 1);
        $pdf->Cell($w[4], $h, $l['tgl_pinjam'], 1, 0, 'C');
        $pdf->Cell($w[5], $h, $l['tgl_jatuh_tempo'], 1, 0, 'C');
        $pdf->Cell($w[6], $h, $l['tgl_kembali'] ?? '-', 1, 0, 'C');
        $status = $l['status'] == 'dipinjam' ? 'Dipinjam' : ($l['status'] == 'dikembalikan' ? 'Kembali' : 'Terlambat');
        $pdf->Cell($w[7], $h, $status, 1, 0, 'C');
        $pdf->Cell($w[8], $h, $l['denda'] > 0 ? number_format($l['denda'], 0, ',', '.') : '-', 1, 0, 'C');
        $pdf->Ln();
    }
    $pdf->Output('D', 'laporan_peminjaman_' . $tgl_awal . '_' . $tgl_akhir . '.pdf');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$total_pinjam = count($laporan);
$total_denda = array_sum(array_column($laporan, 'denda'));
$total_kembali = 0;
$total_dipinjam = 0;
foreach ($laporan as $l) {
    if ($l['status'] != 'dipinjam') $total_kembali++;
    if ($l['status'] == 'dipinjam') $total_dipinjam++;
}

$st = $db->prepare("SELECT b.judul, COUNT(*) AS jml FROM peminjaman p JOIN buku b ON p.id_buku=b.id_buku WHERE p.tgl_pinjam BETWEEN :awal AND :akhir GROUP BY b.id_buku ORDER BY jml DESC LIMIT 5");
$st->execute([':awal' => $tgl_awal, ':akhir' => $tgl_akhir]);
$populer = $st->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-file-text"></i> Laporan</h4>
</div>

<div class="card-simple mb-3">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:end;">
            <div>
                <label style="font-size:0.8rem;color:#6b7280;">Dari</label>
                <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tgl_awal) ?>">
            </div>
            <div>
                <label style="font-size:0.8rem;color:#6b7280;">Sampai</label>
                <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>">
            </div>
            <button class="btn btn-primary"><i class="bi bi-search"></i> Tampilkan</button>
            <a href="?export=csv&tgl_awal=<?= urlencode($tgl_awal) ?>&tgl_akhir=<?= urlencode($tgl_akhir) ?>" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> CSV</a>
            <a href="?export=pdf&tgl_awal=<?= urlencode($tgl_awal) ?>&tgl_akhir=<?= urlencode($tgl_akhir) ?>" class="btn btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        </form>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-item">
        <div class="stat-icon blue"><i class="bi bi-journal"></i></div>
        <div class="stat-info">
            <div class="num"><?= $total_pinjam ?></div>
            <div class="lbl">Total Transaksi</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
        <div class="stat-info">
            <div class="num"><?= $total_kembali ?></div>
            <div class="lbl">Dikembalikan</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon orange"><i class="bi bi-clock"></i></div>
        <div class="stat-info">
            <div class="num"><?= $total_dipinjam ?></div>
            <div class="lbl">Dipinjam</div>
        </div>
    </div>
    <div class="stat-item">
        <div class="stat-icon red"><i class="bi bi-cash"></i></div>
        <div class="stat-info">
            <div class="num"><?= rupiah($total_denda) ?></div>
            <div class="lbl">Total Denda</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem;">

<div class="card-simple">
    <div class="card-head">Detail Transaksi</div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Anggota</th><th>Buku</th><th>Pinjam</th><th>Status</th><th>Denda</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($laporan as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['anggota']) ?></td>
                        <td><?= htmlspecialchars($l['buku']) ?></td>
                        <td><?= tgl_indo($l['tgl_pinjam']) ?></td>
                        <td><?= status_badge($l['status']) ?></td>
                        <td><?= $l['denda']>0 ? rupiah($l['denda']) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($laporan)): ?>
                    <tr><td colspan="5" class="empty-state">Tidak ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card-simple">
    <div class="card-head">Buku Terpopuler</div>
    <div class="card-body" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Judul</th><th>Dipinjam</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($populer as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['judul']) ?></td>
                        <td><span class="badge bg-primary"><?= $b['jml'] ?>x</span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($populer)): ?>
                    <tr><td colspan="2" class="empty-state">Belum ada data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
