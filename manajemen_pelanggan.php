<?php
// manage_customers.php
session_start();
include 'koneksi.php'; // Pastikan file koneksi.php sudah ada dan benar

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Ambil data user dari session
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$role_name = htmlspecialchars($_SESSION['role_name'] ?? 'Guest');

// Fungsi untuk mengecek apakah link aktif (untuk sidebar)
function isActive($pageName) {
    return basename($_SERVER['PHP_SELF']) == $pageName ? 'active' : '';
}

// Fungsi untuk mengecek apakah induk menu aktif (jika ada sub-menu)
function isParentActive($subPages = []) {
    foreach ($subPages as $page) {
        if (basename($_SERVER['PHP_SELF']) == $page) {
            return 'active';
        }
    }
    return '';
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = '<div class="alert-message ' . $_SESSION['message_type'] . '">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$mode = $_GET['mode'] ?? 'view'; // Default mode: 'view'
$customer_id = $_GET['id'] ?? null;
$customer_to_edit = null;

// --- PROSES CRUD (POST Requests) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama_pelanggan = $_POST['nama_pelanggan'] ?? '';
        $alamat = $_POST['alamat'] ?? '';
        $telepon = $_POST['telepon'] ?? '';
        $email = $_POST['email'] ?? '';

        // Validasi input sederhana
        if (empty($nama_pelanggan) || empty($telepon)) { // Minimal nama dan telepon harus ada
            $_SESSION['message'] = 'Nama pelanggan dan telepon harus diisi!';
            $_SESSION['message_type'] = 'alert-error';
        } else {
            $stmt = $conn->prepare("INSERT INTO customers (nama_pelanggan, alamat, telepon, email) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $nama_pelanggan, $alamat, $telepon, $email);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Pelanggan berhasil ditambahkan!';
                    $_SESSION['message_type'] = 'alert-success';
                } else {
                    $_SESSION['message'] = 'Gagal menambahkan pelanggan: ' . $stmt->error;
                    $_SESSION['message_type'] = 'alert-error';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = 'Gagal menyiapkan statement: ' . $conn->error;
                $_SESSION['message_type'] = 'alert-error';
            }
        }
        header('Location: manage_customers.php'); // Redirect kembali ke halaman utama
        exit();

    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $nama_pelanggan = $_POST['nama_pelanggan'] ?? '';
        $alamat = $_POST['alamat'] ?? '';
        $telepon = $_POST['telepon'] ?? '';
        $email = $_POST['email'] ?? '';

        // Validasi input sederhana
        if (empty($id) || empty($nama_pelanggan) || empty($telepon)) {
            $_SESSION['message'] = 'ID pelanggan, nama, dan telepon harus diisi!';
            $_SESSION['message_type'] = 'alert-error';
        } else {
            $stmt = $conn->prepare("UPDATE customers SET nama_pelanggan = ?, alamat = ?, telepon = ?, email = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssssi", $nama_pelanggan, $alamat, $telepon, $email, $id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Data pelanggan berhasil diperbarui!';
                    $_SESSION['message_type'] = 'alert-success';
                } else {
                    $_SESSION['message'] = 'Gagal memperbarui data pelanggan: ' . $stmt->error;
                    $_SESSION['message_type'] = 'alert-error';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = 'Gagal menyiapkan statement: ' . $conn->error;
                $_SESSION['message_type'] = 'alert-error';
            }
        }
        header('Location: manage_customers.php'); // Redirect kembali ke halaman utama
        exit();

    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? ''; // Menggunakan POST untuk delete agar lebih aman (dari form)
        if (empty($id)) {
            $_SESSION['message'] = 'ID pelanggan tidak spesifik untuk dihapus.';
            $_SESSION['message_type'] = 'alert-error';
        } else {
            $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Pelanggan berhasil dihapus!';
                    $_SESSION['message_type'] = 'alert-success';
                } else {
                    $_SESSION['message'] = 'Gagal menghapus pelanggan: ' . $stmt->error;
                    $_SESSION['message_type'] = 'alert-error';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = 'Gagal menyiapkan statement: ' . $conn->error;
                $_SESSION['message_type'] = 'alert-error';
            }
        }
        header('Location: manage_customers.php'); // Redirect kembali ke halaman utama
        exit();
    }
}

// --- AMBIL DATA UNTUK TAMPILAN ATAU EDIT FORM (GET Requests) ---
$sql = "SELECT id, nama_pelanggan, alamat, telepon, email, created_at FROM customers ORDER BY nama_pelanggan ASC";

$customer_data = [];

