<?php
require_once 'config.php';

/**
 * NetGSM ile SMS Gönder
 * 
 * @param string $telefon Telefon numarası (5XXXXXXXXX formatında)
 * @param string $mesaj SMS içeriği
 * @return array ['success' => bool, 'message' => string, 'code' => string]
 */
function smsGonder($telefon, $mesaj) {
    // Telefon numarasını düzenle
    $telefon = preg_replace('/[^0-9]/', '', $telefon);
    
    // Başında 0 varsa kaldır
    if (substr($telefon, 0, 1) === '0') {
        $telefon = substr($telefon, 1);
    }
    
    // 90 ile başlamıyorsa ekle
    if (substr($telefon, 0, 2) !== '90') {
        $telefon = '90' . $telefon;
    }
    
    // NetGSM API parametreleri
    $params = [
        'usercode' => NETGSM_USERCODE,
        'password' => NETGSM_PASSWORD,
        'gsmno' => $telefon,
        'message' => $mesaj,
        'msgheader' => NETGSM_HEADER,
        'dil' => 'TR'
    ];
    
    // API URL
    $url = 'https://api.netgsm.com.tr/sms/send/get?' . http_build_query($params);
    
    // cURL ile gönder
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return [
            'success' => false,
            'message' => 'Bağlantı hatası: ' . $curlError,
            'code' => 'CURL_ERROR'
        ];
    }
    
    // NetGSM yanıt kodları
    $responseCodes = [
        '00' => 'Başarıyla gönderildi',
        '01' => 'Başarıyla gönderildi',
        '02' => 'Başarıyla gönderildi',
        '20' => 'Mesaj metninde hata var',
        '30' => 'Geçersiz kullanıcı adı/şifre',
        '40' => 'Mesaj başlığı tanımlı değil',
        '50' => 'Abone hesabı aktif değil',
        '51' => 'Abone hesabı aktif değil',
        '70' => 'Hatalı sorgulama',
        '80' => 'Gönderim sınır aşımı',
        '85' => 'Mükerrer gönderim'
    ];
    
    // Yanıtı parse et
    $parts = explode(' ', trim($response));
    $code = $parts[0] ?? '';
    
    if (in_array($code, ['00', '01', '02'])) {
        return [
            'success' => true,
            'message' => 'SMS başarıyla gönderildi',
            'code' => $code,
            'bulkId' => $parts[1] ?? ''
        ];
    } else {
        return [
            'success' => false,
            'message' => $responseCodes[$code] ?? 'Bilinmeyen hata: ' . $response,
            'code' => $code
        ];
    }
}

/**
 * Rezervasyon SMS'i oluştur ve gönder
 */
function rezervasyonSmsGonder($rezervasyon) {
    global $turkceAylar;
    
    $tarih = formatTarih($rezervasyon['reservation_date']);
    $saat = formatSaat($rezervasyon['reservation_time']);
    
    $mesaj = "Sayın {$rezervasyon['customer_name']}, ";
    $mesaj .= "{$tarih} saat {$saat} için ";
    $mesaj .= "{$rezervasyon['person_count']} yetişkin {$rezervasyon['child_count']} çocuk olarak ";
    $mesaj .= "rezervasyon kaydınız alınmıştır. ";
    $mesaj .= "Hayırlı ramazanlar dileriz. ";
	$mesaj .= "Konum ve Bilgi için: xxx.com/iletisim";
    
    return smsGonder($rezervasyon['customer_phone'], $mesaj);
}

// AJAX isteği kontrolü - sadece direkt çağrılırsa çalışsın
if (basename($_SERVER['SCRIPT_FILENAME']) === 'sms.php' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'send_reservation_sms':
            $rezervasyonId = intval($_POST['rezervasyon_id'] ?? 0);
            
            if (!$rezervasyonId) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz rezervasyon']);
                exit;
            }
            
            // Rezervasyonu çek
            $stmt = $pdo->prepare("
                SELECT r.*, t.table_number 
                FROM reservations r 
                JOIN tables t ON r.table_id = t.id 
                WHERE r.id = ?
            ");
            $stmt->execute([$rezervasyonId]);
            $rezervasyon = $stmt->fetch();
            
            if (!$rezervasyon) {
                echo json_encode(['success' => false, 'message' => 'Rezervasyon bulunamadı']);
                exit;
            }
            
            if (empty($rezervasyon['customer_phone'])) {
                echo json_encode(['success' => false, 'message' => 'Telefon numarası yok']);
                exit;
            }
            
            $result = rezervasyonSmsGonder($rezervasyon);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
    }
    exit;
}
?>
