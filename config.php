<?php
session_start();

// Veritabanı Ayarları
define('DB_HOST', 'XXXXX');
define('DB_PORT', 3306);
define('DB_NAME', 'XXXXX');
define('DB_USER', 'XXXXX');
define('DB_PASS', 'XXXXX');

// NetGSM API Ayarları
define('NETGSM_USERCODE', '342XXXXX'); // NetGSM kullanıcı kodunuz
define('NETGSM_PASSWORD', 'XXXXX');          // NetGSM şifreniz
define('NETGSM_HEADER', 'XXXXX');          // SMS başlığı (onaylı başlık)

// PDO Bağlantısı
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Türkçe ay isimleri
$turkceAylar = [
    1 => 'Ocak',
    2 => 'Şubat',
    3 => 'Mart',
    4 => 'Nisan',
    5 => 'Mayıs',
    6 => 'Haziran',
    7 => 'Temmuz',
    8 => 'Ağustos',
    9 => 'Eylül',
    10 => 'Ekim',
    11 => 'Kasım',
    12 => 'Aralık'
];

// Yardımcı fonksiyonlar
function formatTarih($tarih)
{
    global $turkceAylar;
    $dt = new DateTime($tarih);
    return $dt->format('j') . ' ' . $turkceAylar[(int) $dt->format('n')];
}

function formatSaat($saat)
{
    return substr($saat, 0, 5);
}
?>