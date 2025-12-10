<?php
require_once 'config.php';

$error = '';

// Cek jika sudah login sebagai admin, redirect ke dashboard admin
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Proses login admin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // Validasi
    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Cek username dan password
        $password_hash = md5($password);
        $sql = "SELECT id_admin, username, email, nama_lengkap, level FROM admin WHERE username = ? AND kata_sandi = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password_hash);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Set session admin
            $_SESSION['admin_id'] = $admin['id_admin'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_nama'] = $admin['nama_lengkap'];
            $_SESSION['admin_level'] = $admin['level'];
            
            // Redirect ke dashboard admin
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Username atau password salah!";
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
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="./assets/css/admin/login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🔐</div>
            <h1>Admin Panel</h1>
            <p class="subtitle"><?php echo SITE_NAME; ?></p>
            <span class="admin-badge">Area Administrator</span>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            ❌ <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username <span class="required">*</span></label>
                <input type="text" name="username" placeholder="Masukkan username" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            
            <button type="submit" class="btn">Masuk sebagai Admin</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Kembali ke Halaman Utama</a>
        </div>
    </div>
</body>
</html>

