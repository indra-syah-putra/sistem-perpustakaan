<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$db = getConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id_buku = (int)$_POST['id_buku'];
    $id_anggota = (int)$_POST['id_anggota'];
    if ($id_buku <= 0 || $id_anggota <= 0) {
        $error = 'Data tidak valid';
    } else {
    $stok = $db->prepare("SELECT stok FROM buku WHERE id_buku = :id");
    $stok->execute([':id' => $id_buku]);
    $cek_buku = $stok->fetch();
    if (!$cek_buku || $cek_buku['stok'] <= 0) {
        $error = 'Stok buku habis';
    } else {
        $st2 = $db->prepare("SELECT status FROM anggota WHERE id_anggota = :id");
        $st2->execute([':id' => $id_anggota]);
        $cek_anggota = $st2->fetch();
        if (!$cek_anggota || $cek_anggota['status'] != 'aktif') {
            $error = 'Anggota tidak aktif';
        } else {
            $st3 = $db->prepare("SELECT COUNT(*) AS jml FROM peminjaman WHERE id_anggota = :id AND status='dipinjam'");
            $st3->execute([':id' => $id_anggota]);
            $max_pinjam = (int)setting('max_pinjam', MAX_PINJAM);
            if ($st3->fetch()['jml'] >= $max_pinjam) {
                $error = 'Anggota sudah mencapai batas maksimal peminjaman (' . $max_pinjam . ' buku)';
            } else {
                $lama_hari = (int)($_POST['lama_hari'] ?? 0);
                $max_hari = (int)setting('max_hari_pinjam', MAX_HARI_PINJAM);
                if ($lama_hari < 1 || $lama_hari > $max_hari) {
                    $error = 'Lama peminjaman harus antara 1 sampai ' . $max_hari . ' hari';
                } else {
                    try {
                        $db->beginTransaction();
                        $stmt = $db->prepare("INSERT INTO peminjaman (id_anggota, id_buku, tgl_pinjam, tgl_jatuh_tempo, status) VALUES (:anggota, :buku, CURDATE(), DATE_ADD(CURDATE(), INTERVAL :lama DAY), 'dipinjam')");
                        $stmt->execute([':anggota' => $id_anggota, ':buku' => $id_buku, ':lama' => $lama_hari]);
                        $upd = $db->prepare("UPDATE buku SET stok = stok - 1 WHERE id_buku = :id AND stok > 0");
                        $upd->execute([':id' => $id_buku]);
                        if ($upd->rowCount() === 0) {
                            $db->rollBack();
                            $error = 'Stok buku habis';
                        } else {
                            $db->commit();
                            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Peminjaman berhasil'];
                            header('Location: pinjam.php');
                            exit;
                        }
                    } catch (Exception $e) {
                        $db->rollBack();
                        $error = 'Terjadi kesalahan. Silakan coba lagi.';
                    }
                }
            }
        }
    }
    }
}

require_once __DIR__ . '/../includes/header.php';

$daftar_kelas = $db->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY tingkatan")->fetchAll();
$anggota = $db->query("SELECT a.id_anggota, a.no_anggota, a.nisn, a.nama, k.nama_kelas AS kelas FROM anggota a LEFT JOIN kelas k ON a.id_kelas = k.id_kelas WHERE a.status='aktif' ORDER BY k.nama_kelas, a.nama")->fetchAll();
$buku = $db->query("SELECT id_buku, judul, stok FROM buku WHERE stok>0 ORDER BY judul")->fetchAll();
?>

<div class="page-header">
    <h4><i class="bi bi-plus-circle"></i> Peminjaman Baru</h4>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

