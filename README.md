## KirimKu - Aplikasi Manajemen Pengiriman Logistik

Proyek ini adalah aplikasi web sederhana untuk mengelola pengiriman barang oleh pelaku usaha (UMKM) dan dipantau oleh admin. User dapat membuat akun, login, menambah pengiriman, dan melacak statusnya. Admin memverifikasi pengiriman dan mengelola alur status kiriman.

---

## 📸 Screenshots Aplikasi

### Halaman Login User
![Login User](screenshots/login-user.jpeg)
*Halaman login untuk pengguna biasa dengan desain modern dan gradient background*

### Halaman Registrasi
![Registrasi](screenshots/register-user.jpeg)
*Form registrasi pengguna baru dengan validasi lengkap*

### Dashboard User
![Dashboard User](screenshots/dashboard-user.jpeg)
*Dashboard utama pengguna menampilkan statistik pengiriman dan daftar pengiriman terbaru*

### Form Tambah Pengiriman
![Tambah Pengiriman](screenshots/tambah-pengiriman.jpeg)
*Form untuk membuat pengiriman baru dengan pilihan ekspedisi dan perhitungan biaya otomatis*

### Halaman Tracking
![Tracking](screenshots/tracking.jpeg)
*Detail tracking pengiriman dengan timeline riwayat pergerakan paket*

### Halaman Login Admin
![Login Admin](screenshots/admin-login.jpeg)
*Halaman login khusus untuk administrator*

### Dashboard Admin
![Dashboard Admin](screenshots/admin-dashboard.jpeg)
*Panel admin untuk menyetujui, membatalkan, dan mengubah status pengiriman*

> **Catatan:** Screenshot di atas adalah contoh. Pastikan Anda menambahkan file gambar screenshot ke folder `screenshots/` di root project dengan nama file yang sesuai.

---

## 1. Fitur Utama

- **Autentikasi Pengguna**
  - Registrasi dan login pengguna (`register.php`, `login.php`).
  - Session berdasarkan `$_SESSION['user_id']`, `$_SESSION['nama']`.

- **Autentikasi Admin**
  - Login admin terpisah (`admin_login.php`) menggunakan tabel `admin`.
  - Session admin: `$_SESSION['admin_id']`, `$_SESSION['admin_level']`.
  - Dashboard admin (`admin_dashboard.php`) untuk menyetujui / membatalkan pengiriman.

- **Manajemen Pengiriman**
  - User menambah pengiriman baru (status awal: **`pending`**).
  - Admin menyetujui / membatalkan pengiriman:
    - `pending` → `diproses` (disetujui admin).
    - `pending` → `dibatalkan` (dibatalkan admin).
  - Status lain yang digunakan di sistem:
    - `diproses` → kiriman sedang disiapkan.
    - `dikirim` → kiriman keluar dari gudang.
    - `transit` → berada di gudang transit / perjalanan.
    - `terkirim` → paket telah diterima penerima.

- **Tracking Pengiriman**
  - Riwayat status tiap pengiriman disimpan di tabel `tracking`.
  - Halaman tracking (mis. `tracking.php`) dapat menampilkan timeline pergerakan paket.

---

## 2. Alur Setelah Admin Menyetujui Pengiriman

1. **User membuat pengiriman baru**
   - Sistem menyimpan data baru ke tabel `pengiriman` dengan `status_pengiriman = 'pending'`.

2. **Admin login ke panel**
   - Masuk lewat `admin_login.php` dengan akun admin.
   - Jika berhasil, diarahkan ke `admin_dashboard.php`.

3. **Admin menyetujui pengiriman**
   - Di `admin_dashboard.php`, admin melihat daftar pengiriman dengan status `pending`.
   - Saat admin klik **Setujui**:
     - Tabel `pengiriman`:
       - `status_pengiriman` diubah dari `pending` menjadi **`diproses`**.
     - Tabel `tracking`:
       - Ditambah entri baru, misalnya:
         - `status_detail`: *"Pengiriman disetujui admin"*
         - `lokasi_terakhir`: *"Gudang Utama"*
         - `keterangan`: *"Pengiriman telah disetujui dan akan segera diproses."*

