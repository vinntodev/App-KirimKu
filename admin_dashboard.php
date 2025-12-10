<?php
require_once 'config.php';

// Hanya admin yang boleh mengakses
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$error = '';
$success = '';

// Proses aksi admin (setujui / batalkan / ubah status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pengiriman = isset($_POST['id_pengiriman']) ? (int) $_POST['id_pengiriman'] : 0;
    $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';
    $status_baru = isset($_POST['status_baru']) ? $_POST['status_baru'] : '';

    if ($id_pengiriman <= 0 || !in_array($aksi, ['setujui', 'batalkan', 'ubah_status'], true)) {
        $error = "Data tidak valid.";
    } else {
        // Pastikan pengiriman ada
        $sqlCheck = "SELECT id_pengiriman, status_pengiriman FROM pengiriman WHERE id_pengiriman = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("i", $id_pengiriman);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows === 0) {
            $error = "Pengiriman tidak ditemukan.";
        } else {
            $rowPengiriman = $resultCheck->fetch_assoc();
            $status_sekarang = $rowPengiriman['status_pengiriman'];

            // Aksi persetujuan awal (dari pending -> diproses)
            if ($aksi === 'setujui') {
                if ($status_sekarang !== 'pending') {
                    $error = "Hanya pengiriman dengan status pending yang bisa disetujui.";
                } else {
                    // Ubah status menjadi 'diproses'
                    $sqlUpdate = "UPDATE pengiriman 
                                  SET status_pengiriman = 'diproses' 
                                  WHERE id_pengiriman = ?";
                    $stmtUpdate = $conn->prepare($sqlUpdate);
                    $stmtUpdate->bind_param("i", $id_pengiriman);

                    if ($stmtUpdate->execute()) {
                        // Tambah riwayat tracking
                        $status_detail = "Pengiriman disetujui admin";
                        $lokasi_terakhir = "Gudang Utama";
                        $keterangan = "Pengiriman telah disetujui dan akan segera diproses.";

                        $sqlTrack = "INSERT INTO tracking (id_pengiriman, status_detail, lokasi_terakhir, keterangan)
                                     VALUES (?, ?, ?, ?)";
                        $stmtTrack = $conn->prepare($sqlTrack);
                        $stmtTrack->bind_param("isss", $id_pengiriman, $status_detail, $lokasi_terakhir, $keterangan);
                        $stmtTrack->execute();

                        $success = "Pengiriman berhasil disetujui.";
                    } else {
                        $error = "Gagal menyetujui pengiriman.";
                    }

                    $stmtUpdate->close();
                }
            } elseif ($aksi === 'batalkan') {
                // Ubah status menjadi 'dibatalkan' dari status apa pun selain sudah terkirim/dibatalkan
                if (in_array($status_sekarang, ['terkirim', 'dibatalkan'], true)) {
                    $error = "Pengiriman sudah selesai atau dibatalkan.";
                } else {
                    $sqlUpdate = "UPDATE pengiriman 
                                  SET status_pengiriman = 'dibatalkan' 
                                  WHERE id_pengiriman = ?";
                    $stmtUpdate = $conn->prepare($sqlUpdate);
                    $stmtUpdate->bind_param("i", $id_pengiriman);

                    if ($stmtUpdate->execute()) {
                        $status_detail = "Pengiriman dibatalkan admin";
                        $lokasi_terakhir = null;
                        $keterangan = "Pengiriman dibatalkan oleh administrator.";

                        $sqlTrack = "INSERT INTO tracking (id_pengiriman, status_detail, lokasi_terakhir, keterangan)
                                     VALUES (?, ?, ?, ?)";
                        $stmtTrack = $conn->prepare($sqlTrack);
                        $stmtTrack->bind_param("isss", $id_pengiriman, $status_detail, $lokasi_terakhir, $keterangan);
                        $stmtTrack->execute();

                        $success = "Pengiriman berhasil dibatalkan.";
                    } else {
                        $error = "Gagal membatalkan pengiriman.";
                    }

                    $stmtUpdate->close();
                }
            } elseif ($aksi === 'ubah_status') {
                // Admin mengubah status secara manual melalui dropdown
                $allowedStatus = ['pending', 'diproses', 'dikirim', 'transit', 'terkirim'];

                if (!in_array($status_baru, $allowedStatus, true)) {
                    $error = "Status baru tidak valid.";
                } elseif ($status_baru === $status_sekarang) {
                    $error = "Status baru sama dengan status sekarang.";
                } else {
                    $sqlUpdate = "UPDATE pengiriman 
                                  SET status_pengiriman = ? 
                                  WHERE id_pengiriman = ?";
                    $stmtUpdate = $conn->prepare($sqlUpdate);
                    $stmtUpdate->bind_param("si", $status_baru, $id_pengiriman);

                    if ($stmtUpdate->execute()) {
                        // Mapping pesan status untuk tracking
                        $mapDetail = [
                            'pending'  => 'Status pengiriman diubah menjadi Pending oleh admin',
                            'diproses' => 'Pengiriman sedang diproses di gudang',
                            'dikirim'  => 'Paket keluar dari gudang dan sedang dikirim',
                            'transit'  => 'Paket berada di lokasi transit',
                            'terkirim' => 'Paket telah diterima oleh penerima'
                        ];

                        $status_detail = $mapDetail[$status_baru] ?? ("Status diubah menjadi " . $status_baru);
                        $lokasi_terakhir = 'Gudang / Transit';
                        if ($status_baru === 'terkirim') {
                            $lokasi_terakhir = 'Alamat Penerima';
                        }
                        $keterangan = "Status diubah dari {$status_sekarang} menjadi {$status_baru} oleh administrator.";

                        $sqlTrack = "INSERT INTO tracking (id_pengiriman, status_detail, lokasi_terakhir, keterangan)
                                     VALUES (?, ?, ?, ?)";
                        $stmtTrack = $conn->prepare($sqlTrack);
                        $stmtTrack->bind_param("isss", $id_pengiriman, $status_detail, $lokasi_terakhir, $keterangan);
                        $stmtTrack->execute();

                        $success = "Status pengiriman berhasil diubah menjadi '{$status_baru}'.";
                    } else {
                        $error = "Gagal mengubah status pengiriman.";
                    }

                    $stmtUpdate->close();
                }
            }
        }

        $stmtCheck->close();
    }
}

