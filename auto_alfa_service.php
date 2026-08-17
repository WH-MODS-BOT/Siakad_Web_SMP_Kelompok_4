<?php
// Set Timezone Asia/Jakarta agar selaras dengan versi Java
date_default_timezone_set('Asia/Jakarta');

// Mengatur locale ke bahasa Indonesia untuk format nama hari
setlocale(LC_ALL, 'id_ID.utf8', 'id_ID', 'id');

// Hubungkan ke file koneksi database Anda (Sesuaikan path-nya)
// Diasumsikan file koneksi menyediakan objek PDO dengan nama $conn
require_once __DIR__ . '/koneksi.php'; 

function cekSemuaGuruAlfa($conn) {
    try {
        // =====================
        // HANYA SETELAH 15:00
        // =====================
        $jamSekarang = (int)date('H');
        if ($jamSekarang < 15) {
            echo "BELUM JAM 15:00 WIB\n";
            return;
        }

        // =====================
        // HARI INDONESIA
        // =====================
        // Menggunakan IntlDateFormatter agar stabil menghasilkan nama hari dalam Bahasa Indonesia
        $fmt = new IntlDateFormatter('id_ID', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Jakarta', IntlDateFormatter::GREGORIAN, 'EEEE');
        $hari = $fmt->format(new DateTime());

        // =====================
        // AMBIL GURU
        // =====================
        $sqlGuru = "SELECT DISTINCT id_guru FROM japel WHERE hari = ? AND deleted = 0";
        $psGuru = $conn->prepare($sqlGuru);
        $psGuru->execute([$hari]);
        $listGuru = $psGuru->fetchAll(PDO::FETCH_ASSOC);

        foreach ($listGuru as $row) {
            $idGuru = $row['id_guru'];

            // =====================
            // CEK ABSENSI HARI INI
            // =====================
            $cek = "SELECT id_absen FROM absensi_guru WHERE id_guru = ? AND tanggal = CURDATE() LIMIT 1";
            $psCek = $conn->prepare($cek);
            $psCek->execute([$idGuru]);
            
            // =====================
            // BELUM ABSEN -> INSERT ALFA
            // =====================
            if (!$psCek->fetch()) {
                $insert = "INSERT INTO absensi_guru (id_guru, tanggal, status, keterangan, deleted) 
                           VALUES (?, CURDATE(), 'Alfa', 'Tidak hadir sampai jam 15:00 WIB', 0)";
                
                $psInsert = $conn->prepare($insert);
                $psInsert->execute([$idGuru]);

                echo "AUTO ALFA -> Guru ID : " . $idGuru . "\n";
            }
        }

    } catch (Exception $e) {
        error_log($e->getMessage());
        echo "Terjadi kesalahan: " . $e->getMessage() . "\n";
    }
}

// Eksekusi fungsi utama
// Variabel $conn didapatkan dari file koneksi.php Anda
if (isset($conn)) {
    cekSemuaGuruAlfa($conn);
} else {
    echo "Koneksi database tidak tersedia.\n";
}
