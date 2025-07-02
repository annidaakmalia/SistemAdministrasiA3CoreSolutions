
<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $jabatan = $_POST['jabatan'];
    $gaji = $_POST['gaji'];
    $tanggal_masuk = $_POST['tanggal_masuk'];

    $sql = "INSERT INTO karyawan (nama, jabatan, gaji, tanggal_masuk) 
            VALUES ('$nama', '$jabatan', '$gaji', '$tanggal_masuk')";
    $conn->query($sql);

    $last_id = $conn->insert_id;

    $lastData = $conn->query("SELECT * FROM karyawan WHERE id = $last_id")->fetch_assoc();

    $_SESSION['karyawan_terakhir'] = $lastData;

    header('Location: data_karyawan.php');
    exit;
}

$result = $conn->query("SELECT * FROM karyawan");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Administrasi Data Karyawan</title>
    <link rel="stylesheet" href="style.css">
</head>

</html>