// Ambil daftar pengiriman yang masih aktif (belum selesai)
$sqlPending = "SELECT p.*, 
                      u.nama_lengkap AS nama_pengguna, 
                      e.nama_ekspedisi 
               FROM pengiriman p
               JOIN pengguna u ON p.id_pengguna = u.id_pengguna
               JOIN ekspedisi e ON p.id_ekspedisi = e.id_ekspedisi
               WHERE p.status_pengiriman IN ('pending', 'diproses', 'dikirim', 'transit')
               ORDER BY p.tanggal_kirim DESC";
$resultPending = $conn->query($sqlPending);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="./assets/css/admin/dashboard.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">🛠️</div>
                <div class="logo-text">
                    <h1>Admin Dashboard</h1>
                    <p><?php echo SITE_NAME; ?> &mdash; Panel Persetujuan Pengiriman</p>
                </div>
            </div>
            <div class="user-info">
                <div>
                    Login sebagai <strong><?php echo htmlspecialchars($_SESSION['admin_nama']); ?></strong>
                </div>
                <div class="badge">
                    <?php echo htmlspecialchars($_SESSION['admin_level']); ?>
                </div>
                <div style="margin-top: 8px;">
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Pengiriman Menunggu Persetujuan</h2>
                    <p>Daftar pengiriman baru dari pengguna dengan status <strong>pending</strong>.</p>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($resultPending && $resultPending->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Resi</th>
                                <th>Pengguna</th>
                                <th>Penerima</th>
                                <th>Ekspedisi</th>
                                <th>Berat</th>
                                <th>Biaya</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $resultPending->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['nomor_resi']); ?></strong><br>
                                        <span class="small"><?php echo htmlspecialchars($row['nama_pengirim']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['nama_pengguna']); ?><br>
                                        <span class="small">ID: <?php echo (int) $row['id_pengguna']; ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['nama_penerima']); ?><br>
                                        <span class="small"><?php echo htmlspecialchars($row['telepon_penerima']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['nama_ekspedisi']); ?><br>
                                        <span class="tag-ekspedisi"><?php echo number_format($row['berat_kg'], 1); ?> kg</span>
                                    </td>
                                    <td><?php echo number_format($row['berat_kg'], 1); ?> kg</td>
                                    <td><?php echo formatRupiah($row['biaya_kirim']); ?></td>
                                    <td>
                                        <span class="status-badge status-pending">
                                            <?php echo ucfirst($row['status_pengiriman']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small">
                                            <?php echo formatTanggal($row['tanggal_kirim']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="id_pengiriman" value="<?php echo (int) $row['id_pengiriman']; ?>">
                                            <div class="action-group">
                                                <?php if ($row['status_pengiriman'] === 'pending'): ?>
                                                    <button type="submit" name="aksi" value="setujui" class="btn btn-approve">Setujui</button>
                                                <?php endif; ?>
                                                <button type="submit" name="aksi" value="batalkan" class="btn btn-cancel" onclick="return confirm('Yakin batalkan pengiriman ini?');">Batalkan</button>
                                            </div>
                                            <div style="margin-top:6px; display:flex; gap:6px; align-items:center;">
                                                <select name="status_baru" class="small" style="padding:4px 6px; border-radius:8px; border:1px solid #374151; background:#020617; color:#e5e7eb;">
                                                    <?php
                                                    $statuses = ['pending', 'diproses', 'dikirim', 'transit', 'terkirim'];
                                                    foreach ($statuses as $st):
                                                    ?>
                                                        <option value="<?php echo $st; ?>" <?php echo $row['status_pengiriman'] === $st ? 'selected' : ''; ?>>
                                                            <?php echo ucfirst($st); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="aksi" value="ubah_status" class="btn btn-approve" style="padding:4px 10px; font-size:11px;">
                                                    Ubah Status
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="small">Belum ada pengiriman dengan status pending.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>

<?php
// Tutup koneksi
$db->closeConnection();
?>