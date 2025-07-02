<?php
// data_karyawan.php
session_start();
include 'koneksi.php'; // Pastikan file koneksi.php sudah ada dan benar

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$message = ''; // Untuk pesan notifikasi (sukses/gagal)

// Ambil data karyawan dari database
$sql = "SELECT id, nama, jabatan, email, telepon, tanggal_masuk FROM karyawan ORDER BY nama ASC";
 

$karyawan_data = [];

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS umum dari dashboard.php (pastikan sesuai atau tambahkan di style.css) */
        body {
            display: flex;
            font-family: 'Arial', sans-serif;
            margin: 0;
            min-height: 100vh;
            background-color: #f0f2f5; /* Latar belakang cerah untuk konten */
            color: #333;
        }

        .dashboard-container {
            display: flex;
            width: 100%;
        }

        /* Sidebar styling (copy dari dashboard.php atau pastikan di style.css) */
        .sidebar {
            width: 280px;
            background-color: #364052; /* Warna sidebar sesuai dashboard sebelumnya */
            padding: 30px 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.5);
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #fff;
        }
        .sidebar h2.dashboard-title {
            background-color: #fff;
            color: #333;
            padding: 12px 30px;
            border-radius: 8px;
            margin-bottom: 40px;
            font-size: 1.8em;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .sidebar-menu { width: 100%; }
        .sidebar-menu ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 15px; }
        .sidebar-menu li a {
            display: block;
            background-color: #4a5463; /* Warna menu item sidebar */
            color: #fff;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1.1em;
            text-align: left;
            transition: background-color 0.3s ease;
        }
        .sidebar-menu li a:hover { background-color: #5a6473; }
        .sidebar-menu li.active a { background-color: #007bff; } /* Menu aktif */


        .main-content {
            flex-grow: 1;
            padding: 30px;
            background-color: #fff; /* Latar belakang putih untuk konten utama */
            border-radius: 8px;
            margin: 20px; /* Margin dari tepi body */
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .main-content h1 {
            color: #0056b3;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .alert-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .action-buttons {
            margin-bottom: 20px;
            text-align: right; /* Untuk tombol tambah */
        }
        .action-buttons .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            font-size: 1em;
            margin-left: 10px;
        }
        .btn-primary { background-color: #007bff; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-success { background-color: #28a745; }
        .btn-success:hover { background-color: #218838; }
        .btn-danger { background-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }
        .btn-warning { background-color: #ffc107; color: #333;}
        .btn-warning:hover { background-color: #e0a800; }


        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #555;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }
        table td .btn {
            padding: 5px 10px;
            font-size: 0.9em;
            margin-right: 5px;
        }
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
            <h1>Data Karyawan</h1>

            <?php echo $message; // Tampilkan pesan ?>

            <div class="action-buttons">
                <a href="create_karyawan.php" class="btn btn-success">Tambah Karyawan Baru</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Tanggal Masuk</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($karyawan_data)): ?>
                        <?php foreach ($karyawan_data as $karyawan): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($karyawan['id']); ?></td>
                                <td><?php echo htmlspecialchars($karyawan['nama']); ?></td>
                                <td><?php echo htmlspecialchars($karyawan['jabatan']); ?></td>
                                <td><?php echo htmlspecialchars($karyawan['email']); ?></td>
                                <td><?php echo htmlspecialchars($karyawan['telepon']); ?></td>
                                <td><?php echo htmlspecialchars($karyawan['tanggal_masuk']); ?></td>
                                <td>
                                    <a href="edit_karyawan.php?id=<?php echo $karyawan['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="delete_karyawan.php?id=<?php echo $karyawan['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus karyawan ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Belum ada data karyawan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>