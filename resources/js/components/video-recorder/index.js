import RecordRTC from 'recordrtc';
import videojs from 'video.js';
import Record from 'videojs-record/dist/videojs.record.js';


export default function videoRecorder({ onSubmit = null } = {}) {
    return {
        player: null,
        isRecording: false,
        hasRecording: false,
        isUploading: false,
        uploadProgress: 0,
        submitCallback: onSubmit,
        recordedBlob: null,
        videoElement: null,
        initialized: false,
        recordPlugin: Record,
        rtcPlugin: RecordRTC,
        currentTime: 0,
        timer: null,
        deviceReady: false,

        init() {
            this.initialized = true;
            // Wait for DOM refs to be available (like video-annotation does)
            this.$nextTick(() => {
                this.videoElement = this.$refs.videoRecorder;
                console.log(this.videoElement);

                if (this.videoElement) {
                    this.setupVideoJS();
                }
            });
        },

        setupVideoJS() {
            if (!this.videoElement) {
                console.error('Video element not found');
                return;
            }

            // Check if element is in DOM
            // if (!document.contains(this.videoElement)) {
            //     console.error('Video element not in DOM');
            //     return;
            // }

            // Clear any existing player
            // if (this.player) {
            //     this.player.dispose();
            //     this.player = null;
            // }

            // // Check if videojs instance already exists on this element
            // if (this.videoElement.player) {
            //     this.videoElement.player.dispose();
            //     this.videoElement.player = null;
            // }

            // Detect if mobile device
            const isMobile = window.innerWidth <= 768;

            const options = {
                controls: false,
                bigPlayButton: false,
                fluid: true,
                responsive: true,
                aspectRatio: isMobile ? '9:16' : '16:9', // Story ratio on mobile, landscape on desktop
                plugins: {
                    record: {
                        image: false,
                        audio: false,
                        video: true,
                        maxLength: 30,
                        videoMimeType: 'video/webm;codecs=vp8',
                        displayMilliseconds: true,
                        debug: false
                    }
                }
            };

            this.player = videojs('video-recorder', options, () => {
                console.log('VideoJS Record player ready');
                console.log('Using video.js', videojs.VERSION);
                if (videojs.getPluginVersion) {
                    console.log('with videojs-record', videojs.getPluginVersion('record'));
                }
            });

            // Set up event listeners
            this.player.on('deviceReady', () => {
                console.log('Device ready');
                this.deviceReady = true;
            });

            this.player.on('startRecord', () => {
                console.log('Recording started');
                this.isRecording = true;
                this.hasRecording = false;
                this.currentTime = 0;
                this.startTimer();
            });

            this.player.on('stopRecord', () => {
                console.log('Recording stopped');
                this.stopTimer();
            });

            this.player.on('finishRecord', () => {
                console.log('Recording finished');
                this.isRecording = false;
                this.hasRecording = true;
                this.recordedBlob = this.player.recordedData;
                this.stopTimer();
                console.log(this.recordedBlob.type);
                console.log(URL.createObjectURL(this.recordedBlob));
                this.player.src({
                    type: 'video/webm',
                    src: URL.createObjectURL(this.recordedBlob)
                });
                this.player.controls(true);
                // Optional: show big play button


                // Optional: autoplay preview
                this.player.ready(() => {
                    this.player.play();
                });

            });

            this.player.on('error', (error) => {
                console.error('VideoJS Error:', error);
            });

            this.player.on('deviceError', () => {
                console.error('Device error:', this.player.deviceErrorCode);
            });

            // Prevent VideoJS events from bubbling
            const playerEl = this.player.el();
            if (playerEl) {
                ['mousedown', 'mousemove', 'mouseup', 'touchstart', 'touchmove', 'touchend', 'click'].forEach(event => {
                    playerEl.addEventListener(event, (e) => {
                        e.stopPropagation();
                    });
                });
            }
        },

        // Custom control methods
        startRecording() {
            if (this.player && this.player.record && !this.isRecording) {
                this.player.record().start();
            }
        },

        stopRecording() {
            if (this.player && this.player.record && this.isRecording) {
                this.player.record().stop();
            }
        },

        getRecordingTime() {
            if (this.player && this.player.record) {
                return this.player.record().getCurrentTime() || 0;
            }
            return 0;
        },

        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60).toString().padStart(2, '0');
            return `${mins}:${secs}`;
        },

        startTimer() {
            this.currentTime = 0;
            this.timer = setInterval(() => {
                this.currentTime++;
            }, 1000);
        },

        stopTimer() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        async submitRecording() {
            if (!this.recordedBlob || this.isUploading) return;

            this.isUploading = true;
            this.uploadProgress = 0;

            try {
                if (typeof this.submitCallback === 'function') {
                    // Use Livewire's $wire.upload for proper file handling
                    const result = await this.submitCallback(this.recordedBlob);
                    
                    this.uploadProgress = 100;
                    setTimeout(() => {
                        this.isUploading = false;
                        this.uploadProgress = 0;
                        this.resetRecording();
                    }, 500);
                    
                    return result;
                } else {
                    console.warn('No submit callback provided');
                    this.isUploading = false;
                }
            } catch (error) {
                console.error('Submit error:', error);
                this.isUploading = false;
                this.uploadProgress = 0;
                
                // Dispatch error event for parent to handle
                this.$dispatch('video-upload-error', { error: error.message });
            }
        },

        resetRecording() {
            if (this.player) {
                this.player.record().reset();
            }
            this.hasRecording = false;
            this.isRecording = false;
            this.recordedBlob = null;
            this.currentTime = 0;
            this.stopTimer();
        },


        destroy() {
            this.stopTimer();
            if (this.player) {
                this.player.dispose();
                this.player = null;
            }
        }
    }
}
