<?php
require_once 'config.php';

$error = '';
$success = '';

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $nomor_telepon = sanitize($_POST['nomor_telepon']);
    $alamat = sanitize($_POST['alamat']);
    $jenis_usaha = sanitize($_POST['jenis_usaha']);
    
    // Validasi
    if (empty($nama_lengkap) || empty($email) || empty($password) || empty($nomor_telepon)) {
        $error = "Field yang bertanda * wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $password_confirm) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Cek email sudah terdaftar atau belum
        $sql_check = "SELECT id_pengguna FROM pengguna WHERE email = ?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email sudah terdaftar. Silakan gunakan email lain.";
        } else {
            // Hash password (gunakan MD5 untuk demo, production gunakan password_hash)
            $password_hash = md5($password);
            
            // Insert pengguna baru
            $sql_insert = "INSERT INTO pengguna (nama_lengkap, email, kata_sandi, nomor_telepon, alamat, jenis_usaha) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_insert);
            $stmt->bind_param("ssssss", $nama_lengkap, $email, $password_hash, $nomor_telepon, $alamat, $jenis_usaha);
            
            if ($stmt->execute()) {
                $success = "Registrasi berhasil! Silakan login.";
                // Redirect ke login setelah 2 detik
                header("refresh:2;url=login.php");
            } else {
                $error = "Terjadi kesalahan. Silakan coba lagi.";
            }
        }
        
        $stmt->close();
        $db->closeConnection();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="./assets/css/user/register.css">
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <div class="logo-icon">📦</div>
            <h1><?php echo SITE_NAME; ?></h1>
            <p class="subtitle">Daftar Akun Baru</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            ❌ <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            ✓ <?php echo $success; ?>
            <br><small>Mengalihkan ke halaman login...</small>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama_lengkap" placeholder="Nama lengkap Anda" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" placeholder="email@example.com" required>
                </div>
                
                <div class="form-group">
                    <label>Nomor Telepon <span class="required">*</span></label>
                    <input type="tel" name="nomor_telepon" placeholder="08xxxxxxxxxx" pattern="[0-9]{10,13}" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password" placeholder="Min. 6 karakter" minlength="6" required>
                </div>
                
                <div class="form-group">
                    <label>Konfirmasi Password <span class="required">*</span></label>
                    <input type="password" name="password_confirm" placeholder="Ulangi password" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Jenis Usaha</label>
                <select name="jenis_usaha">
                    <option value="">-- Pilih Jenis Usaha --</option>
                    <option value="Fashion Online">Fashion & Pakaian</option>
                    <option value="Makanan & Minuman">Makanan & Minuman</option>
                    <option value="Elektronik">Elektronik</option>
                    <option value="Buku & Alat Tulis">Buku & Alat Tulis</option>
                    <option value="Kosmetik & Kecantikan">Kosmetik & Kecantikan</option>
                    <option value="Kerajinan Tangan">Kerajinan Tangan</option>
                    <option value="Aksesoris">Aksesoris</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" placeholder="Alamat lengkap usaha Anda"></textarea>
            </div>
            
            <button type="submit" class="btn">Daftar Sekarang</button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>
</body>
</html>