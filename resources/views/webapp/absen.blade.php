<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Herbigreen Absen</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --tg-theme-bg-color: #1a1b1e;
            --tg-theme-text-color: #ffffff;
            --tg-theme-button-color: #00ff88;
            --tg-theme-button-text-color: #000000;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        body {
            background-color: var(--tg-theme-bg-color);
            color: var(--tg-theme-text-color);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }
        
        /* Video Container */
        .video-container {
            position: relative;
            flex: 1;
            width: 100%;
            overflow: hidden;
            background: #000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #camera-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* Mirror effect for front camera */
        }
        
        /* Glassmorphism Overlays */
        .overlay-top {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }
        .overlay-top h2 {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(90deg, #00ff88, #00b8ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .overlay-top p {
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,0.8);
        }
        
        /* Controls */
        .controls {
            position: absolute;
            bottom: 40px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            z-index: 10;
        }
        
        /* The Shutter Button */
        .shutter-btn {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 4px solid var(--tg-theme-button-color);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
            backdrop-filter: blur(5px);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.4);
        }
        .shutter-btn .inner-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--tg-theme-button-color);
            transition: all 0.2s;
        }
        .shutter-btn:active {
            transform: scale(0.9);
        }
        .shutter-btn:active .inner-circle {
            transform: scale(0.85);
        }
        .shutter-btn.disabled {
            border-color: #555;
            box-shadow: none;
            pointer-events: none;
        }
        .shutter-btn.disabled .inner-circle {
            background: #555;
        }

        /* Status Indicator */
        .status-badge {
            margin-bottom: 20px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ffcc00;
            animation: pulse 1s infinite alternate;
        }
        @keyframes pulse {
            0% { transform: scale(0.8); opacity: 0.5; }
            100% { transform: scale(1.2); opacity: 1; }
        }
        
        .loading-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(10px);
            z-index: 50;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        .spinner {
            width: 40px; height: 40px;
            border: 4px solid rgba(255,255,255,0.1);
            border-top: 4px solid var(--tg-theme-button-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        
        /* Canvas for capturing */
        #capture-canvas {
            display: none;
        }
    </style>
</head>
<body>

    <div class="video-container">
        <video id="camera-preview" autoplay playsinline muted></video>
        
        <div class="overlay-top">
            <h2>Herbigreen HR</h2>
            <p id="user-greeting">Memuat sistem absen...</p>
        </div>
        
        <div class="controls">
            <div class="status-badge" id="status-badge">
                <div class="status-dot" id="status-dot"></div>
                <span id="status-text">Mencari Sinyal GPS...</span>
            </div>
            
            <div class="shutter-btn disabled" id="shutter-btn" onclick="takePhotoAndSubmit()">
                <div class="inner-circle"></div>
            </div>
        </div>
    </div>
    
    <div class="loading-overlay" id="loading">
        <div class="spinner"></div>
        <h3 style="font-weight: 600;">Memproses Absen...</h3>
        <p style="font-size: 12px; color: #aaa; margin-top: 5px;">Menganalisa wajah dan lokasi</p>
    </div>

    <canvas id="capture-canvas"></canvas>

    <script>
        const tg = window.Telegram.WebApp;
        tg.expand();
        tg.ready();
        
        // Setup UI colors from Telegram Theme
        if (tg.themeParams.bg_color) {
            document.documentElement.style.setProperty('--tg-theme-bg-color', tg.themeParams.bg_color);
            document.documentElement.style.setProperty('--tg-theme-text-color', tg.themeParams.text_color);
            document.documentElement.style.setProperty('--tg-theme-button-color', tg.themeParams.button_color);
            document.documentElement.style.setProperty('--tg-theme-button-text-color', tg.themeParams.button_text_color);
        }

        const video = document.getElementById('camera-preview');
        const canvas = document.getElementById('capture-canvas');
        const shutterBtn = document.getElementById('shutter-btn');
        const statusText = document.getElementById('status-text');
        const statusDot = document.getElementById('status-dot');
        const greeting = document.getElementById('user-greeting');
        const loading = document.getElementById('loading');
        
        let userLocation = null;
        const absenType = new URLSearchParams(window.location.search).get('type') || 'hadir';
        
        if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
            greeting.innerText = "Halo, " + tg.initDataUnsafe.user.first_name + " 👋";
        } else {
            greeting.innerText = "Siap Absen " + absenType.toUpperCase();
        }

        async function initCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false 
                });
                video.srcObject = stream;
            } catch (err) {
                tg.showAlert("Gagal buka kamera! Pastikan Telegram punya izin akses kamera HP kamu.");
            }
        }

        function initLocation() {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        statusText.innerText = "GPS Terkunci. Siap Absen!";
                        statusDot.style.background = "#00ff88";
                        statusDot.style.animation = "none";
                        shutterBtn.classList.remove('disabled');
                    },
                    (error) => {
                        let msg = "Gagal dapat GPS.";
                        if(error.code === 1) msg = "Akses GPS Ditolak! Nyalain GPS-nya bos.";
                        statusText.innerText = msg;
                        statusDot.style.background = "#ff3333";
                        tg.showAlert(msg);
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else {
                tg.showAlert("Browser ini tidak mendukung GPS.");
            }
        }

        async function takePhotoAndSubmit() {
            if (shutterBtn.classList.contains('disabled')) return;
            if (!userLocation) {
                tg.showAlert("Tunggu lokasi GPS terkunci dulu!");
                return;
            }

            loading.classList.add('active');
            
            // Draw video frame to canvas
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            
            // Mirror canvas because front camera is mirrored
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Get base64 JPEG
            const photoData = canvas.toDataURL('image/jpeg', 0.8);

            // Prepare payload
            const payload = {
                initData: tg.initData,
                type: absenType,
                latitude: userLocation.lat,
                longitude: userLocation.lng,
                photo: photoData,
                uid: new URLSearchParams(window.location.search).get('uid')
            };

            try {
                const response = await fetch('/api/webapp/submit-absen', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                
                if (result.status) {
                    tg.HapticFeedback.notificationOccurred('success');
                    tg.close();
                } else {
                    loading.classList.remove('active');
                    tg.HapticFeedback.notificationOccurred('error');
                    tg.showAlert(result.message || "Absen Ditolak!");
                }
            } catch (err) {
                loading.classList.remove('active');
                tg.showAlert("Terjadi kesalahan server: " + err.message);
            }
        }

        // Initialize
        initCamera();
        initLocation();
        
        // Haptic feedback for init
        tg.HapticFeedback.impactOccurred('light');
    </script>
</body>
</html>
