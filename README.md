# SITANGKIS — Sistem Transparansi Keuangan Desa
**Kelompok 6, Kelas 4A Informatika — UNSIKA Karawang**

---

## Struktur Folder

```
sitangkis/
├── config/
│   ├── app.php          # Konstanta, helper session, fungsi utilitas
│   └── database.php     # Koneksi PDO ke MySQL
├── includes/
│   ├── header.php           # Navbar publik + HTML head
│   ├── footer.php           # Footer publik
│   ├── admin_layout.php     # Sidebar + topbar admin (open)
│   └── admin_layout_close.php  # Penutup layout admin
├── pages/               # Halaman publik (tanpa login)
│   ├── laporan.php
│   ├── grafik.php
│   └── tentang.php
├── admin/               # Halaman admin (butuh login)
│   ├── index.php        # Dashboard
│   ├── kelola_anggaran.php
│   ├── realisasi_dana.php
│   ├── laporan.php
│   ├── grafik.php
│   ├── pengaturan.php
│   └── logout.php
├── public/
│   ├── css/
│   │   ├── style.css    # Global CSS
│   │   └── admin.css    # CSS khusus admin
│   ├── js/
│   │   └── main.js      # JS global + chart helpers
│   └── uploads/         # File bukti transaksi (dibuat otomatis)
├── index.php            # Halaman beranda publik
├── login.php            # Halaman login admin
├── database.sql         # SQL schema + seed data
└── README.md
```

---

## Cara Instalasi (XAMPP / Laragon)

### 1. Salin folder ke htdocs
```
C:\xampp\htdocs\sitangkis\
```

### 2. Import database
1. Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Klik **Import** → pilih file `database.sql`
3. Klik **Go**

Atau via terminal:
```bash
mysql -u root -p < database.sql
```

### 3. Sesuaikan konfigurasi database
Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sitangkis');
define('DB_USER', 'root');      // sesuaikan
define('DB_PASS', '');          // sesuaikan
```

### 4. Sesuaikan BASE_URL (jika di subfolder)
Edit `config/app.php`:
```php
define('BASE_URL', '/sitangkis');  // jika di subfolder
// atau
define('BASE_URL', '');            // jika di root
```

### 5. Buka di browser
```
http://localhost/sitangkis/
```

---

## Akun Demo

| Role | Email | Password |
|------|-------|----------|
| Bendahara Desa | bendahara@desa.go.id | admin123 |
| Sekretaris Desa | sekdes@desa.go.id | admin123 |
| Kepala Desa | kades@desa.go.id | admin123 |

---

## Hak Akses per Role

| Fitur | Bendahara | Sekdes | Kades |
|-------|-----------|--------|-------|
| Kelola Anggaran (CRUD) | ✅ | ✅ | ❌ |
| Catat Realisasi Dana | ✅ | ✅ | ✅ |
| Validasi Realisasi | ❌ | ❌ | ✅ |
| Publikasikan Realisasi | ❌ | ❌ | ✅ |
| Lihat Laporan | ✅ | ✅ | ✅ |
| Kelola Grafik | ❌ | ✅ | ✅ |
| Pengaturan Desa | ❌ | ✅ | ✅ |

---

## Teknologi

- **Backend**: PHP 8+ (native, tanpa framework)
- **Database**: MySQL dengan PDO
- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **Chart**: Chart.js 4.4
- **Font**: Google Fonts (Plus Jakarta Sans, DM Sans)
- **Keamanan**: CSRF Token, bcrypt password, prepared statements (SQL Injection safe)

---

## Fitur Keamanan

- ✅ Password di-hash dengan `bcrypt` (`password_hash`)
- ✅ Semua query pakai **Prepared Statements** (aman dari SQL Injection)
- ✅ **CSRF Token** di semua form POST
- ✅ **Role-Based Access Control** — setiap halaman cek role
- ✅ Input di-sanitasi dengan `htmlspecialchars`
- ✅ Upload file divalidasi tipe dan ukuran
