<?php
// success.php - Halaman sukses pembayaran PlutoXTeam
// Bisa nangkap POST dari DOKU dan menampilkan key

// ================== TANGKAP DATA POST ==================
$invoice = '';
$amount = 50000;
$phone = '';
$status = 'Pending';
$payment_status = '';

// Cek apakah ada POST data (dari DOKU)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil raw POST data (format JSON)
    $input = file_get_contents('php://input');
    $jsonData = json_decode($input, true);
    
    // Log buat debugging (opsional, hapus kalo gak mau)
    $logFile = 'doku_success.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - POST: ' . $input . PHP_EOL, FILE_APPEND);
    
    if ($jsonData) {
        // Format dari DOKU
        $invoice = $jsonData['reference_id'] ?? $jsonData['payment_refence_id'] ?? '';
        $amount_raw = $jsonData['amount'] ?? 5000000;
        $amount = $amount_raw / 100; // 7000000 -> 70000
        
        // AMBIL NOMOR WA DARI customField2!
        if (isset($jsonData['customField']['customField2'])) {
            $phone = $jsonData['customField']['customField2'];
        }
        
        // Cek status (payment_status = 1 artinya SUCCESS)
        $payment_status = $jsonData['payment_status'] ?? '';
        if ($payment_status == 1) {
            $status = 'Success';
        }
    } else {
        // Kalo format form-data (jarang)
        $invoice = $_POST['invoice'] ?? $_POST['reference_id'] ?? '';
        $phone = $_POST['customField2'] ?? $_POST['phone'] ?? '';
        $payment_status = $_POST['payment_status'] ?? '';
        if ($payment_status == 1) {
            $status = 'Success';
        }
    }
} 
// Fallback ke GET (untuk testing manual)
else {
    $invoice = $_GET['invoice'] ?? $_GET['reference_id'] ?? '';
    $phone = $_GET['phone'] ?? $_GET['customField2'] ?? '';
    $payment_status = $_GET['payment_status'] ?? $_GET['status'] ?? '';
    if ($payment_status == 1 || strtoupper($payment_status) == 'SUCCESS') {
        $status = 'Success';
    }
}

// Fallback kalo invoice kosong
if (empty($invoice)) {
    $invoice = 'INV-' . date('YmdHis');
}

