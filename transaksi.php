<?php
// manage_transactions.php
session_start();
include 'koneksi.php';

// Periksa apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$role_name = htmlspecialchars($_SESSION['role_name'] ?? 'Guest');
$current_user_id = $_SESSION['user_id']; // ID user yang sedang login

// Fungsi untuk mengecek apakah link aktif (untuk sidebar)
function isActive($pageName) {
    return basename($_SERVER['PHP_SELF']) == $pageName ? 'active' : '';
}

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
$transaction_id = $_GET['id'] ?? null;
$transaction_data = null;
$transaction_details = [];
$customer_list = [];
$product_list = [];

// Fetch Customers for dropdown
$sql_customers = "SELECT id, nama_pelanggan FROM customers ORDER BY nama_pelanggan ASC";


// --- PROSES CRUD (POST Requests) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: Add New Transaction
    if ($action === 'add_transaction') {
        $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
        $transaction_items = json_decode($_POST['transaction_items'], true); // Array of {product_id, quantity, price_per_unit}

        if (empty($transaction_items)) {
            $_SESSION['message'] = 'Tidak ada item produk dalam transaksi.';
            $_SESSION['message_type'] = 'alert-error';
            header('Location: manage_transactions.php?mode=add');
            exit();
        }

        $conn->begin_transaction(); // Mulai transaksi database
        $success = true;
        $total_amount = 0;

        try {
            // 1. Insert into transactions table
            $stmt_trans = $conn->prepare("INSERT INTO transactions (customer_id, user_id, total_amount, status) VALUES (?, ?, ?, 'completed')");
            if (!$stmt_trans) {
                throw new Exception("Gagal menyiapkan statement transaksi: " . $conn->error);
            }
            // total_amount diisi 0 dulu, akan diupdate nanti
            $stmt_trans->bind_param("iid", $customer_id, $current_user_id, $total_amount);
            if (!$stmt_trans->execute()) {
                throw new Exception("Gagal membuat transaksi: " . $stmt_trans->error);
            }
            $new_transaction_id = $stmt_trans->insert_id;
            $stmt_trans->close();

            // 2. Insert into transaction_details and update product stock
            $stmt_detail = $conn->prepare("INSERT INTO transaction_details (transaction_id, product_id, quantity, price_per_unit, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt_update_stock = $conn->prepare("UPDATE products SET stok = stok - ? WHERE id = ? AND stok >= ?"); // Pastikan stok cukup

            if (!$stmt_detail || !$stmt_update_stock) {
                throw new Exception("Gagal menyiapkan statement detail/stok: " . $conn->error);
            }

            foreach ($transaction_items as $item) {
                $product_id = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                $price_per_unit = (float)$item['price_per_unit'];
                $subtotal = $quantity * $price_per_unit;

                // Cek stok sebelum mengurangi
                $check_stock_stmt = $conn->prepare("SELECT stok FROM products WHERE id = ?");
                $check_stock_stmt->bind_param("i", $product_id);
                $check_stock_stmt->execute();
                $stock_result = $check_stock_stmt->get_result()->fetch_assoc();
                $available_stock = $stock_result['stok'] ?? 0;
                $check_stock_stmt->close();

                if ($available_stock < $quantity) {
                    throw new Exception("Stok untuk produk ID " . $product_id . " tidak cukup. Tersedia: " . $available_stock . ", Diminta: " . $quantity);
                }

                // Insert detail
                $stmt_detail->bind_param("iiidd", $new_transaction_id, $product_id, $quantity, $price_per_unit, $subtotal);
                if (!$stmt_detail->execute()) {
                    throw new Exception("Gagal menambahkan detail transaksi: " . $stmt_detail->error);
                }

                // Update stock
                $stmt_update_stock->bind_param("iii", $quantity, $product_id, $quantity);
                if (!$stmt_update_stock->execute()) {
                    throw new Exception("Gagal mengurangi stok produk ID " . $product_id . ": " . $stmt_update_stock->error);
                }
                if ($stmt_update_stock->affected_rows === 0) {
                    throw new Exception("Gagal mengurangi stok produk ID " . $product_id . ", mungkin stok tidak cukup.");
                }

                $total_amount += $subtotal;
            }
            $stmt_detail->close();
            $stmt_update_stock->close();

            // 3. Update total_amount in transactions table
            $stmt_update_total = $conn->prepare("UPDATE transactions SET total_amount = ? WHERE id = ?");
            if (!$stmt_update_total) {
                throw new Exception("Gagal menyiapkan statement update total: " . $conn->error);
            }
            $stmt_update_total->bind_param("di", $total_amount, $new_transaction_id);
            if (!$stmt_update_total->execute()) {
                throw new Exception("Gagal memperbarui total transaksi: " . $stmt_update_total->error);
            }
            $stmt_update_total->close();

            $conn->commit(); // Commit semua perubahan
            $_SESSION['message'] = 'Transaksi penjualan berhasil dibuat!';
            $_SESSION['message_type'] = 'alert-success';

        } catch (Exception $e) {
            $conn->rollback(); // Rollback jika ada error
            $_SESSION['message'] = 'Error saat membuat transaksi: ' . $e->getMessage();
            $_SESSION['message_type'] = 'alert-error';
            // Redirect kembali ke form add agar input tidak hilang
            header('Location: manage_transactions.php?mode=add');
            exit();
        }
        header('Location: manage_transactions.php'); // Redirect ke halaman daftar transaksi
        exit();

    } elseif ($action === 'update_status') {
        $id = $_POST['id'] ?? '';
        $new_status = $_POST['status'] ?? '';

        if (empty($id) || empty($new_status)) {
            $_SESSION['message'] = 'ID transaksi atau status baru tidak valid.';
            $_SESSION['message_type'] = 'alert-error';
        } else {
            $stmt = $conn->prepare("UPDATE transactions SET status = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $new_status, $id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Status transaksi berhasil diperbarui!';
                    $_SESSION['message_type'] = 'alert-success';
                } else {
                    $_SESSION['message'] = 'Gagal memperbarui status transaksi: ' . $stmt->error;
                    $_SESSION['message_type'] = 'alert-error';
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = 'Gagal menyiapkan statement: ' . $conn->error;
                $_SESSION['message_type'] = 'alert-error';
            }
        }
        header('Location: manage_transactions.php');
        exit();

    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            $_SESSION['message'] = 'ID transaksi tidak spesifik untuk dihapus.';
            $_SESSION['message_type'] = 'alert-error';
        } else {
            $conn->begin_transaction();
            try {
                // 1. Dapatkan detail transaksi sebelum menghapus
                $stmt_get_details = $conn->prepare("SELECT product_id, quantity FROM transaction_details WHERE transaction_id = ?");
                if (!$stmt_get_details) {
                    throw new Exception("Gagal menyiapkan statement ambil detail: " . $conn->error);
                }
                $stmt_get_details->bind_param("i", $id);
                $stmt_get_details->execute();
                $details_result = $stmt_get_details->get_result();
                $items_to_restore_stock = [];
                while ($row = $details_result->fetch_assoc()) {
                    $items_to_restore_stock[] = $row;
                }
                $stmt_get_details->close();

                // 2. Hapus transaksi (details akan terhapus karena ON DELETE CASCADE)
                $stmt_delete_trans = $conn->prepare("DELETE FROM transactions WHERE id = ?");
                if (!$stmt_delete_trans) {
                    throw new Exception("Gagal menyiapkan statement delete transaksi: " . $conn->error);
                }
                $stmt_delete_trans->bind_param("i", $id);
                if (!$stmt_delete_trans->execute()) {
                    throw new Exception("Gagal menghapus transaksi: " . $stmt_delete_trans->error);
                }

                // 3. Kembalikan stok produk
                $stmt_restore_stock = $conn->prepare("UPDATE products SET stok = stok + ? WHERE id = ?");
                if (!$stmt_restore_stock) {
                    throw new Exception("Gagal menyiapkan statement restore stok: " . $conn->error);
                }
                foreach ($items_to_restore_stock as $item) {
                    $stmt_restore_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                    if (!$stmt_restore_stock->execute()) {
                        throw new Exception("Gagal mengembalikan stok produk ID " . $item['product_id'] . ": " . $stmt_restore_stock->error);
                    }
                }
                $stmt_restore_stock->close();

                $conn->commit();
                $_SESSION['message'] = 'Transaksi berhasil dihapus dan stok dikembalikan!';
                $_SESSION['message_type'] = 'alert-success';

            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = 'Error saat menghapus transaksi: ' . $e->getMessage();
                $_SESSION['message_type'] = 'alert-error';
            }
        }
        header('Location: manage_transactions.php');
        exit();
    }
}