4. **Langkah selanjutnya setelah disetujui**
   - Dari sisi operasional (bisa dibuat halaman admin lanjutan atau di script lain):
     - Saat barang sudah siap dan keluar dari gudang:
       - Ubah `status_pengiriman` menjadi **`dikirim`** dan tambahkan catatan ke tabel `tracking`.
     - Saat barang sampai di gudang / kota transit:
       - Ubah status menjadi **`transit`**, tambah catatan di `tracking`.
     - Saat barang telah diterima oleh penerima:
       - Ubah status menjadi **`terkirim`**, dan simpan entri terakhir di `tracking`.
   - User dan admin bisa melihat semua perubahan status tersebut melalui halaman tracking.

5. **Apa yang bisa Anda kembangkan lagi**
   - Tambah halaman admin untuk mengubah status dari `diproses` → `dikirim` → `transit` → `terkirim`.
   - Tambah filter dan pencarian di dashboard admin berdasarkan status / ekspedisi / tanggal.
   - Kirim notifikasi email/WhatsApp saat status berubah (opsional).

---

## 3. Persiapan Lingkungan

- **Kebutuhan:**
  - PHP 7.4+ (Laragon/XAMPP/WAMP).
  - MySQL/MariaDB.
  - Web server lokal (contoh: `http://localhost/logistik/`).

- **Struktur project (sederhana):**
  - `config.php` – konfigurasi database & helper.
  - `login.php` – login user.
  - `register.php` – registrasi user.
  - `index.php` – dashboard utama user.
  - `admin_login.php` – login admin.
  - `admin_dashboard.php` – dashboard admin (persetujuan pengiriman).
  - `tracking.php` – detail tracking pengiriman.
  - `tambah_pengiriman.php` – form membuat pengiriman baru.
  - `logout.php` – logout user/admin.
  - `db_logistik.sql` – file SQL skema & sample data.
  - `assets/` – folder untuk CSS, JavaScript, dan gambar (logo ekspedisi).
  - `screenshots/` – folder untuk menyimpan screenshot aplikasi (opsional).

---

## 4. Instalasi & Setup Database

1. **Clone / copy project**
   - Letakkan folder di direktori web server Anda, misalnya:
     - Laragon: `D:\laragon\www\logistik`

2. **Import database**
   - Buka phpMyAdmin atau client MySQL lain.
   - Jalankan file `db_logistik.sql`:
     - Script akan:
       - Membuat database `db_logistik` (jika belum ada).
       - Membuat semua tabel: `pengguna`, `ekspedisi`, `pengiriman`, `tracking`, `admin`.
       - Mengisi data contoh (ekspedisi, user, pengiriman, tracking, admin).

3. **Cek konfigurasi `config.php`**
   - Pastikan kredensial sesuai:

```startLine:endLine:config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_logistik');
```

   - Atur `BASE_URL` menyesuaikan environment Anda jika perlu.

---

## 5. Akun Default

- **User biasa**
  - Email: `admin@kirimku.com`
  - Password: `admin123`  
  - Tersimpan di tabel `pengguna` (hanya untuk contoh).

- **Admin**
  - Username: `admin`
  - Email: `admin@kirimku.com`
  - Password: `admin123`  
  - Tersimpan di tabel `admin` dan digunakan untuk login di `admin_login.php`.

> Catatan: Password menggunakan MD5 hanya untuk keperluan demo. Untuk produksi, gunakan `password_hash()` dan `password_verify()`.

---

## 6. Cara Menjalankan Aplikasi

1. Start web server (Apache/Nginx) dan MySQL di Laragon/XAMPP.
2. Pastikan database `db_logistik` sudah terisi dari `db_logistik.sql`.
3. Akses aplikasi di browser:
   - User: `http://localhost/logistik/` → login/registrasi.
   - Admin: `http://localhost/logistik/admin_login.php`.
4. sebagai admin:
   - Login dengan username `admin`, password `admin123`.
   - Masuk ke `admin_dashboard.php` untuk melihat dan menyetujui pengiriman dengan status `pending`.

---

## 7. Kontribusi & Pengembangan Lanjutan

- Ganti MD5 menjadi `password_hash()` untuk keamanan yang lebih baik.
- Tambah role/otorisasi lebih granular (misal: CS, kurir, admin gudang).
- Integrasi API ekspedisi nyata (JNE, J&T, dll) jika dibutuhkan.
- Tambah export laporan pengiriman (CSV/Excel).







