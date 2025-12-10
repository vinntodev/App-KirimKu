<?php
// tracking.php - Halaman Tracking Detail Pengiriman
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

$error = '';

// Ambil ID pengiriman dari URL
$id_pengiriman = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pengiriman <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil detail pengiriman
$sql_pengiriman = "SELECT p.*, e.nama_ekspedisi, e.kontak_cs, e.logo_ekspedisi 
                   FROM pengiriman p 
                   JOIN ekspedisi e ON p.id_ekspedisi = e.id_ekspedisi 
                   WHERE p.id_pengiriman = ? AND p.id_pengguna = ?";
$stmt = $conn->prepare($sql_pengiriman);
$stmt->bind_param("ii", $id_pengiriman, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$pengiriman = $result->fetch_assoc();

// Ambil riwayat tracking
$sql_tracking = "SELECT * FROM tracking 
                 WHERE id_pengiriman = ? 
                 ORDER BY waktu_update DESC";
$stmt = $conn->prepare($sql_tracking);
$stmt->bind_param("i", $id_pengiriman);
$stmt->execute();
$result_tracking = $stmt->get_result();

// Status color mapping
function getStatusColor($status)
{
    $colors = [
        'pending' => '#fbbf24',
        'diproses' => '#3b82f6',
        'dikirim' => '#8b5cf6',
        'transit' => '#ec4899',
        'terkirim' => '#10b981',
        'dibatalkan' => '#ef4444'
    ];
    return isset($colors[$status]) ? $colors[$status] : '#6b7280';
}

// Status icon mapping
function getStatusIcon($status)
{
    $icons = [
        'pending' => '⏳',
        'diproses' => '📋',
        'dikirim' => '📦',
        'transit' => '🚚',
        'terkirim' => '✅',
        'dibatalkan' => '❌'
    ];
    return isset($icons[$status]) ? $icons[$status] : '📦';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking <?php echo $pengiriman['nomor_resi']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="./assets/css/user/tracking.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <h1>📦 <?php echo SITE_NAME; ?> - Tracking</h1>
            </div>
            <a href="index.php" class="btn btn-secondary">← Kembali ke Dashboard</a>
        </div>

        <!-- Resi Header -->
        <div class="resi-header">
            <h2><?php echo getStatusIcon($pengiriman['status_pengiriman']); ?> Status Pengiriman</h2>
            <div class="resi-number">
                No. Resi: <?php echo htmlspecialchars($pengiriman['nomor_resi']); ?>
            </div>
            <div class="status-badge" style="background: <?php echo getStatusColor($pengiriman['status_pengiriman']); ?>">
                <?php echo ucfirst($pengiriman['status_pengiriman']); ?>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-item">
                <label>🏢 Ekspedisi</label>
                <p><?php echo htmlspecialchars($pengiriman['nama_ekspedisi']); ?></p>
            </div>
            <div class="info-item">
                <label>📞 Contact CS</label>
                <p><?php echo htmlspecialchars($pengiriman['kontak_cs']); ?></p>
            </div>
            <div class="info-item">
                <label>⚖️ Berat Paket</label>
                <p><?php echo number_format($pengiriman['berat_kg'], 1); ?> kg</p>
            </div>
            <div class="info-item">
                <label>💰 Biaya Kirim</label>
                <p><?php echo formatRupiah($pengiriman['biaya_kirim']); ?></p>
            </div>
            <div class="info-item">
                <label>📅 Tanggal Kirim</label>
                <p><?php echo formatTanggal($pengiriman['tanggal_kirim']); ?></p>
            </div>
            <div class="info-item">
                <label>🎯 Estimasi Sampai</label>
                <p><?php echo formatTanggal($pengiriman['estimasi_sampai']); ?></p>
            </div>
        </div>

        <!-- Detail Pengiriman -->
        <div class="card">
            <h3 class="section-title">📋 Detail Pengiriman</h3>

            <div class="detail-grid">
                <div class="detail-item">
                    <strong>Pengirim:</strong>
                    <span><?php echo htmlspecialchars($pengiriman['nama_pengirim']); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Penerima:</strong>
                    <span><?php echo htmlspecialchars($pengiriman['nama_penerima']); ?></span>
                </div>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <strong>Alamat Tujuan:</strong>
                    <span><?php echo htmlspecialchars($pengiriman['alamat_penerima']); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Telepon Penerima:</strong>
                    <span><?php echo htmlspecialchars($pengiriman['telepon_penerima']); ?></span>
                </div>
            </div>
        </div>

        <!-- Tracking Timeline -->
        <div class="card">
            <h3 class="section-title">🛣️ Riwayat Tracking</h3>

            <?php if ($result_tracking->num_rows > 0): ?>
                <div class="tracking-timeline">
                    <?php while ($track = $result_tracking->fetch_assoc()): ?>
                        <div class="tracking-item">
                            <div class="tracking-content">
                                <h4><?php echo htmlspecialchars($track['status_detail']); ?></h4>
                                <?php if ($track['lokasi_terakhir']): ?>
                                    <p>📍 <?php echo htmlspecialchars($track['lokasi_terakhir']); ?></p>
                                <?php endif; ?>
                                <?php if ($track['keterangan']): ?>
                                    <p><?php echo htmlspecialchars($track['keterangan']); ?></p>
                                <?php endif; ?>
                                <div class="tracking-time">
                                    🕐 <?php echo date('d M Y, H:i', strtotime($track['waktu_update'])); ?> WIB
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-tracking">
                    <p>📦 Belum ada riwayat tracking untuk pengiriman ini</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>

<?php
$db->closeConnection();
?>