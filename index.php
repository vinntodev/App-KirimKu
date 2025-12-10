<?php
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Ambil data statistik
$user_id = $_SESSION['user_id'];

// Total pengiriman
$sql_total = "SELECT COUNT(*) as total FROM pengiriman WHERE id_pengguna = ?";
$stmt = $conn->prepare($sql_total);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_pengiriman = $stmt->get_result()->fetch_assoc()['total'];

// Total terkirim
$sql_terkirim = "SELECT COUNT(*) as total FROM pengiriman WHERE id_pengguna = ? AND status_pengiriman = 'terkirim'";
$stmt = $conn->prepare($sql_terkirim);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_terkirim = $stmt->get_result()->fetch_assoc()['total'];

// Total transit
$sql_transit = "SELECT COUNT(*) as total FROM pengiriman WHERE id_pengguna = ? AND status_pengiriman IN ('transit', 'dikirim')";
$stmt = $conn->prepare($sql_transit);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_transit = $stmt->get_result()->fetch_assoc()['total'];

// Total pending
$sql_pending = "SELECT COUNT(*) as total FROM pengiriman WHERE id_pengguna = ? AND status_pengiriman IN ('pending', 'diproses')";
$stmt = $conn->prepare($sql_pending);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_pending = $stmt->get_result()->fetch_assoc()['total'];

// Ambil data pengiriman terbaru
$sql_pengiriman = "SELECT p.*, e.nama_ekspedisi 
                   FROM pengiriman p 
                   JOIN ekspedisi e ON p.id_ekspedisi = e.id_ekspedisi 
                   WHERE p.id_pengguna = ? 
                   ORDER BY p.tanggal_kirim DESC 
                   LIMIT 10";
$stmt = $conn->prepare($sql_pengiriman);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_pengiriman = $stmt->get_result();

// Ambil data ekspedisi
$sql_ekspedisi = "SELECT * FROM ekspedisi ORDER BY rating DESC";
$result_ekspedisi = $conn->query($sql_ekspedisi);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="./assets/css/user/dashboard.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <div class="logo-icon">📦</div>
                <div>
                    <h1><?php echo SITE_NAME; ?></h1>
                    <p style="color: #666; font-size: 14px;">Platform Logistik Pintar untuk UMKM</p>
                </div>
            </div>
            <div class="user-info">
                <span style="color: #666;">Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong></span>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="dashboard">
            <div class="card stat-card">
                <div class="stat-icon blue">📊</div>
                <div class="stat-info">
                    <h3>Total Pengiriman</h3>
                    <p><?php echo $total_pengiriman; ?></p>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon green">✓</div>
                <div class="stat-info">
                    <h3>Terkirim</h3>
                    <p><?php echo $total_terkirim; ?></p>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon yellow">🚚</div>
                <div class="stat-info">
                    <h3>Dalam Transit</h3>
                    <p><?php echo $total_transit; ?></p>
                </div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon purple">⏳</div>
                <div class="stat-info">
                    <h3>Pending</h3>
                    <p><?php echo $total_pending; ?></p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Tabel Pengiriman -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="section-title" style="margin-bottom: 0;">Daftar Pengiriman Terbaru</h2>
                    <a href="tambah_pengiriman.php" class="btn btn-primary">+ Buat Pengiriman</a>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Resi</th>
                                <th>Penerima</th>
                                <th>Tujuan</th>
                                <th>Ekspedisi</th>
                                <th>Berat</th>
                                <th>Biaya</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_pengiriman->num_rows > 0): ?>
                                <?php while($row = $result_pengiriman->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['nomor_resi']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nama_penerima']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($row['alamat_penerima'], 0, 30)) . '...'; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_ekspedisi']); ?></td>
                                    <td><?php echo number_format($row['berat_kg'], 1); ?> kg</td>
                                    <td><?php echo formatRupiah($row['biaya_kirim']); ?></td>
                                    <td><span class="status <?php echo $row['status_pengiriman']; ?>"><?php echo ucfirst($row['status_pengiriman']); ?></span></td>
                                    <td>
                                        <a href="tracking.php?id=<?php echo $row['id_pengiriman']; ?>" class="btn btn-primary btn-sm">Track</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">📦</div>
                                            <h3>Belum ada pengiriman</h3>
                                            <p>Mulai buat pengiriman pertama Anda dengan klik tombol "Buat Pengiriman" di atas</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Daftar Ekspedisi -->
            <div class="card">
                <h2 class="section-title">Ekspedisi Partner</h2>
                
                <?php 
                if ($result_ekspedisi->num_rows > 0):
                    while($eks = $result_ekspedisi->fetch_assoc()): 
                ?>
                <div class="expedisi-item">
                    <div class="expedisi-info">
                        <h4><?php echo htmlspecialchars($eks['nama_ekspedisi']); ?></h4>
                        <p><strong><?php echo formatRupiah($eks['tarif_per_kg']); ?>/kg</strong></p>
                        <p style="font-size: 12px;">CS: <?php echo htmlspecialchars($eks['kontak_cs']); ?></p>
                        <div class="rating">
                            <?php 
                            $rating = floor($eks['rating']);
                            $decimal = $eks['rating'] - $rating;
                            
                            for($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '★';
                                } elseif ($i == $rating + 1 && $decimal >= 0.5) {
                                    echo '★';
                                } else {
                                    echo '☆';
                                }
                            }
                            echo ' ' . number_format($eks['rating'], 1);
                            ?>
                        </div>
                    </div>
                    <a href="tambah_pengiriman.php?ekspedisi=<?php echo $eks['id_ekspedisi']; ?>" class="btn btn-success">Pilih</a>
                </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="empty-state">
                        <p>Tidak ada ekspedisi tersedia</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$db->closeConnection();
?>