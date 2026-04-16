<?php
require_once 'config.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo "Geçersiz ID";
    exit;
}

// Rezervasyonu çek
$stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
$stmt->execute([$id]);
$rez = $stmt->fetch();

if (!$rez) {
    echo "Rezervasyon bulunamadı";
    exit;
}

$tarihFormatli = formatTarih($rez['reservation_date']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Fiş - <?= htmlspecialchars($rez['customer_name']) ?></title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            width: 80mm;
            font-family: 'Courier New', monospace;
            background-color: white;
            color: black;
            font-weight: bold;
        }
        
        .fis {
            width: 72mm;
            padding: 4mm;
            margin: 0 auto;
        }
        
        .fis-header {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            border-bottom: 1px dashed #000;
            margin-bottom: 8px;
            padding-bottom: 5px;
        }
        
        .fis-date {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 8px;
            color: #555;
        }
        
        .fis-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 10pt;
        }
        
        .fis-line span {
            font-weight: bold;
        }
        
        .fis-line b {
            font-weight: bold;
        }
        
        .fis-note-label {
            margin-top: 8px;
            font-weight: bold;
            font-size: 9pt;
        }
        
        .fis-note {
            border: 1px solid #ccc;
            padding: 3px;
            font-size: 9pt;
            min-height: 20px;
            margin-top: 3px;
        }
        
        .fis-masa {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-top: 10px;
            padding: 5px;
            border: 2px solid #000;
        }
        
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
        
        .print-btn {
            background: #e94560;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 5px;
        }
        
        .print-btn:hover {
            background: #d63550;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                width: 80mm;
            }
        }
        
        @media screen {
            body {
                background: #f0f0f0;
                padding: 20px;
                width: 100%;
            }
            
            .fis {
                background: white;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Yazdır</button>
        <button class="print-btn" onclick="window.close()" style="background:#666">Kapat</button>
    </div>
    
    <div class="fis">
        <div class="fis-header">PRINCEX</div>
        <div class="fis-date"><?= $tarihFormatli ?> <?= date('Y', strtotime($rez['reservation_date'])) ?></div>
        
        <div class="fis-line">
            <span>Tip:</span>
            <b><?= htmlspecialchars($rez['customer_type']) ?></b>
        </div>
        
        <div class="fis-line">
            <span>Müşteri:</span>
            <b><?= htmlspecialchars($rez['customer_name']) ?></b>
        </div>
        
        <div class="fis-line">
            <span>Telefon:</span>
            <b><?= htmlspecialchars($rez['customer_phone']) ?></b>
        </div>
        
        <div class="fis-line">
            <span>Kişi:</span>
            <b><?= $rez['person_count'] ?></b>
        </div>
        
        <div class="fis-line">
            <span>Çocuk:</span>
            <b><?= $rez['child_count'] ?? 0 ?></b>
        </div>
        
        <div class="fis-line">
            <span>Saat:</span>
            <b><?= substr($rez['reservation_time'], 0, 5) ?></b>
        </div>
                
        <?php if (!empty($rez['notes'])): ?>
        <div class="fis-note-label">Not:</div>
        <div class="fis-note"><?= htmlspecialchars($rez['notes']) ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
