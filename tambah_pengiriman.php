<?php
// tambah_pengiriman.php - Form Tambah Pengiriman Baru
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
$success = '';

// Ambil data pengguna
$sql_user = "SELECT * FROM pengguna WHERE id_pengguna = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Ambil semua ekspedisi
$sql_ekspedisi = "SELECT * FROM ekspedisi ORDER BY nama_ekspedisi ASC";
$result_ekspedisi = $conn->query($sql_ekspedisi);

// Proses form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_ekspedisi = sanitize($_POST['id_ekspedisi']);
    $nama_penerima = sanitize($_POST['nama_penerima']);
    $telepon_penerima = sanitize($_POST['telepon_penerima']);
    $alamat_penerima = sanitize($_POST['alamat_penerima']);
    $berat_kg = floatval($_POST['berat_kg']);

    // Validasi
    if (empty($id_ekspedisi) || empty($nama_penerima) || empty($telepon_penerima) || empty($alamat_penerima) || $berat_kg <= 0) {
        $error = "Semua field harus diisi dengan benar!";
    } else {
        // Ambil tarif ekspedisi
        $sql_tarif = "SELECT tarif_per_kg FROM ekspedisi WHERE id_ekspedisi = ?";
        $stmt = $conn->prepare($sql_tarif);
        $stmt->bind_param("i", $id_ekspedisi);
        $stmt->execute();
        $tarif_result = $stmt->get_result();

        if ($tarif_result->num_rows > 0) {
            $tarif = $tarif_result->fetch_assoc()['tarif_per_kg'];
            $biaya_kirim = $berat_kg * $tarif;

            // Generate nomor resi unik
            $nomor_resi = strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));

            // Estimasi sampai (3-5 hari dari sekarang)
            $estimasi_hari = rand(3, 5);
            $estimasi_sampai = date('Y-m-d', strtotime("+$estimasi_hari days"));

            // Insert pengiriman
            $sql_insert = "INSERT INTO pengiriman 
                          (id_pengguna, id_ekspedisi, nomor_resi, nama_pengirim, nama_penerima, 
                           alamat_penerima, telepon_penerima, berat_kg, biaya_kirim, 
                           status_pengiriman, estimasi_sampai) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";

            $stmt = $conn->prepare($sql_insert);
            $stmt->bind_param(
                "iisssssdds",
                $user_id,
                $id_ekspedisi,
                $nomor_resi,
                $user_data['nama_lengkap'],
                $nama_penerima,
                $alamat_penerima,
                $telepon_penerima,
                $berat_kg,
                $biaya_kirim,
                $estimasi_sampai
            );

            if ($stmt->execute()) {
                $id_pengiriman = $stmt->insert_id;

                // Insert tracking pertama
                $sql_tracking = "INSERT INTO tracking (id_pengiriman, status_detail, lokasi_terakhir, keterangan) 
                                VALUES (?, 'Pengiriman dibuat', 'Menunggu pickup', 'Paket berhasil dibuat dan menunggu pickup dari pengirim')";
                $stmt_track = $conn->prepare($sql_tracking);
                $stmt_track->bind_param("i", $id_pengiriman);
                $stmt_track->execute();

                $success = "Pengiriman berhasil dibuat dengan nomor resi: <strong>$nomor_resi</strong>";

                // Redirect setelah 2 detik
                header("refresh:2;url=tracking.php?id=$id_pengiriman");
            } else {
                $error = "Gagal membuat pengiriman. Silakan coba lagi.";
            }
        } else {
            $error = "Ekspedisi tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Pengiriman - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="./assets/css/user/add.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <h1>📦 <?php echo SITE_NAME; ?> - Buat Pengiriman</h1>
            </div>
            <a href="index.php" class="btn btn-secondary">← Kembali</a>
        </div>

        <div class="card">
            <h2>Form Pengiriman Baru</h2>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✓ <?php echo $success; ?>
                    <br><small>Anda akan dialihkan ke halaman tracking...</small>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="formPengiriman">
                <div class="form-group">
                    <label>Pilih Ekspedisi *</label>
                    <div class="expedisi-select">
                        <?php
                        $result_ekspedisi->data_seek(0); // Reset pointer
                        while ($eks = $result_ekspedisi->fetch_assoc()):
                        ?>
                            <div class="expedisi-option">
                                <input
                                    type="radio"
                                    name="id_ekspedisi"
                                    id="eks_<?php echo $eks['id_ekspedisi']; ?>"
                                    value="<?php echo $eks['id_ekspedisi']; ?>"
                                    data-tarif="<?php echo $eks['tarif_per_kg']; ?>"
                                    required>
                                <label for="eks_<?php echo $eks['id_ekspedisi']; ?>" class="expedisi-label">
                                    <?php
                                    // Tampilkan logo ekspedisi bila tersedia
                                    if (!empty($eks['logo_ekspedisi'])):
                                        // Jika di database tersimpan tanpa ekstensi yang sesuai, pastikan file di folder assets/img/
                                        $logoPath = 'assets/img/' . $eks['logo_ekspedisi'];
                                    ?>
                                        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($eks['nama_ekspedisi']); ?>" style="max-width:80px; max-height:40px; object-fit:contain; margin-bottom:8px;">
                                    <?php endif; ?>
                                    <h4><?php echo htmlspecialchars($eks['nama_ekspedisi']); ?></h4>
                                    <p><strong><?php echo formatRupiah($eks['tarif_per_kg']); ?>/kg</strong></p>
                                    <p>⭐ <?php echo number_format($eks['rating'], 1); ?></p>
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nama Penerima *</label>
                    <input type="text" name="nama_penerima" placeholder="Nama lengkap penerima" required>
                </div>

                <div class="form-group">
                    <label>Nomor Telepon Penerima *</label>
                    <input type="tel" name="telepon_penerima" placeholder="08xxxxxxxxxx" pattern="[0-9]{10,13}" required>
                </div>

                <div class="form-group">
                    <label>Alamat Lengkap Penerima *</label>
                    <textarea name="alamat_penerima" placeholder="Jl. Nama Jalan No. XX, Kecamatan, Kota, Provinsi, Kode Pos" required></textarea>
                </div>

                <div class="form-group">
                    <label>Berat Paket (kg) *</label>
                    <input type="number" name="berat_kg" id="berat_kg" step="0.1" min="0.1" placeholder="0.0" required>
                </div>

                <div class="biaya-preview" id="biayaPreview" style="display: none;">
                    <h3>Estimasi Biaya Pengiriman:</h3>
                    <div class="biaya-amount" id="biayaAmount">Rp 0</div>
                    <p style="color: #666; margin-top: 5px;">
                        <span id="detailBerat">0</span> kg × <span id="detailTarif">Rp 0</span>/kg
                    </p>
                </div>

                <button type="submit" class="btn btn-primary">🚀 Buat Pengiriman</button>
            </form>
        </div>
    </div>

    <script>
        // Hitung biaya otomatis
        const beratInput = document.getElementById('berat_kg');
        const ekspedisiInputs = document.querySelectorAll('input[name="id_ekspedisi"]');
        const biayaPreview = document.getElementById('biayaPreview');
        const biayaAmount = document.getElementById('biayaAmount');
        const detailBerat = document.getElementById('detailBerat');
        const detailTarif = document.getElementById('detailTarif');

        function hitungBiaya() {
            const selectedEkspedisi = document.querySelector('input[name="id_ekspedisi"]:checked');
            const berat = parseFloat(beratInput.value) || 0;

            if (selectedEkspedisi && berat > 0) {
                const tarif = parseFloat(selectedEkspedisi.dataset.tarif);
                const total = berat * tarif;

                biayaPreview.style.display = 'block';
                biayaAmount.textContent = formatRupiah(total);
                detailBerat.textContent = berat.toFixed(1);
                detailTarif.textContent = formatRupiah(tarif);
            } else {
                biayaPreview.style.display = 'none';
            }
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        beratInput.addEventListener('input', hitungBiaya);
        ekspedisiInputs.forEach(input => {
            input.addEventListener('change', hitungBiaya);
        });
    </script>
</body>

</html>

<?php
$db->closeConnection();
?>