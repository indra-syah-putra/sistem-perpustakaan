# Sistem Informasi Perpustakaan Sekolah

Aplikasi perpustakaan berbasis web **PHP Native + MySQL** untuk sekolah. Dibangun dengan arsitektur sederhana tanpa framework, cocok untuk lingkungan belajar dan kebutuhan operasional perpustakaan sekolah.

## Fitur

- **Manajemen Anggota** — CRUD anggota dengan status aktif/nonaktif, kelas, NISN
- **Manajemen Buku** — CRUD buku dengan kategori, stok, pencarian
- **Manajemen Kategori** — Inline add/edit/hapus kategori
- **Peminjaman & Pengembalian** — Form dengan searchable select, hitung denda otomatis
- **Denda** — Pelacakan denda (terlambat / belum bayar / riwayat lunas)
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

Jalankan Laragon (Start All), buka phpMyAdmin, lalu **Import** file:

```
sql/database.sql
```

Atau copy-paste isinya ke tab SQL.

### 3. Konfigurasi environment

Ubah file `.env` sesuai environment kamu:

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=perpustakaan
DB_USER=root
DB_PASS=
BASE_URL=
APP_NAME=SIPerpus
```

> `BASE_URL` kosong jika di root domain (`http://perpustakaan.test/`).
> Isi `/perpustakaan` jika dalam subfolder (`http://localhost/perpustakaan/`).

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

## Role

| Role    | Akses |
|---------|-------|
| **Admin** | Semua fitur, termasuk Pengaturan & Manajemen User |
| **Petugas** | Anggota, Buku, Kategori, Peminjaman, Denda, Laporan |

## Teknologi

- **Backend:** PHP 8 native (PDO, prepared statements)
- **Database:** MySQL
- **Frontend:** Vanilla CSS, Vanilla JS, Bootstrap Icons
- **PDF:** FPDF
- **Server:** Apache (Laragon)

## Lisensi

MIT License — silakan gunakan, modifikasi, dan distribusikan.

Dibuat oleh [Indra Syah Putra](https://github.com/indra-syah-putra).
