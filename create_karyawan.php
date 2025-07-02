<?php
// create_karyawan.php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $jabatan = trim($_POST['jabatan']);
    $email = trim($_POST['email']);
    $telepon = trim($_POST['telepon']);
    $alamat = trim($_POST['alamat']);
    $tanggal_masuk = trim($_POST['tanggal_masuk']);
    $gaji = trim($_POST['gaji']);

    // Validasi sederhana
    if (empty($nama) || empty($jabatan) || empty($email) || empty($tanggal_masuk) || empty($gaji)) {
        $message = '<div class="alert-message alert-error">Nama, Jabatan, Email, Tanggal Masuk, dan Gaji harus diisi!</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert-message alert-error">Format email tidak valid!</div>';
    } else {
        // Cek apakah email sudah terdaftar
        $sql_check_email = "SELECT id FROM karyawan WHERE email = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $result_check_email = $stmt_check_email->get_result();

        if ($result_check_email->num_rows > 0) {
            $message = '<div class="alert-message alert-error">Email sudah terdaftar untuk karyawan lain!</div>';
        } else {
            // Masukkan data ke database
            $sql = "INSERT INTO karyawan (nama, jabatan, email, telepon, alamat, tanggal_masuk, gaji) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssd", $nama, $jabatan, $email, $telepon, $alamat, $tanggal_masuk, $gaji); // ssssd = string, string, string, string, string, date, double

            if ($stmt->execute()) {
                $message = '<div class="alert-message alert-success">Data karyawan berhasil ditambahkan!</div>';
                // Opsional: Redirect kembali ke halaman data_karyawan setelah sukses
                header('Location: data_karyawan.php?status=success_add');
                exit();
            } else {
                $message = '<div class="alert-message alert-error">Error: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        }
        $stmt_check_email->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan Baru</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS umum (copy dari data_karyawan.php atau pastikan di style.css) */
        body { display: flex; font-family: 'Arial', sans-serif; margin: 0; min-height: 100vh; background-color: #f0f2f5; color: #333; }
        .dashboard-container { display: flex; width: 100%; }
        .sidebar { /* Styling sidebar sama */
            width: 280px; background-color: #364052; padding: 30px 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.5); position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; align-items: center; color: #fff;
        }
        .sidebar h2.dashboard-title {
            background-color: #fff; color: #333; padding: 12px 30px; border-radius: 8px; margin-bottom: 40px; font-size: 1.8em; font-weight: bold; box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .sidebar-menu { width: 100%; }
        .sidebar-menu ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 15px; }
        .sidebar-menu li a {
            display: block; background-color: #4a5463; color: #fff; padding: 12px 20px; border-radius: 5px; text-decoration: none; font-size: 1.1em; text-align: left; transition: background-color 0.3s ease;
        }
        .sidebar-menu li a:hover { background-color: #5a6473; }
        .sidebar-menu li.active a { background-color: #007bff; }

        .main-content {
            flex-grow: 1;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            margin: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .main-content h1 { color: #0056b3; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .alert-message { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Form styling */
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Pastikan padding tidak menambah lebar */
            font-size: 1em;
        }
        .form-group textarea {
            resize: vertical; /* Izinkan user mengubah tinggi textarea */
            min-height: 80px;
        }
        .form-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end; /* Posisikan tombol ke kanan */
            gap: 10px;
        }
        .form-actions .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            font-size: 1em;
        }
        .btn-submit { background-color: #007bff; }
        .btn-submit:hover { background-color: #0056b3; }
        .btn-cancel { background-color: #6c757d; }
        .btn-cancel:hover { background-color: #5a6268; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <h2 class="dashboard-title">Dashboard</h2>
            <nav class="sidebar-menu">
                <ul>
                    <li><a href="data_karyawan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'data_karyawan.php' ? 'active' : ''; ?>">Data Karyawan</a></li>
                    <li><a href="create_karyawan.php">Tambah Karyawan</a></li>
                    <li><a href="manajemen_pelanggan.php">Manajemen Pelanggan</a></li>
                    <li><a href="manajemen_produk.php">Manajemen Produk</a></li>
                    <li><a href="transaksi.php">Transaksi</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
            <h1>Tambah Karyawan Baru</h1>
            <?php echo $message; // Tampilkan pesan ?>
            <form method="POST" action="create_karyawan.php">
                <div class="form-group">
                    <label for="nama">Nama Karyawan:</label>
                    <input type="text" id="nama" name="nama" required>
                </div>
                <div class="form-group">
                    <label for="jabatan">Jabatan:</label>
                    <select name="jabatan" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <option value="Manager">Manager</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Staff">Staff</option>
                        <option value="Admin">Admin</option>
                        <option value="HRD">HRD</option>
                        <option value="Keuangan">Keuangan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="telepon">Telepon:</label>
                    <input type="text" id="telepon" name="telepon">
                </div>
                <div class="form-group">
                    <label for="alamat">Alamat:</label>
                    <textarea id="alamat" name="alamat"></textarea>
                </div>
                <div class="form-group">
                    <label for="tanggal_masuk">Tanggal Masuk:</label>
                    <input type="date" id="tanggal_masuk" name="tanggal_masuk" required>
                </div>
                 <div class="form-group">
                    <label for="gaji">Gaji:</label>
                    <input type="number" id="gaji" name="gaji" step="0.01" required>
                </div>
                <div class="form-actions">
                    <a href="data_karyawan.php" class="btn btn-cancel">Batal</a>
                    <button type="submit" class="btn btn-submit">Simpan Karyawan</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>