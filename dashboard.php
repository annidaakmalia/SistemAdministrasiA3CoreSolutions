<?php
session_start();
// Pastikan file koneksi.php sudah ada dan benar
// include 'koneksi.php'; // Mungkin tidak diperlukan di dashboard.php jika hanya menampilkan UI

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Ambil data user dari session
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$role_name = htmlspecialchars($_SESSION['role_name'] ?? 'Guest');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Aplikasi - <?php echo $username; ?></title>
    <link rel="stylesheet" href="style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS Utama untuk Layout Flexbox */
        body {
            display: flex;
            font-family: 'Arial', sans-serif;
            margin: 0;
            min-height: 100vh;
            background-color: #f0f2f5; /* Latar belakang cerah untuk keseluruhan dashboard */
            color: #333;
        }

        .dashboard-container {
            display: flex;
            width: 100%;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background-color: #364052; /* Warna latar belakang sidebar */
            padding: 30px 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.5);
            position: sticky;
            top: 0;
            height: 100vh; /* Pastikan sidebar mengisi tinggi viewport */
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #fff; /* Warna teks umum di sidebar */
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
        .sidebar-menu {
            width: 100%;
        }
        .sidebar-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 15px; /* Jarak antar item menu */
        }
        .sidebar-menu li a {
            display: block;
            background-color: #4a5463; /* Warna latar belakang item menu */
            color: #fff; /* Warna teks item menu */
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1.1em;
            text-align: left; /* Teks rata kiri */
            transition: background-color 0.3s ease;
        }
        .sidebar-menu li a:hover {
            background-color: #5a6473; /* Warna hover item menu */
        }
        .sidebar-menu li.active a {
            background-color: #007bff; /* Warna untuk menu yang sedang aktif */
        }

        /* Main Content Area Styling */
        .main-content {
            flex-grow: 1; /* Konten utama akan mengambil sisa ruang */
            padding: 30px;
            background-color: #fff; /* Latar belakang putih untuk konten utama */
            border-radius: 8px;
            margin: 20px; /* Margin dari tepi body */
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            color: #333; /* Pastikan teks di konten utama terlihat (hitam/gelap) */
        }
        .main-content h1 {
            color: #0056b3; /* Warna judul di konten utama */
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* Styling untuk Pesan Peringatan */
        .warning-message {
            background-color: #fff3cd; /* Warna kuning muda */
            color: #664d03; /* Warna teks coklat gelap */
            border: 1px solid #ffecb5; /* Border kuning */
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }
        /* Styling untuk tombol (jika diperlukan di dashboard utama, biasanya ada di halaman CRUD) */
        /* Anda bisa menghapus bagian ini jika tidak ada tombol di dashboard utama */
        .btn {
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
            <h1>Selamat Datang di Dashboard Utama A3 Core Solutions!</h1>
            <p>Ini adalah area konten utama dashboard Anda. Pilih modul dari menu sidebar.</p>

            <?php if ($role_name === 'Admin'): ?>
                <div class="warning-message">
                    Anda login sebagai: **<?php echo $role_name; ?>** (<?php echo $username; ?>)
                </div>
            <?php else: ?>
                <p>Anda login sebagai: <?php echo $role_name; ?> (<?php echo $username; ?>)</p>
            <?php endif; ?>

            </main>
    </div>
</body>
</html>