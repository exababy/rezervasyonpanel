<?php
require_once 'config.php';

$tarih = $_GET['tarih'] ?? date('Y-m-d');
$tarihFormatli = formatTarih($tarih);

// Rezervasyonları çek (kişi sayısına göre, sonra çocuk sayısına göre azalan sırada)
$stmt = $pdo->prepare("
    SELECT * FROM reservations 
    WHERE reservation_date = ?
    ORDER BY person_count DESC, child_count DESC, reservation_time
");
$stmt->execute([$tarih]);
$rezervasyonlar = $stmt->fetchAll();

// Grup istatistikleri hesapla
$gruplar = ['2' => 0, '4' => 0];
foreach ($rezervasyonlar as $r) {
    $toplam = $r['person_count'] + ($r['child_count'] ?? 0);
    if ($toplam <= 2) $gruplar['2']++;
    elseif ($toplam <= 4) $gruplar['4']++;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Rezervasyon Listesi - <?= $tarihFormatli ?></title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #000;
            background: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
        }
        
        .header .date {
            font-size: 14pt;
            color: #555;
        }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 15px;
            font-size: 12pt;
        }
        
        .stats span {
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        
        th, td {
            border: 1px solid #333;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        
        th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        tr:nth-child(even) {
            background: #fafafa;
        }
        
        .col-tip { width: 9%; }
        .col-isim { width: 18%; }
        .col-telefon { width: 14%; }
        .col-saat { width: 7%; text-align: center; }
        .col-kisi { width: 6%; text-align: center; }
        .col-cocuk { width: 6%; text-align: center; }
        .col-masa { width: 8%; text-align: center; }
        .col-not { width: 32%; }
        
        td.center {
            text-align: center;
        }
        
        .empty-row td {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 20px;
        }
        
        .footer {
            margin-top: 15px;
            text-align: right;
            font-size: 9pt;
            color: #666;
        }
        
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        .print-btn {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #e94560;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-btn:hover {
            background: #d63550;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Yazdır
    </button>

    
    <div class="stats">
        <div>Toplam Rezervasyon: <span><?= count($rezervasyonlar) ?></span></div>
        <div>Toplam Kişi: <span><?= array_sum(array_column($rezervasyonlar, 'person_count')) ?></span></div>
        <div>Toplam Çocuk: <span><?= array_sum(array_column($rezervasyonlar, 'child_count')) ?></span></div>
        <div>2'li: <span><?= $gruplar['2'] ?></span></div>
        <div>3-4'lü: <span><?= $gruplar['4'] ?></span></div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th class="col-tip">Tip</th>
                <th class="col-isim">İsim</th>
                <th class="col-telefon">Telefon</th>
                <th class="col-saat">Saat</th>
                <th class="col-kisi">Kişi</th>
                <th class="col-cocuk">Çocuk</th>
                <th class="col-masa">Masa</th>
                <th class="col-not">Not</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $toplamSatir = 95; // 3 sayfa için sabit satır sayısı
            $mevcutSatir = count($rezervasyonlar);
            $bosSatir = $toplamSatir - $mevcutSatir;
            
            if (empty($rezervasyonlar) && $bosSatir == $toplamSatir): 
            ?>
            <?php for ($i = 0; $i < $toplamSatir; $i++): ?>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td class="center">&nbsp;</td>
                <td class="center">&nbsp;</td>
                <td class="center">&nbsp;</td>
                <td class="center">&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <?php endfor; ?>
            <?php else: ?>
            <?php foreach ($rezervasyonlar as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['customer_type']) ?></td>
                <td><?= htmlspecialchars($r['customer_name']) ?></td>
                <td><?= htmlspecialchars($r['customer_phone']) ?></td>
                <td class="center"><?= substr($r['reservation_time'], 0, 5) ?></td>
                <td class="center"><?= $r['person_count'] ?></td>
                <td class="center"><?= $r['child_count'] ?? 0 ?></td>
                <td class="center">&nbsp;</td>
                <td><?= htmlspecialchars($r['notes']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php for ($i = 0; $i < $bosSatir; $i++): ?>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td class="center">&nbsp;</td>
                <td class="center">&nbsp;</td>
                <td class="center">&nbsp;</td>
                <td class="center">&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            <?php endfor; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
