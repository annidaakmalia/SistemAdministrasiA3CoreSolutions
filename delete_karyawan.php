<?php
// delete_karyawan.php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Pastikan ada ID karyawan yang dikirim dan valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: data_karyawan.php'); // Redirect jika ID tidak valid
    exit();
}

$karyawan_id = $_GET['id'];

// Hapus data dari database
$sql_delete = "DELETE FROM karyawan WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $karyawan_id);

if ($stmt_delete->execute()) {
    // Redirect kembali ke halaman data_karyawan dengan pesan sukses
    header('Location: data_karyawan.php?status=success_delete');
    exit();
} else {
    // Redirect dengan pesan error jika gagal
    header('Location: data_karyawan.php?status=error_delete');
    exit();
}

$stmt_delete->close();
$conn->close();
?>