// --- AMBIL DATA UNTUK TAMPILAN ATAU DETAIL TRANSAKSI (GET Requests) ---
$transaction_list = [];
$sql_transactions = "
    SELECT 
        t.id, 
        c.nama_pelanggan, 
        u.username AS user_pelayanan,
        t.transaction_date, 
        t.total_amount, 
        t.status
    FROM 
        transactions t
    LEFT JOIN 
        customers c ON t.customer_id = c.id
    LEFT JOIN
        users u ON t.user_id = u.id
    ORDER BY t.transaction_date DESC
";
// Jika mode 'detail', ambil data transaksi spesifik dan detailnya
if ($mode === 'detail' && $transaction_id) {
    $stmt_trans_data = $conn->prepare("
        SELECT 
            t.id, 
            c.nama_pelanggan, 
            u.username AS user_pelayanan,
            t.transaction_date, 
            t.total_amount, 
            t.status
        FROM 
            transactions t
        LEFT JOIN 
            customers c ON t.customer_id = c.id
        LEFT JOIN
            users u ON t.user_id = u.id
        WHERE t.id = ?
    ");
    if ($stmt_trans_data) {
        $stmt_trans_data->bind_param("i", $transaction_id);
        $stmt_trans_data->execute();
        $res_trans_data = $stmt_trans_data->get_result();
        if ($res_trans_data->num_rows > 0) {
            $transaction_data = $res_trans_data->fetch_assoc();
        } else {
            $_SESSION['message'] = 'Transaksi tidak ditemukan.';
            $_SESSION['message_type'] = 'alert-error';
            header('Location: manage_transactions.php');
            exit();
        }
        $stmt_trans_data->close();
    }

    $stmt_trans_details = $conn->prepare("
        SELECT 
            td.quantity, 
            td.price_per_unit, 
            td.subtotal,
            p.nama_produk
        FROM 
            transaction_details td
        JOIN 
            products p ON td.product_id = p.id
        WHERE td.transaction_id = ?
    ");
    if ($stmt_trans_details) {
        $stmt_trans_details->bind_param("i", $transaction_id);
        $stmt_trans_details->execute();
        $res_trans_details = $stmt_trans_details->get_result();
        while ($row = $res_trans_details->fetch_assoc()) {
            $transaction_details[] = $row;
        }
        $stmt_trans_details->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Transaksi Penjualan</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS umum (sidebar, alert, button, table) sama seperti manage_products.php */
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

        /* Sidebar Styling (copy from manage_products.php or ensure it's in style.css) */
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


        /* Main Content Area Styling (copy from manage_products.php or ensure it's in style.css) */
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

        /* Styling untuk Pesan Peringatan (copy from manage_products.php or ensure it's in style.css) */
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
        .btn-info { background-color: #17a2b8; }
        .btn-info:hover { background-color: #138496; }

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

        /* Form Specific Styles (copy from manage_products.php or ensure it's in style.css) */
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
        .form-group input[type="number"],
        .form-group select,
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

        /* Specific for Transaction Form */
        #product-items-container {
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 5px;
            margin-top: 15px;
        }
        .product-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .product-item select,
        .product-item input[type="number"] {
            flex: 1; /* Biar rata lebar */
        }
        .product-item .remove-item-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .product-item .remove-item-btn:hover {
            background-color: #c82333;
        }
        #add-item-btn {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        #add-item-btn:hover {
            background-color: #138496;
        }
        .total-amount-display {
            font-size: 1.5em;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
        }

        /* Detail View Specific */
        .detail-card {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #eee;
        }
        .detail-card p {
            margin-bottom: 10px;
        }
        .detail-card p strong {
            display: inline-block;
            width: 150px; /* Align labels */
        }
        .detail-card h3 {
            margin-top: 25px;
            margin-bottom: 15px;
            color: #007bff;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
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
            <h1>Manajemen Transaksi Penjualan</h1>

            <?php echo $message; // Tampilkan pesan dari operasi CRUD ?>

            <?php if ($mode === 'add'): ?>
                <div class="form-container">
                    <h2>Buat Transaksi Baru</h2>
                    <form action="manage_transactions.php" method="POST" id="transactionForm">
                        <input type="hidden" name="action" value="add_transaction">
                        <input type="hidden" name="transaction_items" id="transaction_items_json">

                        <div class="form-group">
                            <label for="customer_id">Pelanggan:</label>
                            <select id="customer_id" name="customer_id">
                                <option value="">-- Pilih Pelanggan (Opsional) --</option>
                                <?php foreach ($customer_list as $customer): ?>
                                    <option value="<?php echo htmlspecialchars($customer['id']); ?>">
                                        <?php echo htmlspecialchars($customer['nama_pelanggan']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <h3>Item Produk:</h3>
                        <div id="product-items-container">
                            <p id="no-items-message">Belum ada item ditambahkan.</p>
                        </div>
                        <button type="button" id="add-item-btn" class="btn btn-info">Tambah Item Produk</button>

                        <div class="total-amount-display">
                            Total: Rp <span id="total-amount-span">0.00</span>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                            <a href="manage_transactions.php" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>

            <?php elseif ($mode === 'detail' && $transaction_data): ?>
                <div class="detail-card">
                    <h2>Detail Transaksi #<?php echo htmlspecialchars($transaction_data['id']); ?></h2>
                    <p><strong>Tanggal Transaksi:</strong> <?php echo htmlspecialchars($transaction_data['transaction_date']); ?></p>
                    <p><strong>Pelanggan:</strong> <?php echo htmlspecialchars($transaction_data['nama_pelanggan'] ?? 'Umum'); ?></p>
                    <p><strong>Dilayani Oleh:</strong> <?php echo htmlspecialchars($transaction_data['user_pelayanan'] ?? 'N/A'); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($transaction_data['status'])); ?></p>
                    <p><strong>Total Pembayaran:</strong> Rp <?php echo number_format(htmlspecialchars($transaction_data['total_amount']), 2, ',', '.'); ?></p>

                    <h3>Item yang Dibeli:</h3>
                    <?php if (!empty($transaction_details)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Harga Satuan</th>
                                    <th>Kuantitas</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaction_details as $detail): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($detail['nama_produk']); ?></td>
                                        <td>Rp <?php echo number_format(htmlspecialchars($detail['price_per_unit']), 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($detail['quantity']); ?></td>
                                        <td>Rp <?php echo number_format(htmlspecialchars($detail['subtotal']), 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Tidak ada detail item untuk transaksi ini.</p>
                    <?php endif; ?>
                    <div class="form-actions" style="text-align: left; margin-top: 20px;">
                         <a href="manage_transactions.php" class="btn btn-primary">Kembali ke Daftar Transaksi</a>
                    </div>
                </div>

            <?php endif; ?>

            <?php if ($mode === 'view' || $mode === 'detail'): // Tampilkan tabel di mode view atau setelah melihat detail ?>
                <div class="action-buttons">
                    <a href="manage_transactions.php?mode=add" class="btn btn-success">Buat Transaksi Baru</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Pelanggan</th>
                            <th>Dilayani Oleh</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transaction_list)): ?>
                            <?php foreach ($transaction_list as $trans): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trans['id']); ?></td>
                                    <td><?php echo htmlspecialchars($trans['nama_pelanggan'] ?? 'Umum'); ?></td>
                                    <td><?php echo htmlspecialchars($trans['user_pelayanan'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($trans['transaction_date']); ?></td>
                                    <td>Rp <?php echo number_format(htmlspecialchars($trans['total_amount']), 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($trans['status'])); ?></td>
                                    <td>
                                        <a href="manage_transactions.php?mode=detail&id=<?php echo $trans['id']; ?>" class="btn btn-info btn-sm">Detail</a>
                                        <form action="manage_transactions.php" method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo $trans['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="btn btn-warning btn-sm" style="margin-left: 5px;">
                                                <option value="completed" <?php echo ($trans['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                <option value="pending" <?php echo ($trans['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="cancelled" <?php echo ($trans['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                        <form action="manage_transactions.php" method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $trans['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan.');">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">Belum ada data transaksi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productItemsContainer = document.getElementById('product-items-container');
            const addItemBtn = document.getElementById('add-item-btn');
            const totalAmountSpan = document.getElementById('total-amount-span');
            const transactionForm = document.getElementById('transactionForm');
            const transactionItemsJson = document.getElementById('transaction_items_json');
            const noItemsMessage = document.getElementById('no-items-message');

            let itemCounter = 0; // Untuk ID unik setiap item
            let transactionItems = []; // Array untuk menyimpan data item transaksi

            // Data produk dari PHP (disimpan di JavaScript)
            const products = <?php echo json_encode($product_list); ?>;

            function updateNoItemsMessage() {
                if (transactionItems.length === 0) {
                    noItemsMessage.style.display = 'block';
                } else {
                    noItemsMessage.style.display = 'none';
                }
            }

            function calculateTotal() {
                let total = 0;
                transactionItems.forEach(item => {
                    total += item.subtotal;
                });
                totalAmountSpan.textContent = total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function addItemToForm(product = null, quantity = 1) {
                itemCounter++;
                const itemId = `item-${itemCounter}`;

                const itemDiv = document.createElement('div');
                itemDiv.classList.add('product-item');
                itemDiv.dataset.itemId = itemId;

                const productSelect = document.createElement('select');
                productSelect.name = `product_id[]`;
                productSelect.classList.add('product-select');
                productSelect.required = true;
                productSelect.innerHTML = '<option value="">-- Pilih Produk --</option>';
                products.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.id;
                    option.textContent = `${p.nama_produk} (Stok: ${p.stok}, Harga: Rp ${p.harga})`;
                    option.dataset.price = p.harga; // Simpan harga di data-attribute
                    option.dataset.stock = p.stok; // Simpan stok di data-attribute
                    if (product && product.id == p.id) { // Jika ada produk awal (untuk edit, jika diaktifkan)
                        option.selected = true;
                    }
                    productSelect.appendChild(option);
                });

                const quantityInput = document.createElement('input');
                quantityInput.type = 'number';
                quantityInput.name = `quantity[]`;
                quantityInput.classList.add('quantity-input');
                quantityInput.min = '1';
                quantityInput.value = quantity;
                quantityInput.required = true;

                const removeItemBtn = document.createElement('button');
                removeItemBtn.type = 'button';
                removeItemBtn.classList.add('remove-item-btn');
                removeItemBtn.textContent = 'X';
                removeItemBtn.addEventListener('click', function() {
                    const indexToRemove = transactionItems.findIndex(item => item.itemId === itemId);
                    if (indexToRemove > -1) {
                        transactionItems.splice(indexToRemove, 1);
                    }
                    itemDiv.remove();
                    calculateTotal();
                    updateNoItemsMessage();
                });

                itemDiv.appendChild(productSelect);
                itemDiv.appendChild(quantityInput);
                itemDiv.appendChild(removeItemBtn);
                productItemsContainer.appendChild(itemDiv);

                // Tambahkan event listener untuk perubahan pada select produk atau input quantity
                productSelect.addEventListener('change', updateItemData);
                quantityInput.addEventListener('input', updateItemData);

                // Inisialisasi item data ke array
                const initialProduct = products.find(p => p.id == productSelect.value);
                const initialPrice = initialProduct ? parseFloat(initialProduct.harga) : 0;
                transactionItems.push({
                    itemId: itemId,
                    product_id: parseInt(productSelect.value) || 0,
                    quantity: parseInt(quantityInput.value) || 0,
                    price_per_unit: initialPrice,
                    subtotal: (parseInt(quantityInput.value) || 0) * initialPrice
                });
                updateItemData.call(productSelect); // Panggil sekali untuk inisialisasi total
                updateNoItemsMessage();
            }

            function updateItemData() {
                const itemDiv = this.closest('.product-item');
                const itemId = itemDiv.dataset.itemId;
                const productSelect = itemDiv.querySelector('.product-select');
                const quantityInput = itemDiv.querySelector('.quantity-input');

                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const productId = selectedOption.value ? parseInt(selectedOption.value) : 0;
                const price = parseFloat(selectedOption.dataset.price || 0);
                const maxStock = parseInt(selectedOption.dataset.stock || 0);
                let quantity = parseInt(quantityInput.value) || 0;

                // Validasi quantity agar tidak melebihi stok
                if (quantity > maxStock) {
                    quantityInput.value = maxStock;
                    quantity = maxStock;
                    alert('Kuantitas tidak boleh melebihi stok yang tersedia (' + maxStock + ').');
                }
                if (quantity < 1) {
                    quantityInput.value = 1;
                    quantity = 1;
                }

                const subtotal = price * quantity;

                // Cari dan update item di array transactionItems
                const itemIndex = transactionItems.findIndex(item => item.itemId === itemId);
                if (itemIndex > -1) {
                    transactionItems[itemIndex] = {
                        itemId: itemId,
                        product_id: productId,
                        quantity: quantity,
                        price_per_unit: price,
                        subtotal: subtotal
                    };
                } else {
                    // Ini seharusnya tidak terjadi jika addItemToForm dipanggil dengan benar
                    console.error("Item not found in transactionItems array:", itemId);
                }
                calculateTotal();
            }

            addItemBtn.addEventListener('click', function() {
                addItemToForm();
            });

            transactionForm.addEventListener('submit', function(event) {
                // Pastikan ada item yang ditambahkan
                if (transactionItems.length === 0) {
                    alert('Harap tambahkan setidaknya satu item produk untuk membuat transaksi.');
                    event.preventDefault();
                    return;
                }

                // Validasi bahwa semua item memiliki product_id dan quantity yang valid
                for (const item of transactionItems) {
                    if (!item.product_id || item.quantity <= 0) {
                        alert('Pastikan semua item produk dipilih dan kuantitasnya lebih dari 0.');
                        event.preventDefault();
                        return;
                    }
                }

                // Masukkan data transactionItems ke hidden input sebagai JSON string
                transactionItemsJson.value = JSON.stringify(transactionItems);
            });

            // Initial state: show "no items" message
            updateNoItemsMessage();
        });
    </script>
</body>
</html>