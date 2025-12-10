<?php
require_once 'config.php';

$error = '';
$success = '';

// Cek jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    // Validasi
    if (empty($email) || empty($password)) {
        $error = "Email dan password wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        // Cek email dan password
        $password_hash = md5($password);
        $sql = "SELECT id_pengguna, nama_lengkap, email FROM pengguna WHERE email = ? AND kata_sandi = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $password_hash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Set session
            $_SESSION['user_id'] = $user['id_pengguna'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            $_SESSION['email'] = $user['email'];

            // Redirect ke dashboard
            header("Location: index.php");
            exit();
        } else {
            $error = "Email atau password salah!";
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
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="./assets/css/user/login.css">
</head>

<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">📦</div>
            <h1><?php echo SITE_NAME; ?></h1>
            <p class="subtitle">Masuk ke Akun Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" placeholder="email@example.com" required autofocus>
            </div>

            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <input type="password" name="password" placeholder="Masukkan password Anda" required>
            </div>

            <button type="submit" class="btn">Masuk</button>
        </form>

        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>
</body>

</html>