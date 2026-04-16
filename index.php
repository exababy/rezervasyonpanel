<?php
require_once 'config.php';

$bugun = date('Y-m-d');
$secilenTarih = $_GET['tarih'] ?? $bugun;

// Rezervasyonları çek (kişi sayısına göre, sonra çocuk sayısına göre azalan sırada)
$stmt = $pdo->prepare("
    SELECT * FROM reservations 
    WHERE reservation_date = ?
    ORDER BY person_count DESC, child_count DESC, reservation_time
");
$stmt->execute([$secilenTarih]);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Princex - Rezervasyon</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-utensils"></i> Princex - Rezervasyon</h1>
            <div class="header-actions">
                <div class="date-selector">
                    <button onclick="changeDate(-1)" class="btn-icon"><i class="fas fa-chevron-left"></i></button>
                    <input type="date" id="selectedDate" value="<?= $secilenTarih ?>" onchange="loadDate(this.value)">
                    <button onclick="changeDate(1)" class="btn-icon"><i class="fas fa-chevron-right"></i></button>
                    <button onclick="loadDate('<?= $bugun ?>')" class="btn-today">Bugün</button>
                </div>
                <button class="btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Yeni Rezervasyon
                </button>
                <a href="print_list.php?tarih=<?= $secilenTarih ?>" target="_blank" class="btn-print-all">
                    <i class="fas fa-print"></i> Liste (A4)
                </a>
                <a href="print_receipts.php?tarih=<?= $secilenTarih ?>" target="_blank" class="btn-print-receipts">
                    <i class="fas fa-receipt"></i> Fişler (80mm)
                </a>
            </div>
        </header>
        
        <div class="stats">
            <div class="stat">
                <span class="stat-value"><?= count($rezervasyonlar) ?></span>
                <span class="stat-label">Rezervasyon</span>
            </div>
            <div class="stat">
                <span class="stat-value"><?= array_sum(array_column($rezervasyonlar, 'person_count')) ?></span>
                <span class="stat-label">Kişi</span>
            </div>
            <div class="stat">
                <span class="stat-value"><?= array_sum(array_column($rezervasyonlar, 'child_count')) ?></span>
                <span class="stat-label">Çocuk</span>
            </div>
            <div class="stat">
                <span class="stat-value"><?= $gruplar['2'] ?></span>
                <span class="stat-label">2'li</span>
            </div>
            <div class="stat">
                <span class="stat-value"><?= $gruplar['4'] ?></span>
                <span class="stat-label">3-4'lü</span>
            </div>
        </div>
        
        <table class="reservation-table">
            <thead>
                <tr>
                    <th>Tip</th>
                    <th>İsim</th>
                    <th>Telefon</th>
                    <th>Kişi</th>
                    <th>Çocuk</th>
                    <th>Saat</th>
                    <th>Not</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rezervasyonlar)): ?>
                <tr>
                    <td colspan="8" class="empty">Bu tarihte rezervasyon bulunmuyor</td>
                </tr>
                <?php else: ?>
                <?php foreach ($rezervasyonlar as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['customer_type']) ?></td>
                    <td><?= htmlspecialchars($r['customer_name']) ?></td>
                    <td><?= htmlspecialchars($r['customer_phone']) ?></td>
                    <td><?= $r['person_count'] ?></td>
                    <td><?= $r['child_count'] ?? 0 ?></td>
                    <td><?= substr($r['reservation_time'], 0, 5) ?></td>
                    <td><?= htmlspecialchars($r['notes']) ?></td>
                    <td class="actions">
                        <button onclick="editReservation(<?= $r['id'] ?>)" class="btn-edit" title="Düzenle">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteReservation(<?= $r['id'] ?>)" class="btn-delete" title="Sil">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button onclick="printSingleReceipt(<?= $r['id'] ?>)" class="btn-print-single" title="Fiş Yazdır">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal -->
    <div class="modal" id="reservationModal">
        <div class="modal-wrapper">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalTitle">Yeni Rezervasyon</h3>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <form id="reservationForm" onsubmit="saveReservation(event)">
                    <input type="hidden" id="reservationId" name="id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Müşteri Tipi</label>
                            <select id="customerType" name="customer_type">
                                <option value="Bireysel">Bireysel</option>
                                <option value="Kurumsal">Kurumsal</option>
                                <option value="Gelin">Gelin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kişi Sayısı</label>
                            <input type="number" id="personCount" name="person_count" value="1" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Çocuk Sayısı</label>
                            <input type="number" id="childCount" name="child_count" value="0" min="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Müşteri Adı</label>
                        <input type="text" id="customerName" name="customer_name" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Telefon</label>
                        <input type="tel" id="customerPhone" name="customer_phone" placeholder="5XX XXX XX XX">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tarih</label>
                        <input type="date" id="reservationDate" name="reservation_date" value="<?= $secilenTarih ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Saat</label>
                        <input type="time" id="reservationTime" name="reservation_time" value="18:00" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notlar</label>
                    <textarea id="reservationNotes" name="notes" rows="2"></textarea>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="sendSms" name="send_sms" value="1" checked>
                        Müşteriye SMS gönder
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Vazgeç</button>
                    <button type="submit" class="btn-save">Kaydet</button>
                </div>
            </form>
        </div>
        
        <!-- NetGSM Numaralar Modal (Sağ Taraf) -->
        <div class="netgsm-modal">
            <div class="netgsm-header">
                <h4><i class="fas fa-phone"></i> NetGSM Numaralar</h4>
                <button class="btn-refresh" onclick="refreshNetGsmNumbers()" title="Yenile">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="netgsm-content">
                <div id="currentCallSection" class="netgsm-section" style="display: none;">
                    <h5>Şu An Konuşulan</h5>
                    <div id="currentCallNumber" class="netgsm-number current-call">
                        <span class="number-display"></span>
                        <button class="btn-add-number" onclick="addNetGsmNumber('')">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="netgsm-section">
                    <h5>Son 5 Gelen Arama</h5>
                    <div id="lastNumbers" class="last-numbers">
                        <div class="loading">
                            <i class="fas fa-spinner fa-spin"></i> Yükleniyor...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="app.js"></script>
    <script>
    // Tekli fiş yazdırma (iframe ile sayfa açmadan)
    function printSingleReceipt(id) {
        var iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = 'print_single.php?id=' + id + '&auto=1';
        document.body.appendChild(iframe);
        
        iframe.onload = function() {
            setTimeout(function() {
                iframe.contentWindow.print();
                // Yazdırma bitince iframe'i kaldır
                setTimeout(function() {
                    document.body.removeChild(iframe);
                }, 1000);
            }, 300);
        };
    }
    </script>
</body>
</html>