<div class="card-simple">
    <div class="card-head">Form Peminjaman</div>
    <div class="card-body">
        <form method="POST" id="pinjamForm">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Kelas</label>
                <select id="filter_kelas" class="form-select">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($daftar_kelas as $k): ?>
                    <option value="<?= htmlspecialchars($k['nama_kelas']) ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Anggota <span class="text-danger">*</span></label>
                <div class="search-select-wrap">
                    <input type="text" class="form-control search-select-input" id="anggotaInput" placeholder="Ketik nama atau no. anggota..." autocomplete="off" required>
                    <span class="ss-badge" id="anggotaBadge"></span>
                    <select name="id_anggota" id="id_anggota" class="search-select-hidden" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ($anggota as $a): ?>
                        <option value="<?= $a['id_anggota'] ?>" data-kelas="<?= htmlspecialchars($a['kelas'] ?: '') ?>"><?= htmlspecialchars('['.$a['kelas'].'] '.$a['no_anggota'].' - '.$a['nama'].' (NISN: '.$a['nisn'].')') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="search-select-dropdown" id="anggotaDropdown"></div>
                </div>
            </div>
            <div class="form-group">
                <label>Buku <span class="text-danger">*</span></label>
                <div class="search-select-wrap">
                    <input type="text" class="form-control search-select-input" id="bukuInput" placeholder="Ketik judul buku..." autocomplete="off" required>
                    <span class="ss-badge" id="bukuBadge"></span>
                    <select name="id_buku" id="id_buku" class="search-select-hidden" required>
                        <option value="">-- Pilih --</option>
                        <?php foreach ($buku as $b): ?>
                        <option value="<?= $b['id_buku'] ?>"><?= htmlspecialchars($b['judul']) ?> (stok: <?= (int)$b['stok'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="search-select-dropdown" id="bukuDropdown"></div>
                </div>
            </div>
            <div class="form-group">
                <label>Lama (hari) <span class="text-danger">*</span></label>
                <input type="number" name="lama_hari" id="lama_hari" class="form-control" value="<?= (int)setting('max_hari_pinjam', MAX_HARI_PINJAM) ?>" min="1" max="<?= (int)setting('max_hari_pinjam', MAX_HARI_PINJAM) ?>" required>
                <small id="notif_lama" style="color:#dc2625;display:none;margin-top:0.25rem;"><i class="bi bi-exclamation-circle"></i> Maksimal <?= (int)setting('max_hari_pinjam', MAX_HARI_PINJAM) ?> hari!</small>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Simpan</button>
        </form>
    </div>
</div>

<script>
document.getElementById('pinjamForm').addEventListener('submit', function(e) {
    var anggota = document.getElementById('id_anggota');
    var buku = document.getElementById('id_buku');
    if (!anggota.value || !buku.value) {
        e.preventDefault();
        alert('Pilih anggota dan buku dari dropdown terlebih dahulu');
    }
});

function initSearchSelect(inputId, dropdownId, selectId, filterKelasId) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    const select = document.getElementById(selectId);
    const filterKelas = document.getElementById(filterKelasId);
    const badgeId = inputId.replace('Input', 'Badge');
    const badge = document.getElementById(badgeId);
    let items = [];
    let selectedValue = '';

    function buildItems() {
        items = [];
        const opts = select.querySelectorAll('option');
        let first = true;
        opts.forEach(function(o) {
            if (first) { first = false; return; }
            if (o.value) {
                items.push({
                    value: o.value,
                    text: o.textContent,
                    kelas: o.dataset.kelas || ''
                });
            }
        });
    }

    function matchFilter(it, filterVal) {
        if (!filterVal) return true;
        var cats = it.kelas.split(',').map(function(s) { return s.trim(); });
        return cats.indexOf(filterVal) !== -1;
    }

    function filterItems(q) {
        const k = filterKelas ? filterKelas.value : '';
        const lower = q.toLowerCase();
        return items.filter(function(it) {
            if (k && !matchFilter(it, k)) return false;
            return it.text.toLowerCase().indexOf(lower) !== -1;
        });
    }

    function render(filt) {
        dropdown.innerHTML = '';
        if (filt.length === 0) {
            dropdown.innerHTML = '<div class="ss-empty"><i class="bi bi-search" style="display:block;font-size:1.2rem;margin-bottom:0.3rem;"></i>Tidak ditemukan</div>';
            return;
        }
        filt.forEach(function(it) {
            var div = document.createElement('div');
            div.className = 'ss-item' + (it.value === selectedValue ? ' selected' : '');
            div.textContent = it.text;
            div.dataset.value = it.value;
            div.addEventListener('click', function() {
                selectItem(it.value, it.text);
            });
            dropdown.appendChild(div);
        });
    }

    function selectItem(val, txt) {
        selectedValue = val;
        input.value = txt;
        select.value = val;
        dropdown.classList.remove('show');
        input.classList.add('has-value');
        if (badge) {
            var count = items.filter(function(it) {
                if (filterKelas && filterKelas.value && !matchFilter(it, filterKelas.value)) return false;
                return true;
            }).length;
            badge.textContent = count + ' tersedia';
            badge.classList.add('show');
        }
    }

    function clearSelection() {
        selectedValue = '';
        select.value = '';
        input.classList.remove('has-value');
        if (badge) badge.classList.remove('show');
    }

    input.addEventListener('focus', function() {
        buildItems();
        var q = input.value;
        var filt = filterItems(q);
        render(filt);
        dropdown.classList.add('show');
    });

    input.addEventListener('input', function() {
        clearSelection();
        var q = this.value;
        var filt = filterItems(q);
        render(filt);
        if (q) dropdown.classList.add('show');
    });

    input.addEventListener('blur', function() {
        setTimeout(function() { dropdown.classList.remove('show'); }, 200);
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var first = dropdown.querySelector('.ss-item:not(.ss-empty)');
            if (first) first.click();
        }
        if (e.key === 'Escape') {
            dropdown.classList.remove('show');
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            var cur = dropdown.querySelector('.ss-item.highlight');
            var next = cur ? cur.nextElementSibling : dropdown.querySelector('.ss-item:not(.ss-empty)');
            if (cur) cur.classList.remove('highlight');
            if (next) next.classList.add('highlight');
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            var cur = dropdown.querySelector('.ss-item.highlight');
            var prev = cur ? cur.previousElementSibling : null;
            if (cur) cur.classList.remove('highlight');
            if (prev) prev.classList.add('highlight');
        }
    });

    if (filterKelas) {
        filterKelas.addEventListener('change', function() {
            input.value = '';
            clearSelection();
            var filt = filterItems('');
            render(filt);
            dropdown.classList.add('show');
        });
    }

    buildItems();
}

initSearchSelect('anggotaInput', 'anggotaDropdown', 'id_anggota', 'filter_kelas');
initSearchSelect('bukuInput', 'bukuDropdown', 'id_buku', null);

document.getElementById('lama_hari').addEventListener('input', function() {
    const notif = document.getElementById('notif_lama');
    notif.style.display = parseInt(this.value) > <?= (int)setting('max_hari_pinjam', MAX_HARI_PINJAM) ?> ? '' : 'none';
});
</script>

<div class="card-simple">
    <div class="card-head">Informasi</div>
    <div class="card-body" style="font-size:0.85rem;color:#6b7280;">
        <ul style="padding-left:1.2rem;line-height:1.8;">
            <li>Maksimal <?= (int)setting('max_hari_pinjam', MAX_HARI_PINJAM) ?> hari peminjaman</li>
            <li>Denda <?= rupiah((int)setting('denda_per_hari', DENDA_PER_HARI)) ?> per hari terlambat</li>
            <li>Anggota harus berstatus aktif</li>
            <li>Stok buku akan otomatis berkurang</li>
        </ul>

    </div>
</div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
