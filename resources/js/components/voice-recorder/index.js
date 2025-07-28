import WaveSurfer from 'wavesurfer.js';
import RecordPlugin from 'wavesurfer.js/dist/plugins/record.esm.js';

export default function recorder({ onSubmit = null } = {}) {
    return {
        // Core state
        isSupported: false,
        isRecording: false,
        hasRecording: false,
        isPlaying: false,
        submitCallback: onSubmit,
        
        // Upload state
        isUploading: false,
        uploadProgress: 0,
        
        // WaveSurfer instances
        wavesurfer: null,
        record: null,
        playbackWavesurfer: null,
        
        // Recording data
        recordedBlob: null,
        recordedUrl: null,
        currentDuration: 0,
        maxDuration: 180, // 3 minutes
        
        // Timer
        timer: null,
        
        init() {
            this.checkSupport();
            if (this.isSupported) {
                this.createWaveSurfer();
            }
        },
        
        checkSupport() {
            this.isSupported = !!(navigator.mediaDevices?.getUserMedia && window.MediaRecorder);
        },
        
        createWaveSurfer() {
            // Destroy previous instance
            if (this.wavesurfer) {
                this.wavesurfer.destroy();
            }
            
            const container = this.$refs.recordingWaveform;
            if (!container) {
                console.error('Recording waveform container not found');
                return;
            }
            
            // Check theme for colors
            const isDark = document.documentElement.classList.contains('dark');
            
            // Create WaveSurfer instance
            this.wavesurfer = WaveSurfer.create({
                container: container,
                height: 32,
                waveColor: isDark ? '#71717a' : '#d4d4d8', // zinc-500/300
                progressColor: '#10b981', // emerald-500
                cursorColor: isDark ? '#059669' : '#047857', // emerald-600/700
                barWidth: 2,
                barGap: 1,
                responsive: true,
                normalize: true,
            });
            
            // Initialize Record plugin
            this.record = this.wavesurfer.registerPlugin(
                RecordPlugin.create({
                    renderRecordedAudio: false,
                    scrollingWaveform: false,
                    continuousWaveform: true,
                    continuousWaveformDuration: this.maxDuration,
                })
            );
            
            // Set up event listeners
            this.record.on('record-start', () => {
                this.isRecording = true;
                this.startTimer();
                console.log('Recording started');
            });
            
            this.record.on('record-end', (blob) => {
                this.isRecording = false;
                this.stopTimer();
                this.recordedBlob = blob;
                this.recordedUrl = URL.createObjectURL(blob);
                this.hasRecording = true;
                this.createPlaybackWaveSurfer();
                console.log('Recording ended');
            });
            
            this.record.on('record-progress', (time) => {
                this.currentDuration = Math.floor(time / 1000);
                
                // Auto-stop at max duration
                if (this.currentDuration >= this.maxDuration) {
                    this.stopRecording();
                }
            });
            
            // Clean up on destroy
            this.wavesurfer.on('destroy', () => {
                console.log('Recording wavesurfer destroyed');
                this.isRecording = false;
                this.record = null;
            });
        },
        
        createPlaybackWaveSurfer() {
            if (!this.recordedUrl) return;
            
            const container = this.$refs.playbackWaveform;
            if (!container) {
                console.error('Playback waveform container not found');
                return;
            }
            
            // Clear container
            container.innerHTML = '';
            
            // Check theme for colors
            const isDark = document.documentElement.classList.contains('dark');
            
            // Create playback WaveSurfer
            this.playbackWavesurfer = WaveSurfer.create({
                container: container,
                height: 32,
                waveColor: isDark ? '#71717a' : '#d4d4d8', // zinc-500/300
                progressColor: '#0ea5e9', // sky-500
                cursorColor: isDark ? '#0284c7' : '#0369a1', // sky-600/700
                barWidth: 2,
                barGap: 1,
                responsive: true,
                normalize: true,
                url: this.recordedUrl,
            });
            
            this.playbackWavesurfer.on('ready', () => {
                console.log('Playback waveform ready');
            });
            
            this.playbackWavesurfer.on('play', () => {
                this.isPlaying = true;
            });
            
            this.playbackWavesurfer.on('pause', () => {
                this.isPlaying = false;
            });
            
            this.playbackWavesurfer.on('finish', () => {
                this.isPlaying = false;
            });
            
            this.playbackWavesurfer.on('destroy', () => {
                console.log('Playback wavesurfer destroyed');
                this.isPlaying = false;
            });
            
            this.playbackWavesurfer.on('error', (error) => {
                console.error('Playback waveform error:', error);
            });
        },
        
        async startRecording() {
            if (!this.isSupported || this.isRecording) return;
            
            try {
                await this.record.startRecording();
            } catch (error) {
                console.error('Failed to start recording:', error);
                this.showError('Failed to start recording. Please check microphone permissions.');
            }
        },
        
        stopRecording() {
            if (!this.isRecording) return;
            
            this.record.stopRecording();
        },
        
        togglePlayback() {
            if (!this.playbackWavesurfer || !this.hasRecording) return;
            
            this.playbackWavesurfer.playPause();
        },
        
        
        deleteRecording() {
            // Clean up URLs
            if (this.recordedUrl) {
                URL.revokeObjectURL(this.recordedUrl);
                this.recordedUrl = null;
            }
            
            // Reset state
            this.recordedBlob = null;
            this.hasRecording = false;
            this.isPlaying = false;
            this.currentDuration = 0;
            this.isUploading = false;
            this.uploadProgress = 0;
            
            // Destroy playback waveform
            if (this.playbackWavesurfer) {
                this.playbackWavesurfer.destroy();
                this.playbackWavesurfer = null;
            }
            
            // Clear containers
            if (this.$refs.playbackWaveform) {
                this.$refs.playbackWaveform.innerHTML = '';
            }
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
            }, 200);
            
            if (typeof this.submitCallback === 'function') {
                this.submitCallback(this.recordedBlob)
                    .then(() => {
                        clearInterval(progressInterval);
                        this.uploadProgress = 100;
                        
                        setTimeout(() => {
                            this.isUploading = false;
                            this.uploadProgress = 0;
                            this.deleteRecording();
                        }, 500);
                    })
                    .catch((error) => {
                        console.error('Submit error:', error);
                        clearInterval(progressInterval);
                        this.isUploading = false;
                        this.uploadProgress = 0;
                        this.showError('Failed to submit recording.');
                    });
            } else {
                // Simulate completion
                setTimeout(() => {
                    clearInterval(progressInterval);
                    this.uploadProgress = 100;
                    setTimeout(() => {
                        this.isUploading = false;
                        this.uploadProgress = 0;
                        this.showError('Recording submitted successfully!');
                        this.deleteRecording();
                    }, 300);
                }, 1000);
            }
        },
        
        startTimer() {
            this.currentDuration = 0;
            this.timer = setInterval(() => {
                this.currentDuration++;
                if (this.currentDuration >= this.maxDuration) {
                    this.stopRecording();
                }
            }, 1000);
        },
        
        stopTimer() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        
        formatTime(seconds) {
            if (!isFinite(seconds) || seconds < 0) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60).toString().padStart(2, '0');
            return `${mins}:${secs}`;
        },
        
        showError(message) {
            console.error('Voice Recorder:', message);
            // You can dispatch an event here to show a toast notification
            window.dispatchEvent(new CustomEvent('voice-recorder:error', {
                detail: { message }
            }));
        },
        
        destroy() {
            this.stopTimer();
            
            if (this.wavesurfer) {
                this.wavesurfer.destroy();
                this.wavesurfer = null;
            }
            
            if (this.playbackWavesurfer) {
                this.playbackWavesurfer.destroy();
                this.playbackWavesurfer = null;
            }
            
            if (this.recordedUrl) {
                URL.revokeObjectURL(this.recordedUrl);
            }
            
            this.record = null;
        }
    }
}