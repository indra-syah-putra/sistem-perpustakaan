# Sistem Informasi Perpustakaan Sekolah

Aplikasi perpustakaan berbasis web **PHP Native + MySQL** untuk sekolah. Dibangun dengan arsitektur sederhana tanpa framework, cocok untuk lingkungan belajar dan kebutuhan operasional perpustakaan sekolah.

## Fitur

- **Manajemen Anggota** — CRUD anggota dengan status aktif/nonaktif, kelas lookup, NISN
- **Manajemen Buku** — CRUD buku dengan kategori, stok, pencarian
- **Manajemen Kategori** — Inline add/edit/hapus kategori
- **Manajemen Dokumen** — CRUD metadata dokumen (judul, file, versi)
- **Peminjaman & Pengembalian** — Form dengan searchable select, hitung denda otomatis
- **Denda** — Pelacakan denda dengan tabel pembayaran terpisah
- **Pengaturan** — Konfigurasi denda per hari, maksimal pinjam, maksimal hari
- **Laporan** — Filter tanggal, ekspor CSV & PDF
- **Dashboard** — Statistik (total buku, anggota aktif, buku dipinjam, dll)
- **Manajemen User** — Admin & petugas
- **Ganti Password**
- **Rate Limiting Login** — 5 percobaan, lockout 15 menit
- **CSRF Protection**
- **UI Responsive** — Vanilla CSS + Bootstrap Icons

## Persyaratan Sistem

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10+
- Web server (Apache/Nginx) — **recommended: Laragon**
- Ekstensi PHP: `pdo_mysql`, `mbstring`

## Instalasi

### 1. Clone atau download

```bash
git clone https://github.com/indra-syah-putra/sistem-perpustakaan.git
```

Letakkan di folder `C:\laragon\www\perpustakaan\` (atau `htdocs/` jika pakai XAMPP).

### 2. Setup database

**Opsi A — phpMyAdmin (GUI):**
Jalankan Laragon (Start All), buka phpMyAdmin, lalu **Import** file `sql/database.sql` atau copy-paste isinya ke tab SQL.

**Opsi B — Command line (lebih cepat):**
```bash
mysql -u root < sql/database.sql
```
Jika pakai password: `mysql -u root -p < sql/database.sql`

### 3. Konfigurasi environment

Copy file `.env.example` jadi `.env`, lalu sesuaikan:

```bash
cp .env.example .env
```

Buka file `.env` dan perhatikan **`BASE_URL`**:

```
BASE_URL=/perpustakaan
```

Nilai `BASE_URL` tergantung cara akses aplikasi:

| Akses via | `BASE_URL` | Contoh |
|-----------|-----------|--------|
| Subfolder (`localhost/perpustakaan/`) | `/perpustakaan` | ✅ Default — langsung work |
| Virtual host / root domain (`perpustakaan.test/`) | (kosong) | Ubah jadi `BASE_URL=` |
| Subfolder lain (`localhost/sma/perpustakaan/`) | `/sma/perpustakaan` | Sesuaikan dengan path |

> **Penting:** `BASE_URL` menentukan path seluruh link CSS, JS, dan redirect. Jika salah, halaman akan tampil tanpa style (CSS 404) dan redirect menuju halaman tidak ditemukan (404).

### 4. Akses aplikasi

```
http://perpustakaan.test/
atau
http://localhost/perpustakaan/
```

**Login default:**

| Role    | Username | Password |
|---------|----------|----------|
| Admin   | admin    | password |
| Petugas | petugas  | password |

## Struktur Project

```
perpustakaan/
├── index.php               # Redirect ke login
├── login.php               # Halaman login + rate limiting
├── logout.php              # Logout
├── dashboard.php           # Dashboard statistik
├── config/
│   └── database.php        # Koneksi PDO + helper functions
├── includes/
│   ├── header.php          # Session auth, sidebar, flash modal, confirm modal
│   └── footer.php          # Penutup HTML
├── assets/
│   ├── css/style.css       # Semua styling
│   └── js/script.js        # Search enter, form loading state
├── anggota/                # CRUD Anggota
├── buku/                   # CRUD Buku
├── kategori/               # CRUD Kategori (inline)
├── peminjaman/             # Peminjaman & Pengembalian
├── laporan/                # Laporan filter tanggal + CSV/PDF
├── denda/                  # Denda management
├── pengaturan/             # Pengaturan sistem (admin only)
├── users/                  # Manajemen user (admin only)
├── lib/
│   └── fpdf.php            # Library PDF
└── sql/
    └── database.sql        # Database schema + seed data
```

## Database Objects

| Objek | Jumlah |
|-------|--------|
| Tabel | 12 (anggota, buku, buku_kategori, dokumen, kategori, kelas, log_peminjaman, pembayaran_denda, peminjaman, pengaturan, user) |
| View | 4 (v_peminjaman_aktif, v_riwayat_peminjaman, v_buku_kategori, v_statistik) |
| Function | 1 (hitung_denda) |
| Stored Procedure | 3 (pinjam_buku, kembalikan_buku, laporan_peminjaman) |
| Trigger | 2 (before_insert_peminjaman, after_update_peminjaman) |

## Role

| Role    | Akses |
|---------|-------|
| **Admin** | Semua fitur, termasuk Pengaturan & Manajemen User |
| **Petugas** | Anggota, Buku, Kategori, Peminjaman, Denda, Laporan |

## Teknologi

- **Backend:** PHP 8 native (PDO, prepared statements)
- **Database:** MySQL (InnoDB, foreign keys, trigger, stored procedure, function)
- **Frontend:** Vanilla CSS, Vanilla JS, Bootstrap Icons
- **PDF:** FPDF
- **Server:** Apache (Laragon)

## Lisensi

MIT License — silakan gunakan, modifikasi, dan distribusikan.

Dibuat oleh [Indra Syah Putra](https://github.com/indra-syah-putra).
