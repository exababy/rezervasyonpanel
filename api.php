<?php
// api.php - API
require_once 'config.php';
require_once 'sms.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

if ($action === 'add_reservation') {
    $stmt = $pdo->prepare("
        INSERT INTO reservations 
        (table_id, customer_type, customer_name, customer_phone, person_count, child_count,
         reservation_date, reservation_time, status, notes, price, created_at) 
        VALUES (1, ?, ?, ?, ?, ?, ?, ?, 'Bekliyor', ?, ?, NOW())
    ");
    
    $customerPhone = $_POST['customer_phone'] ?? '';
    // Telefon numarasını temizle: başındaki 0'ı ve boşlukları kaldır
    $customerPhone = preg_replace('/\s+/', '', $customerPhone);
    $customerPhone = ltrim($customerPhone, '0');
    
    $customerName = $_POST['customer_name'] ?? '';
    $personCount = intval($_POST['person_count'] ?? 1);
    $childCount = intval($_POST['child_count'] ?? 0);
    $reservationDate = $_POST['reservation_date'] ?? date('Y-m-d');
    $reservationTime = $_POST['reservation_time'] ?? '19:00';
    
    $stmt->execute([
        $_POST['customer_type'] ?? 'Bireysel',
        $customerName,
        $customerPhone,
        $personCount,
        $childCount,
        $reservationDate,
        $reservationTime,
        $_POST['notes'] ?? '',
        floatval($_POST['price'] ?? 0)
    ]);
    
    $rezervasyonId = $pdo->lastInsertId();
    
    // SMS gönderimi
    $smsResult = null;
    if (!empty($customerPhone) && isset($_POST['send_sms']) && $_POST['send_sms'] === '1') {
        $data = [
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'person_count' => $personCount,
			'child_count' => $childCount,
            'reservation_date' => $reservationDate,
            'reservation_time' => $reservationTime,
            'table_number' => 1
        ];
        
        $smsResult = rezervasyonSmsGonder($data);
    }
    
    echo json_encode(['success' => true, 'id' => $rezervasyonId, 'sms' => $smsResult]);
    exit;
}

if ($action === 'update_reservation') {
    // Telefon numarasını temizle
    $customerPhone = $_POST['customer_phone'] ?? '';
    $customerPhone = preg_replace('/\s+/', '', $customerPhone);
    $customerPhone = ltrim($customerPhone, '0');
    
    $stmt = $pdo->prepare("
        UPDATE reservations SET 
            customer_type = ?, customer_name = ?, customer_phone = ?,
            person_count = ?, child_count = ?, reservation_date = ?, reservation_time = ?, notes = ?, price = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['customer_type'] ?? 'Bireysel',
        $_POST['customer_name'],
        $customerPhone,
        intval($_POST['person_count']),
        intval($_POST['child_count'] ?? 0),
        $_POST['reservation_date'],
        $_POST['reservation_time'],
        $_POST['notes'] ?? '',
        floatval($_POST['price'] ?? 0),
        intval($_POST['id'])
    ]);
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete_reservation') {
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
    $stmt->execute([intval($_POST['id'])]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_reservation') {
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([intval($_POST['id'])]);
    $rez = $stmt->fetch();
    if ($rez) {
        $rez['reservation_date'] = date('Y-m-d', strtotime($rez['reservation_date']));
        $rez['reservation_time'] = substr($rez['reservation_time'], 0, 5);
    }
    echo json_encode(['success' => true, 'data' => $rez]);
    exit;
}

if ($action === 'get_reservations') {
    $tarih = $_POST['tarih'] ?? date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT * FROM reservations 
        WHERE reservation_date = ?
        ORDER BY person_count DESC, child_count DESC, reservation_time
    ");
    $stmt->execute([$tarih]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'get_netgsm_numbers') {
    // Webhook'tan kaydedilmiş çağrı kayıtlarını oku
    $callsFile = __DIR__ . '/netgsm_calls.json';
    $numbers = [];
    
    if (file_exists($callsFile)) {
        $calls = json_decode(file_get_contents($callsFile), true) ?: [];
        
        // Farklı numaraları topla (aynı numaradan sadece en son kayıt)
        $processedNumbers = [];
        
        foreach ($calls as $call) {
            $phone = $call['phone'] ?? '';
            
            if (!empty($phone) && !in_array($phone, $processedNumbers)) {
                $processedNumbers[] = $phone;
                
                $numbers[] = [
                    'phone' => $phone,
                    'datetime' => $call['datetime'] ?? '',
                    'duration' => $call['duration'] ?? 0,
                    'status' => $call['status'] ?? 'ended',
                    'type' => 'call'
                ];
                
                // İlk 5 numarayı aldıktan sonra dur
                if (count($numbers) >= 5) break;
            }
        }
    }
    
    // Şu an konuşulan numarayı session'dan al
    $currentCall = null;
    if (isset($_SESSION['netgsm_current_call'])) {
        $callData = $_SESSION['netgsm_current_call'];
        // 60 saniyeden eski ise temizle
        if (time() - ($callData['timestamp'] ?? 0) < 60) {
            $currentCall = [
                'phone' => $callData['phone'] ?? '',
                'status' => $callData['status'] ?? 'ringing'
            ];
        } else {
            unset($_SESSION['netgsm_current_call']);
        }
    }
    
    echo json_encode(['success' => true, 'data' => $numbers, 'current_call' => $currentCall]);
    exit;
}

// Hiçbiri değilse
echo json_encode(['success' => false, 'message' => 'Bilinmeyen action: ' . $action]);