// Jika mode 'edit', ambil data pelanggan spesifik
if ($mode === 'edit' && $customer_id) {
    $stmt = $conn->prepare("SELECT id, nama_pelanggan, alamat, telepon, email FROM customers WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $customer_to_edit = $res->fetch_assoc();
        } else {
            $_SESSION['message'] = 'Pelanggan tidak ditemukan.';
            $_SESSION['message_type'] = 'alert-error';
            header('Location: manage_customers.php'); // Redirect jika ID tidak valid
            exit();
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan</title>
    <link rel="stylesheet" href="style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS ini HARUS sama persis dengan yang di dashboard.php atau Anda letakkan di style.css */
        /* Pastikan untuk mengimpor atau menyalin CSS yang sama dari manage_products.php */
        body {
            display: flex;
            font-family: 'Arial', sans-serif;
            margin: 0;
            min-height: 100vh;
            background-color: #f0f2f5;
            color: #333;
        }

        .dashboard-container {
            display: flex;
            width: 100%;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background-color: #364052;
            padding: 30px 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.5);
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #fff;
            overflow-y: auto;
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
            gap: 5px;
        }
        .sidebar-menu > ul > li {
            margin-bottom: 10px;
        }
        .sidebar-menu li a {
            display: block;
            background-color: #4a5463;
            color: #fff;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1.1em;
            text-align: left;
            transition: background-color 0.3s ease;
        }
        .sidebar-menu li a:hover {
            background-color: #5a6473;
        }
        .sidebar-menu li a:not([href]):hover {
            cursor: default;
        }
        .sidebar-menu li.active > a {
            background-color: #007bff;
        }

        .sidebar-menu .sub-menu {
            list-style: none;
            padding-left: 20px;
            margin-top: 5px;
            display: none;
        }
        .sidebar-menu li:hover > .sub-menu,
        .sidebar-menu li.active > .sub-menu {
             display: block;
        }
        .sidebar-menu .sub-menu li a {
            background-color: #3e4857;
            padding: 8px 15px;
            font-size: 1em;
        }
        .sidebar-menu .sub-menu li a:hover {
            background-color: #4a5463;
        }
        .sidebar-menu .sub-menu li.active a {
            background-color: #007bff;
        }


        /* Main Content Area Styling */
        .main-content {
            flex-grow: 1;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            margin: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            color: #333;
        }
        .main-content h1 {
            color: #0056b3;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* Styling untuk Pesan Peringatan */
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
            text-align: right; /* Tombol rata kanan */
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

        /* Form Specific Styles */
        .form-container {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #eee;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Pastikan padding tidak menambah lebar */
        }
        .form-actions {
            margin-top: 20px;
            text-align: right;
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
            <h1>Manajemen Pelanggan</h1>

            <?php echo $message; // Tampilkan pesan dari operasi CRUD ?>

            <?php if ($mode === 'add' || $mode === 'edit'): ?>
                <div class="form-container">
                    <h2><?php echo ($mode === 'edit') ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru'; ?></h2>
                    <form action="manage_customers.php" method="POST">
                        <input type="hidden" name="action" value="<?php echo ($mode === 'edit') ? 'edit' : 'add'; ?>">
                        <?php if ($mode === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($customer_to_edit['id']); ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nama_pelanggan">Nama Pelanggan:</label>
                            <input type="text" id="nama_pelanggan" name="nama_pelanggan" value="<?php echo htmlspecialchars($customer_to_edit['nama_pelanggan'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="alamat">Alamat:</label>
                            <textarea id="alamat" name="alamat" rows="3"><?php echo htmlspecialchars($customer_to_edit['alamat'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="telepon">Telepon:</label>
                            <input type="text" id="telepon" name="telepon" value="<?php echo htmlspecialchars($customer_to_edit['telepon'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email (Opsional):</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer_to_edit['email'] ?? ''); ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><?php echo ($mode === 'edit') ? 'Update Pelanggan' : 'Simpan Pelanggan'; ?></button>
                            <a href="manage_customers.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($mode === 'view' || $mode === 'add' || $mode === 'edit'): // Tampilkan tabel di semua mode ?>
                <div class="action-buttons">
                    <a href="manage_customers.php?mode=add" class="btn btn-success">Tambah Pelanggan Baru</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Pelanggan</th>
                            <th>Alamat</th>
                            <th>Telepon</th>
                            <th>Email</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($customer_data)): ?>
                            <?php foreach ($customer_data as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['id']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['nama_pelanggan']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['alamat']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['telepon']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['created_at']); ?></td>
                                    <td>
                                        <a href="manage_customers.php?mode=edit&id=<?php echo $customer['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <form action="manage_customers.php" method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $customer['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini?');">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">Belum ada data pelanggan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>