// Format amount buat tampilan
$amount_formatted = 'Rp ' . number_format($amount, 0, ',', '.');
$current_time = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Pembayaran Berhasil - PlutoXTeam</title>
    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-database.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 16px;
        }
        
        .container {
            background: white;
            border-radius: 24px;
            padding: 32px 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 100%;
            text-align: center;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo-container {
            margin-bottom: 20px;
        }
        
        .logo {
            max-width: 120px;
            max-height: 80px;
            object-fit: contain;
            border-radius: 10px;
        }
        
        .logo-fallback {
            font-size: 24px;
            font-weight: 800;
            color: #667eea;
            letter-spacing: 1px;
            display: none;
        }
        
        .logo-fallback span {
            color: #764ba2;
        }
        
        h1 {
            color: #333;
            font-size: 26px;
            margin-bottom: 8px;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: left;
            border: 1px solid #eaeaea;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #ddd;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #888;
            font-size: 14px;
        }
        
        .info-value {
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
        
        .key-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .key-title {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .key-display {
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 16px;
            font-size: 18px;
            font-weight: 700;
            color: white;
            font-family: monospace;
            letter-spacing: 2px;
            word-break: break-all;
            border: 2px dashed rgba(255,255,255,0.5);
        }
        
        .whatsapp-section {
            background: #e8f5e9;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border-left: 4px solid #25D366;
        }
        
        .whatsapp-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #075E54;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .whatsapp-title svg {
            width: 20px;
            height: 20px;
            fill: #25D366;
        }
        
        .whatsapp-text {
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .phone-display {
            background: white;
            border: 1px solid #25D366;
            border-radius: 30px;
            padding: 12px 16px;
            font-size: 16px;
            font-weight: 600;
            color: #075E54;
            text-align: center;
            margin: 10px 0;
        }
        
        .status-message {
            padding: 10px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
            display: none;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 24px;
            border-radius: 30px;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 8px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-wa {
            background: #25D366;
        }
        
        .btn-copy {
            background: #6c757d;
            margin-top: 10px;
        }
        
        .btn-copy:hover {
            background: #5a6268;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-home {
            background: #667eea;
            margin-top: 15px;
        }

        .btn-payment {
            background: #f0933b;
        }
        
        .footer {
            margin-top: 24px;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .footer strong {
            color: #667eea;
        }
        
        .copy-notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        
        .copy-notification.show {
            opacity: 1;
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 20px;
            border-radius: 16px;
            margin: 20px 0;
            text-align: left;
        }

        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="logo.png" alt="PlutoXTeam Logo" class="logo" id="mainLogo" onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='block'">
            <div class="logo-fallback" id="logoFallback" style="display:none;">
                Pluto<span>XTeam</span>
            </div>
        </div>
        
        <?php if ($status == 'Success'): ?>
        <!-- AREA SUKSES -->
        <div id="successArea">
            <h1>Pembayaran Berhasil! ✅</h1>
            
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">Total</span>
                    <span class="info-value"><?php echo $amount_formatted; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Waktu</span>
                    <span class="info-value"><?php echo $current_time; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nomor WA</span>
                    <span class="info-value" id="phoneDisplay"><?php echo $phone ?: 'Tidak tersedia'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Invoice</span>
                    <span class="info-value"><?php echo $invoice; ?></span>
                </div>
            </div>
            
            <div class="key-card">
                <div class="key-title">🔑 LICENSE KEY VIP ANDA</div>
                <div class="key-display" id="keyDisplay">-</div>
            </div>
            
            <button onclick="copyKey()" class="btn btn-copy" id="copyBtn">📋 Salin Key</button>
            
            <div id="waStatus" class="status-message"></div>
            
            <div class="whatsapp-section">
                <div class="whatsapp-title">
                    <svg viewBox="0 0 24 24">
                        <path d="M19.05 4.91A9.816 9.816 0 0 0 12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01zm-7.01 15.24c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.32a8.26 8.26 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.82 2.42a8.24 8.24 0 0 1 2.41 5.83c.01 4.54-3.69 8.23-8.22 8.23z"/>
                    </svg>
                    <span>Key juga dikirim via WhatsApp</span>
                </div>
                
                <div class="whatsapp-text">
                    Key aktivasi akan dikirim ke nomor WhatsApp Anda. 
                </div>
                
                <div class="phone-display" id="phoneLink"><?php echo $phone ?: 'Tidak tersedia'; ?></div>
                
                <a href="#" id="waLink" class="btn btn-wa" target="_blank">
                    📱 Buka WhatsApp
                </a>
            </div>
            
            <a href="https://plutoxteam.github.io/Payment/" class="btn btn-home">
                🏠 Kembali ke Menu Utama
            </a>
            
            <a href="#" onclick="contactAdmin()" class="btn btn-wa" style="margin-top: 10px;">
                📞 Hubungi Admin (085849299253)
            </a>
        </div>
        <?php else: ?>
        <!-- AREA PENDING/MENUNGGU -->
        <div id="pendingArea" class="warning-box">
            <h3>⏳ Menunggu Pembayaran</h3>
            <p>Anda kembali ke merchant sebelum menyelesaikan pembayaran.</p>
            <p style="margin-top: 10px;"><strong>Invoice:</strong> <?php echo $invoice; ?></p>
            <p><strong>Status:</strong> <?php echo $status; ?> (payment_status: <?php echo $payment_status; ?>)</p>
            <p>Silakan selesaikan pembayaran Anda di halaman DOKU.</p>
            <a href="https://sandbox.doku.com/p-link/p/HDLMKO9gb3" class="btn btn-payment" style="margin-top: 15px;">
                💳 Kembali ke Halaman Pembayaran
            </a>
            <a href="https://plutoxteam.github.io/Payment/" class="btn btn-home" style="margin-top: 10px;">
                🏠 Kembali ke Menu Utama
            </a>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <strong>PlutoXTeam</strong>
        </div>
    </div>
    
    <div class="copy-notification" id="copyNotification">✅ Key berhasil disalin</div>
    
    <script>
        // Konfigurasi Firebase
        const firebaseConfig = {
            databaseURL: "https://sidiannext-default-rtdb.asia-southeast1.firebasedatabase.app"
        };
        firebase.initializeApp(firebaseConfig);
        
        const ADMIN_PHONE = '6285849299253';
        const WHAPI_TOKEN = 'dUtoaA65nf39YiR4oJweRlGP5fCPZXek';
        const AMOUNT_FIXED = 50000; // HARGA FIX RP 50.000
        
        // Data dari PHP
        const phpData = {
            invoice: <?php echo json_encode($invoice); ?>,
            phone: <?php echo json_encode($phone); ?>,
            status: <?php echo json_encode($status); ?>,
            amount: <?php echo $amount; ?>
        };
        
        console.log('📦 Data dari PHP:', phpData);
        
        // ================== FUNGSI FORMAT ==================
        
        function formatRupiah(amount) {
            return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
        }
        
        function getCurrentTime() {
            const now = new Date();
            return now.toLocaleDateString('id-ID', { 
                day: 'numeric', 
                month: 'long', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function getExpireDate() {
            const date = new Date();
            date.setDate(date.getDate() + 30);
            return date.toLocaleDateString('id-ID', { 
                day: 'numeric', 
                month: 'long', 
                year: 'numeric' 
            });
        }
        
        function formatPhoneLocal(phone) {
            if (!phone) return 'Tidak tersedia';
            let cleaned = phone.replace(/\D/g, '');
            if (cleaned.startsWith('62')) return '0' + cleaned.substring(2);
            if (cleaned.startsWith('0')) return cleaned;
            return '0' + cleaned;
        }
        
        function formatPhoneApi(phone) {
            let cleaned = phone.replace(/\D/g, '');
            if (cleaned.startsWith('0')) return '62' + cleaned.substring(1);
            if (!cleaned.startsWith('62')) return '62' + cleaned;
            return cleaned;
        }
        
        // ================== FUNGSI KEY ==================
        
        function generateRandomPart() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            let result = '';
            for (let i = 0; i < 6; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }
        
        function generateKey() {
            return 'VIP-' + generateRandomPart();
        }
        
        async function isKeyExists(key) {
            try {
                const snapshot = await firebase.database().ref('Keys/' + key).once('value');
                return snapshot.exists();
            } catch (error) {
                console.error('❌ Error cek key:', error);
                return true;
            }
        }
        
        async function generateUniqueKey() {
            let attempts = 0;
            const maxAttempts = 10;
            
            while (attempts < maxAttempts) {
                const key = generateKey();
                const exists = await isKeyExists(key);
                
                if (!exists) {
                    console.log(`✅ Key unik: ${key} (${attempts + 1} percobaan)`);
                    return key;
                }
                
                attempts++;
                console.log(`🔄 Key ${key} sudah ada, generate ulang...`);
            }
            
            console.warn('⚠️ Fallback: pake timestamp');
            return 'VIP-' + Date.now().toString(36).toUpperCase().substring(0, 6);
        }
        
        window.copyKey = function() {
            const key = document.getElementById('keyDisplay').innerText;
            if (key && !key.includes('Memproses') && !key.includes('Gagal') && key !== '-') {
                navigator.clipboard.writeText(key).then(() => {
                    const notification = document.getElementById('copyNotification');
                    notification.classList.add('show');
                    setTimeout(() => notification.classList.remove('show'), 2000);
                });
            }
        };
        
        // ================== FUNGSI FIREBASE ==================
        
        async function saveKeyToFirebase(key, invoice, phone) {
            try {
                await firebase.database().ref('Keys/' + key).set({
                    ActivatedAt: 0,
                    Devices: "null",
                    MaxDevices: 1,
                    Type: "Duration",
                    Value: "30d",
                    generated_for: invoice,
                    customer_phone: phone
                });
                
                console.log('✅ Key tersimpan di Keys/' + key);
                return true;
            } catch (error) {
                console.error('❌ Gagal simpan key:', error);
                return false;
            }
        }
        
        // ================== FUNGSI WHATSAPP ==================
        
        async function sendViaWhapi(phone, key, invoice) {
            const statusDiv = document.getElementById('waStatus');
            statusDiv.style.display = 'block';
            statusDiv.innerHTML = '📤 Mengirimkan key VIP via WhatsApp...';
            
            if (!phone || phone.length < 10) {
                statusDiv.className = 'status-message status-error';
                statusDiv.innerHTML = '❌ Nomor WhatsApp tidak valid. Key dapat disalin manual.';
                return false;
            }
            
            try {
                const formattedPhone = formatPhoneApi(phone);
                const formattedAmount = formatRupiah(AMOUNT_FIXED);
                const currentTime = getCurrentTime();
                const expireDate = getExpireDate();
                
                const response = await fetch('https://gate.whapi.cloud/messages/text', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${WHAPI_TOKEN}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        to: formattedPhone,
                        body: `✨ *PEMBAYARAN BERHASIL* ✨

Yth. Pelanggan PlutoXTeam,

Terima kasih telah melakukan pembayaran. Berikut adalah detail transaksi dan key VIP Anda.

🔑 *KEY VIP ANDA*
\`${key}\`

📋 *DETAIL TRANSAKSI*
• Total       : ${formattedAmount}
• Waktu       : ${currentTime}
• Invoice     : ${invoice}
• Masa Berlaku: 30 hari (hingga ${expireDate})

📌 *CARA AKTIVASI*
1. Buka aplikasi
2. Masukkan key: ${key}
3. Klik tombol Login
4. Nikmati layanan premium

📞 *BUTUH BANTUAN?*
Hubungi admin kami
WhatsApp: 0858-4929-9253

Terima kasih atas kepercayaan Anda.
Salam hangat,
*Manajemen PlutoXTeam* 🚀`
                    })
                });
                
                if (response.ok) {
                    statusDiv.className = 'status-message status-success';
                    statusDiv.innerHTML = '✅ Key VIP berhasil dikirim ke WhatsApp!';
                    document.getElementById('waLink').href = `https://wa.me/${formattedPhone}`;
                    return true;
                } else {
                    throw new Error('Gagal mengirim');
                }
                
            } catch (error) {
                console.error('WA Error:', error);
                statusDiv.className = 'status-message status-error';
                statusDiv.innerHTML = '❌ Gagal mengirim ke WhatsApp. Key dapat disalin manual.';
                return false;
            }
        }
        
        // ================== FUNGSI LAIN ==================
        
        window.contactAdmin = function() {
            window.location.href = `https://wa.me/${ADMIN_PHONE}?text=Halo%20admin%20PlutoXTeam%2C%20saya%20butuh%20bantuan%20terkait%20key%20VIP%20saya`;
        };
        
        window.handleLogoError = function() {
            document.getElementById('mainLogo').style.display = 'none';
            document.getElementById('logoFallback').style.display = 'block';
        };
        
        // ================== MAIN FUNCTION ==================
        
        window.onload = async function() {
            try {
                // Cek apakah status success
                if (phpData.status !== 'Success') {
                    console.log('⏳ Bukan halaman sukses, menampilkan pending');
                    return; // Pending area sudah ditampilkan oleh PHP
                }
                
                console.log('✅ Deteksi sukses, generate key...');
                
                // Bersihkan nomor telepon
                const cleanPhone = phpData.phone ? phpData.phone.replace(/\D/g, '') : '';
                
                // Tampilkan status loading
                document.getElementById('waStatus').style.display = 'block';
                document.getElementById('waStatus').className = 'status-message';
                document.getElementById('waStatus').innerHTML = '⏳ Membuat key VIP...';
                
                // Generate key unik
                const fullKey = await generateUniqueKey();
                document.getElementById('keyDisplay').textContent = fullKey;
                
                // Simpan key ke Firebase
                await saveKeyToFirebase(fullKey, phpData.invoice, cleanPhone);
                
                // Kirim WA jika nomor tersedia
                if (cleanPhone && cleanPhone.length >= 10) {
                    await sendViaWhapi(cleanPhone, fullKey, phpData.invoice);
                } else {
                    document.getElementById('waStatus').className = 'status-message status-warning';
                    document.getElementById('waStatus').innerHTML = '⚠️ Nomor WA tidak tersedia. Key dapat disalin manual.';
                }
                
            } catch (error) {
                console.error('❌ ERROR:', error);
                alert('Error: ' + error.message);
            }
        };
    </script>
</body>
</html>
