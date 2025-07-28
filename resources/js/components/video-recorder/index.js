import videojs from 'video.js';
import 'videojs-record/dist/videojs.record.js';

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

        init() {
            this.initialized = true;
            this.$nextTick(() => {
                this.setupVideoJS();
            });
        },

        setupVideoJS() {
            this.videoElement = this.$refs.videoRecorder;
            if (!this.videoElement) {
                console.error('Video element not found');
                return;
            }

            const options = {
                controls: true,
                bigPlayButton: false,
                width: 320,
                height: 240,
                fluid: false,
                responsive: true,
                // Prevent VideoJS from interfering with modal events
                userActions: {
                    hotkeys: false,
                    doubleClick: false
                },
                plugins: {
                    record: {
                        audio: true,
                        video: true,
                        maxLength: 300, // 5 minutes
                        debug: false,
                        videoMimeType: 'video/webm;codecs=vp9,opus',
                        videoBitsPerSecond: 2500000
                    }
                }
            };

            this.player = videojs(this.videoElement, options, () => {
                console.log('VideoJS Record player ready');
            });

            // Set up event listeners
            this.player.on('startRecord', () => {
                console.log('Recording started');
                this.isRecording = true;
                this.hasRecording = false;
            });

            this.player.on('finishRecord', () => {
                console.log('Recording finished');
                this.isRecording = false;
                this.hasRecording = true;
                this.recordedBlob = this.player.recordedData;
            });

            this.player.on('error', (error) => {
                console.error('VideoJS Error:', error);
                this.showError('Recording failed. Please try again.');
            });

            this.player.on('deviceError', () => {
                console.error('Device error:', this.player.deviceErrorCode);
                this.showError('Camera/microphone access denied. Please check your browser settings.');
            });

            // Prevent VideoJS events from bubbling to region selection
            this.player.el().addEventListener('mousedown', (e) => {
                e.stopPropagation();
            });
            this.player.el().addEventListener('mousemove', (e) => {
                e.stopPropagation();
            });
            this.player.el().addEventListener('mouseup', (e) => {
                e.stopPropagation();
            });
            this.player.el().addEventListener('touchstart', (e) => {
                e.stopPropagation();
            });
            this.player.el().addEventListener('touchmove', (e) => {
                e.stopPropagation();
            });
            this.player.el().addEventListener('touchend', (e) => {
                e.stopPropagation();
            });
        },

        submitRecording() {
            if (!this.recordedBlob || this.isUploading) return;
            
            this.isUploading = true;
            this.uploadProgress = 0;
            
            // Simulate upload progress
            const progressInterval = setInterval(() => {
                this.uploadProgress += 20;
                if (this.uploadProgress >= 100) {
                    clearInterval(progressInterval);
                }
            }, 300);
            
            if (typeof this.submitCallback === 'function') {
                this.submitCallback(this.recordedBlob)
                    .then(() => {
                        clearInterval(progressInterval);
                        this.uploadProgress = 100;
                        
                        setTimeout(() => {
                            this.isUploading = false;
                            this.uploadProgress = 0;
                            this.resetRecording();
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
                        this.showError('Video submitted successfully!');
                        this.resetRecording();
                    }, 300);
                }, 2000);
            }
        },

        resetRecording() {
            if (this.player) {
                this.player.record().reset();
            }
            this.hasRecording = false;
            this.isRecording = false;
            this.recordedBlob = null;
        },

        showError(message) {
            const errorMessage = message || 'An unknown error occurred';
            console.error('Video Recorder:', errorMessage);
            
            // Only dispatch events after component is initialized to avoid Alpine init errors
            if (this.initialized && typeof window !== 'undefined' && window.dispatchEvent) {
                window.dispatchEvent(new CustomEvent('video-recorder:error', { 
                    detail: { message: errorMessage } 
                }));
            }
        },

        destroy() {
            if (this.player) {
                this.player.dispose();
                this.player = null;
            }
        }
    }
}