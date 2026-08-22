# 🎓 EduScanQR - Sistem Absensi Sekolah Berbasis QR Code

Aplikasi absensi sekolah modern dan responsif berbasis web yang memungkinkan Guru membuat sesi absensi berbatas waktu menggunakan QR Code, serta Siswa melakukan pemindaian (scan) presensi secara langsung melalui kamera smartphone atau laptop.

---

## 🚀 Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+), **Tailwind CSS** (via CDN), **Lucide Icons**
- **Backend:** PHP Native (PDO MySQL, Clean Session Auth)
- **Database:** MySQL / MariaDB
- **Pustaka QR:** `qrcodejs` (Generasi QR Code) & `html5-qrcode` (Pemindai Kamera HTML5)

---

## 📌 Fitur Utama

1. **Autentikasi Berbasis Role (Guru & Siswa)**
   - Login terpisah menggunakan NIP/NIS atau Email.
   - Enkripsi password menggunakan `password_hash()` & `password_verify()`.
   - Tombol *Quick Fill Demo Account* untuk pengujian cepat.

2. **Dashboard Guru & Generator QR Code**
   - Ringkasan statistik kehadiran siswa hari ini.
   - Form pembuatan sesi absensi baru (Pilihan Mata Pelajaran, Target Kelas, & Durasi Sesi 5-60 menit).
   - **Layar Proyeksi QR Code Proyektor/TV Kelas:**
     - Menampilkan QR Code unik dengan countdown timer sisa waktu.
     - Kode token manual sebagai fallback backup.
     - **Realtime Polling Feed:** Daftar nama siswa yang sudah scan diperbarui secara langsung tanpa refresh halaman.

3. **Dashboard Siswa & QR Code Scanner**
   - **Pemindai Kamera Web/Mobile:** Memindai QR Code di layar guru secara langsung.
   - **Input Token Manual:** Fallback jika kamera perangkat siswa tidak aktif atau bermasalah.
   - **Proteksi Ganda:** Mencegah double scan pada sesi yang sama dan menolak QR yang sudah kedaluwarsa.
   - **Modal Feedback:** Menampilkan status berhasil (Nama, Waktu, Mapel) atau pesan kesalahan.
   - **Riwayat Presensi:** Catatan lengkap hasil absensi siswa.

---

## 🗝️ Akun Demo (Default Credentials)

| Role | Identitas (NIP / NIS) | Email | Password |
| :--- | :--- | :--- | :--- |
| **Guru** | `198501012010011001` | `guru@sekolah.sch.id` | `guru123` |
| **Siswa 1** | `22231001` | `budi@sekolah.sch.id` | `siswa123` |
| **Siswa 2** | `22231002` | `anisa@sekolah.sch.id` | `siswa123` |

---

## 🛠️ Cara Penggunaan & Pengaturan Database

### 1. Persiapan Database MySQL
1. Buka **phpMyAdmin** atau MySQL Client Anda.
2. Buat database baru bernama `absensi_sekolah` atau impor langsung file `schema.sql`:
   ```bash
   mysql -u root -p < schema.sql
   ```
   *(Sistem juga dilengkapi dengan **Auto-Initialization** yang otomatis membuat tabel & data demo jika database kosong).*

### 2. Konfigurasi Database (Jika Diperlukan)
Sesuaikan kredensial MySQL di file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Isikan password MySQL Anda jika ada
define('DB_NAME', 'absensi_sekolah');
```

### 3. Menjalankan Server Lokal

#### Opsi A: Menggunakan XAMPP / Laragon
Salin folder proyek ini ke dalam directory `htdocs` (XAMPP) atau `www` (Laragon), lalu buka browser dan akses:
```
http://localhost/Project%20web%20absen%20sekolah/
```

#### Opsi B: Menggunakan Built-in PHP Server
Buka terminal / command prompt di folder proyek ini dan jalankan:
```bash
php -S localhost:8000
```
Buka browser dan akses:
```
http://localhost:8000
```

---

## 📁 Struktur Direktori Proyek

```
Project web absen sekolah/
├── api/
│   ├── generate_qr.php        # API endpoint membuat sesi QR baru
│   ├── get_session_stats.php  # API polling statistik siswa scan (Guru)
│   └── scan.php               # API verifikasi scan QR (Siswa)
├── assets/
│   └── css/
│       └── custom.css         # Styling Glassmorphism & Animasi Scanner
├── config/
│   └── database.php           # Koneksi PDO MySQL & Auto Init Database
├── includes/
│   ├── auth.php               # Logika Autentikasi & Session Guard
│   ├── header.php             # Navbar & Header HTML (Tailwind CDN)
│   └── footer.php             # Footer & Script Global
├── index.php                  # Main Router (Redirect berdasarkan role)
├── login.php                  # Halaman Login Guru & Siswa
├── logout.php                 # Handler Logout
├── guru_dashboard.php         # Control Panel Guru
├── generate_qr_page.php       # Layar Proyeksi QR Code Proyektor Kelas
├── siswa_dashboard.php        # Scanner Kamera QR & Dashboard Siswa
├── schema.sql                 # SQL Schema & Seed Data
└── README.md                  # Dokumentasi Proyek
```
