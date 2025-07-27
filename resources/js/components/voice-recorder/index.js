import WaveSurfer from 'wavesurfer.js';

export default function recorder({ onSubmit = null } = {}) {
    return {
        isSupported: false,
        isRecording: false,
        isPlaying: false,
        hasRecording: false,
        browserSupport: {
            mediaRecorder: false,
            getUserMedia: false,
            webAudio: false
        },

        //callback
        submitCallback: onSubmit,

        //player
        wavesurfer: null,
        playerLoaded: false,
        playbackCurrentTime: 0,
        playbackUpdateInterval: null,

        // Upload state
        isUploading: false,
        uploadProgress: 0,

        // Audio data
        mediaRecorder: null,
        audioChunks: [],
        audioBlob: null,
        audioUrl: null,
        stream: null,

        //Record Monitoring
        audioContext: null,
        analyser: null,
        volumeThreshold: 0.02,
        showingWaves: false,
        volumeTimer: null,
        volumeLevel: 0,
        // Timing
        maxDuration: 60 * 3,
        currentDuration: 0,
        recordingDuration: 0,
        timer: null,
        wakeLock: null, // Add this for mobile support

        init() {
            this.checkSupport();
            this.setupMobileOptimizations();
        },

        handleMainButtonClick() {
            if (!this.isSupported) return;

            if (!this.isRecording && !this.hasRecording) {
                this.startRecording();
            } else if (this.isRecording) {
                this.stopRecording();
            } else if (this.hasRecording) {
                this.startRecording();
            }
        },

        getButtonText() {
            if (!this.isSupported) return 'Not Supported';
            if (this.isRecording) return 'Stop Recording';
            if (this.hasRecording) return 'Play Recording';
            return 'Start Recording';
        },

        checkSupport() {
            this.browserSupport.mediaRecorder = !!window.MediaRecorder;
            this.browserSupport.getUserMedia = !!(navigator.mediaDevices?.getUserMedia);
            this.browserSupport.webAudio = !!(window.AudioContext || window.webkitAudioContext);
            this.isSupported = this.browserSupport.mediaRecorder && this.browserSupport.getUserMedia;
        },

        setupMobileOptimizations() {
            if (this.isIOS()) {
                document.addEventListener('touchstart', () => { }, { passive: true });
            }
            window.addEventListener('orientationchange', () => {
                setTimeout(() => {
                    if (this.$refs.audioPlayer) {
                        this.$refs.audioPlayer.load();
                    }
                }, 500);
            });
        },

        isIOS() {
            return /iPad|iPhone|iPod/.test(navigator.userAgent);
        },

        isAndroid() {
            return /Android/.test(navigator.userAgent);
        },

        isMobile() {
            return this.isIOS() || this.isAndroid() || /Mobi|Mobile/.test(navigator.userAgent);
        },

        async getUserMedia() {
            const constraints = {
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: false,
                    ...(this.isMobile() && { sampleRate: 44100, channelCount: 1 })
                }
            };
            return navigator.mediaDevices.getUserMedia(constraints);
        },

        getSupportedMimeType() {
            const mimeTypes = [
                'audio/webm;codecs=opus', 'audio/webm',
                'audio/ogg;codecs=opus', 'audio/ogg',
                'audio/mpeg', 'audio/wav'
            ];
            return mimeTypes.find(m => MediaRecorder.isTypeSupported(m)) || '';
        },

        async startRecording() {
            if (!this.isSupported) {
                this.showError('Recording not supported');
                return;
            }

            try {
                const stream = await Promise.race([
                    this.getUserMedia(),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('Permission timeout')), 10000))
                ]);
                this.stream = stream;

                const mimeType = this.getSupportedMimeType();
                const options = mimeType ? { mimeType } : {};

                if (this.isMobile()) options.audioBitsPerSecond = 128000;

                this.mediaRecorder = new MediaRecorder(stream, options);
                this.audioChunks = [];
                this.currentDuration = 0;
                this.isRecording = true;

                this.mediaRecorder.ondataavailable = e => {
                    if (e.data && e.data.size > 0) this.audioChunks.push(e.data);
                };
                this.mediaRecorder.onstop = () => this.finalizeRecording();
                this.mediaRecorder.onerror = e => {
                    console.error('MediaRecorder error:', e);
                    this.showError('Recording failed');
                    this.resetState();
                };

                this.mediaRecorder.start(this.isMobile() ? 1000 : 100);
                this.startTimer();
                this.startVolumeMonitoring(stream);

                if ('wakeLock' in navigator) {
                    try {
                        this.wakeLock = await navigator.wakeLock.request('screen');
                    } catch { }
                }
            } catch (err) {
                let msg = 'Could not access microphone.';
                if (err.name === 'NotAllowedError') msg = 'Microphone permission denied.';
                if (err.name === 'NotFoundError') msg = 'No microphone found.';
                if (err.name === 'NotReadableError') msg = 'Microphone is in use.';
                if (err.message === 'Permission timeout') msg = 'Permission request timed out.';
                this.showError(msg);
                this.resetState();
            }
        },

        stopRecording() {
            if (this.mediaRecorder && this.isRecording) {
                this.mediaRecorder.stop();
                this.isRecording = false;
                this.stopTimer();
                if (this.wakeLock) {
                    this.wakeLock.release().catch(() => { });
                    this.wakeLock = null;
                }
                this.stopVolumeMonitoring();
            }
        },

        finalizeRecording() {
            if (!this.audioChunks.length) return;

            try {
                const type = this.mediaRecorder?.mimeType || 'audio/webm';
                this.audioBlob = new Blob(this.audioChunks, { type });
                this.audioUrl = URL.createObjectURL(this.audioBlob);
                this.recordingDuration = this.currentDuration;
                this.hasRecording = true;
                this.setupAudioElement();
            } catch (err) {
                this.showError('Failed to process audio');
                console.error(err);
            }

            this.stopAllTracks();
        },

        startTimer() {
            this.timer = setInterval(() => {
                this.currentDuration++;
                if (this.currentDuration >= this.maxDuration) {
                    this.stopRecording();
                }
            }, 1000);
        },

        stopTimer() {
            if (this.timer) clearInterval(this.timer);
            this.timer = null;
        },

        setupAudioElement() {
            // This will trigger wavesurfer initialization in the blade template
            this.playerLoaded = false;
        },

        async initWavesurfer() {
            if (!this.audioUrl || this.wavesurfer) return;

            try {
                const container = this.$refs.playbackWaveform;
                
                if (!container) {
                    console.error('Playback waveform container not found');
                    return;
                }

                // Clear loading state
                container.innerHTML = '';

                // Check theme for colors
                const isDark = document.documentElement.classList.contains('dark');
                
                this.wavesurfer = WaveSurfer.create({
                    container: container,
                    height: 24,
                    waveColor: isDark ? '#71717a' : '#d4d4d8', // zinc-500 dark, zinc-300 light
                    progressColor: '#0ea5e9', // sky-500
                    cursorColor: isDark ? '#0284c7' : '#0369a1', // sky-600 dark, sky-700 light
                    barWidth: 1.5,
                    barGap: 0.5,
                    responsive: true,
                    normalize: true,
                    backend: 'WebAudio'
                });

                // Setup event listeners BEFORE loading
                this.wavesurfer.on('ready', () => {
                    this.playerLoaded = true;
                    this.recordingDuration = this.wavesurfer.getDuration();
                    console.log('Wavesurfer ready, playerLoaded:', this.playerLoaded);
                });

                this.wavesurfer.on('play', () => {
                    this.isPlaying = true;
                    this.startPlaybackTimeUpdates();
                });

                this.wavesurfer.on('pause', () => {
                    this.isPlaying = false;
                    this.stopPlaybackTimeUpdates();
                });

                this.wavesurfer.on('finish', () => {
                    this.isPlaying = false;
                    this.playbackCurrentTime = 0;
                    this.stopPlaybackTimeUpdates();
                });

                this.wavesurfer.on('error', (error) => {
                    console.error('Wavesurfer error:', error);
                    this.showError('Failed to load audio');
                });

                // Load audio after setting up listeners
                await this.wavesurfer.load(this.audioUrl);

            } catch (error) {
                console.error('Failed to initialize Wavesurfer:', error);
                this.showError('Failed to initialize audio player');
            }
        },

        togglePlayback() {
            if (!this.wavesurfer) return;

            if (this.isPlaying) {
                this.wavesurfer.pause();
            } else {
                this.wavesurfer.play();
            }
        },

        startPlaybackTimeUpdates() {
            if (this.playbackUpdateInterval) {
                clearInterval(this.playbackUpdateInterval);
            }

            this.playbackUpdateInterval = setInterval(() => {
                if (this.wavesurfer && this.wavesurfer.isPlaying()) {
                    this.playbackCurrentTime = this.wavesurfer.getCurrentTime();
                }
            }, 100);
        },

        stopPlaybackTimeUpdates() {
            if (this.playbackUpdateInterval) {
                clearInterval(this.playbackUpdateInterval);
                this.playbackUpdateInterval = null;
            }
        },


        deleteRecording() {
            if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
            this.isUploading = false;
            this.uploadProgress = 0;
            this.resetState();
        },

        recordAgain() {
            this.deleteRecording();
            setTimeout(() => this.startRecording(), 100);
        },

        submitRecording() {
            if (!this.audioBlob || this.isUploading) return;
            console.log('Voice recorder: Starting submission');
            
            this.isUploading = true;
            this.uploadProgress = 0;
            
            // Simulate upload progress
            const progressInterval = setInterval(() => {
                this.uploadProgress += 20;
                if (this.uploadProgress >= 100) {
                    clearInterval(progressInterval);
                }
            }, 200);
            
            if (typeof this.submitCallback === 'function') {
                console.log('Voice recorder: Calling submit callback');
                this.submitCallback(this.audioBlob)
                    .then((result) => {
                        console.log('Voice recorder: Callback completed', result);
                        clearInterval(progressInterval);
                        this.uploadProgress = 100;
                        
                        // Add a small delay to allow UI updates to be visible
                        setTimeout(() => {
                            console.log('Voice recorder: Cleaning up recording');
                            this.isUploading = false;
                            this.uploadProgress = 0;
                            this.deleteRecording();
                        }, 500);
                    })
                    .catch((e) => {
                        console.error('Submit error:', e);
                        clearInterval(progressInterval);
                        this.isUploading = false;
                        this.uploadProgress = 0;
                        this.showError('Failed to submit recording.');
                    });
            } else {
                // Simulate upload completion
                setTimeout(() => {
                    clearInterval(progressInterval);
                    this.uploadProgress = 100;
                    setTimeout(() => {
                        this.isUploading = false;
                        this.uploadProgress = 0;
                        alert(`Recording submitted!`);
                        this.deleteRecording();
                    }, 300);
                }, 1000);
            }
        },

        updateMaxDuration() {
            this.maxDuration = parseInt(this.maxDuration) || 60;
        },

        formatTime(sec) {
            if (!isFinite(sec) || sec < 0) return '0:00';
            const m = Math.floor(sec / 60);
            const s = Math.floor(sec % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },

        stopAllTracks() {
            this.stream?.getTracks().forEach(track => track.stop());
        },

        showError(msg) {
            console.error('Voice Recorder Error:', msg);
        },

        resetState() {
            this.cleanupWavesurfer();
            this.playerLoaded = false;
            this.isRecording = false;
            this.isPlaying = false;
            this.hasRecording = false;
            this.isUploading = false;
            this.uploadProgress = 0;
            this.currentDuration = 0;
            this.recordingDuration = 0;
            this.playbackCurrentTime = 0;
            this.audioChunks = [];
            this.audioBlob = null;

            if (this.audioUrl) {
                URL.revokeObjectURL(this.audioUrl);
                this.audioUrl = null;
            }

            this.stopTimer();
            this.stopPlaybackTimeUpdates();
            this.mediaRecorder = null;

            this.stopAllTracks();
            this.stream = null;
            
            this.stopVolumeMonitoring();
        },

        cleanupWavesurfer() {
            if (this.wavesurfer) {
                this.wavesurfer.destroy();
                this.wavesurfer = null;
            }
            this.stopPlaybackTimeUpdates();
        },

        startVolumeMonitoring(stream) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const source = this.audioContext.createMediaStreamSource(stream);
            this.analyser = this.audioContext.createAnalyser();
            
            // Enhanced analyzer settings for better sensitivity
            this.analyser.fftSize = 512;
            this.analyser.smoothingTimeConstant = 0.3;
            
            source.connect(this.analyser);

            const bufferLength = this.analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);

            const checkVolume = () => {
                this.analyser.getByteFrequencyData(dataArray);

                // Calculate RMS (Root Mean Square) for better volume detection
                let sum = 0;
                for (let i = 0; i < bufferLength; i++) {
                    sum += dataArray[i] * dataArray[i];
                }
                const rms = Math.sqrt(sum / bufferLength);
                
                // Normalize and enhance sensitivity
                let normalizedVolume = Math.min(rms / 128, 1);
                
                // Apply logarithmic scaling for better visual response
                normalizedVolume = Math.pow(normalizedVolume, 0.5);
                
                // Enhanced smoothing with faster response to peaks
                if (normalizedVolume > this.volumeLevel) {
                    this.volumeLevel = this.volumeLevel * 0.6 + normalizedVolume * 0.4;
                } else {
                    this.volumeLevel = this.volumeLevel * 0.85 + normalizedVolume * 0.15;
                }

                this.volumeTimer = requestAnimationFrame(checkVolume);
            };

            checkVolume();
        },

        stopVolumeMonitoring() {
            if (this.volumeTimer) cancelAnimationFrame(this.volumeTimer);
            this.volumeTimer = null;
            if (this.audioContext) {
                this.audioContext.close();
                this.audioContext = null;
            }
        }
    }
}