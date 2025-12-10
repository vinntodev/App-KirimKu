<?php
// logout.php - Proses Logout
session_start();

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke login
header("Location: login.php");
exit();
?>