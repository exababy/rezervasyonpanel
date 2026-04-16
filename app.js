// Global değişkenler
let currentDate = document.getElementById('selectedDate').value;

// Modal aç
function openModal() {
    document.getElementById('reservationModal').classList.add('active');
    document.getElementById('modalTitle').textContent = 'Yeni Rezervasyon';
    document.getElementById('reservationForm').reset();
    document.getElementById('reservationId').value = '';
    document.getElementById('reservationDate').value = currentDate;
    document.getElementById('reservationTime').value = '18:00';
    document.getElementById('childCount').value = '0';
    document.getElementById('sendSms').checked = true;
    
    // NetGSM numaralarını yükle
    loadNetGsmNumbers();
}

// Modal kapat
function closeModal() {
    document.getElementById('reservationModal').classList.remove('active');
}

// Rezervasyon düzenle
async function editReservation(id) {
    const formData = new FormData();
    formData.append('action', 'get_reservation');
    formData.append('id', id);
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            const r = result.data;
            
            document.getElementById('reservationModal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Rezervasyon Düzenle';
            
            document.getElementById('reservationId').value = r.id;
            document.getElementById('customerType').value = r.customer_type || 'Bireysel';
            document.getElementById('customerName').value = r.customer_name;
            document.getElementById('customerPhone').value = r.customer_phone || '';
            document.getElementById('personCount').value = r.person_count;
            document.getElementById('childCount').value = r.child_count || 0;
            document.getElementById('reservationDate').value = r.reservation_date;
            document.getElementById('reservationTime').value = r.reservation_time;
            document.getElementById('reservationNotes').value = r.notes || '';
            document.getElementById('sendSms').checked = false;
        }
    } catch (error) {
        console.error('Hata:', error);
        showToast('Rezervasyon yüklenemedi', 'error');
    }
}

// Rezervasyon kaydet
async function saveReservation(event) {
    event.preventDefault();
    
    const form = document.getElementById('reservationForm');
    const formData = new FormData(form);
    
    const id = document.getElementById('reservationId').value;
    formData.append('action', id ? 'update_reservation' : 'add_reservation');
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(id ? 'Rezervasyon güncellendi' : 'Rezervasyon eklendi', 'success');
            
            if (result.sms) {
                if (result.sms.success) {
                    showToast('SMS gönderildi', 'success');
                } else {
                    showToast('SMS gönderilemedi: ' + result.sms.message, 'error');
                }
            }
            
            closeModal();
            
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            showToast('Hata oluştu', 'error');
        }
    } catch (error) {
        console.error('Hata:', error);
        showToast('Kaydetme hatası', 'error');
    }
}

// Rezervasyon sil
async function deleteReservation(id) {
    if (!confirm('Bu rezervasyonu silmek istediğinize emin misiniz?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_reservation');
    formData.append('id', id);
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Rezervasyon silindi', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    } catch (error) {
        console.error('Hata:', error);
        showToast('Silme hatası', 'error');
    }
}

// Tarih değiştir
function changeDate(days) {
    const dateInput = document.getElementById('selectedDate');
    const date = new Date(dateInput.value);
    date.setDate(date.getDate() + days);
    dateInput.value = date.toISOString().split('T')[0];
    loadDate(dateInput.value);
}

// Tarihi yükle
function loadDate(date) {
    window.location.href = `index.php?tarih=${date}`;
}

// Toast bildirim göster
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Klavye kısayolları
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Telefon numarası otomatik düzeltme
document.getElementById('customerPhone').addEventListener('blur', function() {
    let phone = this.value;
    // Boşlukları kaldır
    phone = phone.replace(/\s+/g, '');
    // Başındaki 0'ı kaldır
    phone = phone.replace(/^0+/, '');
    this.value = phone;
});

// Tekli fiş yazdırma
function printSingleReceipt(id) {
    window.open('print_single.php?id=' + id, '_blank');
}

// NetGSM numaralarını yükle
async function loadNetGsmNumbers() {
    const lastNumbersDiv = document.getElementById('lastNumbers');
    lastNumbersDiv.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Yükleniyor...</div>';
    
    const formData = new FormData();
    formData.append('action', 'get_netgsm_numbers');
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const numbers = result.data || [];
            const currentCall = result.current_call;
            
            // Şu an konuşulan numarayı göster
            const currentCallSection = document.getElementById('currentCallSection');
            const currentCallNumber = document.getElementById('currentCallNumber');
            
            if (currentCall && currentCall.phone) {
                currentCallSection.style.display = 'block';
                const statusText = currentCall.status === 'answered' ? '📞 Konuşuyor' : '📱 Çalıyor';
                currentCallNumber.innerHTML = `
                    <div class="number-info">
                        <div class="number-display"><i class="fas fa-phone-alt"></i> ${currentCall.phone}</div>
                        <div class="number-meta">${statusText}</div>
                    </div>
                    <button class="btn-add-number" onclick="addNetGsmNumber('${currentCall.phone}')" title="Ekle">
                        <i class="fas fa-plus"></i>
                    </button>
                `;
            } else {
                currentCallSection.style.display = 'none';
            }
            
            // Son gelen numaraları göster
            if (numbers.length > 0) {
                lastNumbersDiv.innerHTML = '';
                numbers.forEach(item => {
                    const numberDiv = document.createElement('div');
                    numberDiv.className = 'netgsm-number';
                    
                    let metaInfo = item.datetime || '';
                    if (item.duration && parseInt(item.duration) > 0) {
                        const minutes = Math.floor(parseInt(item.duration) / 60);
                        const seconds = parseInt(item.duration) % 60;
                        metaInfo += ` • ${minutes}:${seconds.toString().padStart(2, '0')} dk`;
                    }
                    
                    numberDiv.innerHTML = `
                        <div class="number-info">
                            <div class="number-display"><i class="fas fa-phone-volume"></i> ${item.phone}</div>
                            ${metaInfo ? '<div class="number-meta">' + metaInfo + '</div>' : ''}
                        </div>
                        <button class="btn-add-number" onclick="addNetGsmNumber('${item.phone}')" title="Ekle">
                            <i class="fas fa-plus"></i>
                        </button>
                    `;
                    lastNumbersDiv.appendChild(numberDiv);
                });
            } else {
                lastNumbersDiv.innerHTML = '<div class="no-data">Henüz gelen arama yok</div>';
            }
        } else {
            lastNumbersDiv.innerHTML = '<div class="error">Numaralar yüklenemedi</div>';
        }
    } catch (error) {
        console.error('NetGSM yükleme hatası:', error);
        lastNumbersDiv.innerHTML = '<div class="error">Bağlantı hatası</div>';
    }
}

// NetGSM numarasını telefon alanına ekle
function addNetGsmNumber(phone) {
    const phoneInput = document.getElementById('customerPhone');
    phoneInput.value = phone;
    phoneInput.focus();
    
    // Küçük bir animasyon efekti
    phoneInput.style.backgroundColor = '#e94560';
    setTimeout(() => {
        phoneInput.style.backgroundColor = '';
    }, 300);
    
    showToast('Numara eklendi', 'success');
}

// NetGSM numaralarını yenile
function refreshNetGsmNumbers() {
    const refreshBtn = document.querySelector('.btn-refresh i');
    refreshBtn.classList.add('fa-spin');
    
    loadNetGsmNumbers().then(() => {
        setTimeout(() => {
            refreshBtn.classList.remove('fa-spin');
        }, 500);
    });
}
