import WaveSurfer from 'wavesurfer.js';
import { recorderManager } from '../audioplayer/wavesurfer-manager.js';

export default function recorder({ onSubmit = null, instanceKey = null, maxDuration = 30, size = 'sm' } = {}) {
    return {
        // Core state
        isSupported: false,
        isInitialized: false,
        isRecording: false,
        hasRecording: false,
        isPlaying: false,
        submitCallback: onSubmit,
        instanceKey: instanceKey,

        // Upload state
        isUploading: false,
        uploadProgress: 0,

        // Only keep playback reference for UI state
        playbackWavesurfer: null,

        // Prevent multiple simultaneous initializations
        isInitializing: false,

        // Recording data
        recordedBlob: null,
        recordedUrl: null,
        currentDuration: 0,
        maxDuration: maxDuration, // 3 minutes
        size: size,

        // Timer
        timer: null,

        async init() {
            this.checkSupport();
            if (this.isSupported && this.instanceKey) {
                await this.createWaveSurfer();
                // Set up Livewire event listeners after DOM is ready
                this.$nextTick(() => {
                    this.setupLivewireListeners();
                });
            }
        },

        checkSupport() {
            this.isSupported = !!(navigator.mediaDevices?.getUserMedia && window.MediaRecorder);
        },

        async createWaveSurfer() {
            // Prevent multiple simultaneous calls
            if (this.isInitializing) {
                console.log('⏳ Already initializing, skipping...');
                return;
            }

            try {
                console.log('🎙️ createWaveSurfer called, instanceKey:', this.instanceKey);
                this.isInitializing = true;
                // Mark as not initialized while setting up
                this.isInitialized = false;

                const container = this.$refs.recordingWaveform;
                if (!container) {
                    console.error('Recording waveform container not found');
                    this.isInitializing = false;
                    return;
                }

                console.log('📦 Container found, existing children:', container.children.length);

                // Destroy existing instance first if it exists
                if (this.instanceKey) {
                    console.log('🗑️ Destroying existing recorder instance...');
                    recorderManager.destroy(this.instanceKey);

                    // Wait a moment for cleanup to complete
                    await new Promise(resolve => setTimeout(resolve, 50));

                    // Manually clear any remaining DOM elements that weren't cleaned up
                    if (container.children.length > 0) {
                        console.log('🧹 Manually clearing remaining DOM elements...');
                        while (container.firstChild) {
                            container.removeChild(container.firstChild);
                        }
                    }

                    console.log('✨ Container after cleanup, children:', container.children.length);
                }

                // Check theme for colors
                const isDark = document.documentElement.classList.contains('dark');

                // Map size to height
                const getWaveformHeight = (size) => {
                    switch (size) {
                        case 'sm': return 24; // h-6
                        case 'lg': return 40; // h-10
                        case 'md':
                        default: return 32; // h-8
                    }
                };

                const options = {
                    height: getWaveformHeight(this.size),
                    waveColor: isDark ? '#71717a' : '#d4d4d8', // zinc-500/300
                    progressColor: '#10b981', // emerald-500
                    cursorColor: isDark ? '#059669' : '#047857', // emerald-600/700
                    barWidth: 2,
                    barGap: 1,
                    responsive: true,
                    normalize: true,
                    hideScrollbar: true,
                    interact: false, // Disable interaction during recording
                    maxDuration: this.maxDuration,
                };

                // Always initialize - let WaveSurfer handle container visibility
                console.log('🔧 Initializing recorder with manager...');
                await recorderManager.init(
                    this.instanceKey,
                    container,
                    null,
                    null,
                    options
                );

                // Set up event listeners via manager (manager should handle duplicates internally)
                console.log('🎧 Setting up event listeners...');
                const recordPlugin = recorderManager.getRecord(this.instanceKey);
                if (recordPlugin) {
                    // Remove any existing listeners first to prevent duplicates
                    recordPlugin.unAll();

                    recordPlugin.on('record-start', () => {
                        this.isRecording = true;
                        this.startTimer();
                        console.log('📹 Recording started - isRecording:', this.isRecording);

                        // Check if the container is visible in the DOM
                        setTimeout(() => {
                            const container = this.$refs.recordingWaveform;
                            if (container) {
                                const rect = container.getBoundingClientRect();
                                console.log('📐 Container visibility - width:', rect.width, 'height:', rect.height, 'visible:', rect.width > 0 && rect.height > 0);
                                console.log('🎨 Container computed display:', window.getComputedStyle(container).display);
                                console.log('👁️ Container parent visibility:', window.getComputedStyle(container.parentElement).display);

                                // Force WaveSurfer to redraw if container became visible
                                const wavesurfer = recorderManager.getWavesurfer(this.instanceKey);
                                if (wavesurfer && rect.width > 0 && rect.height > 0) {
                                    console.log('🔄 Forcing WaveSurfer redraw...');
                                    try {
                                        wavesurfer.drawer.fireEvent('redraw');
                                    } catch (error) {
                                        console.log('⚠️ Could not force redraw:', error.message);
                                    }
                                }
                            }
                        }, 100);
                    });

                    recordPlugin.on('record-end', (blob) => {
                        this.isRecording = false;
                        this.stopTimer();

                        if (blob && blob.size > 0) {
                            this.recordedBlob = blob;
                            this.recordedUrl = URL.createObjectURL(blob);
                            this.hasRecording = true;
                            console.log('Recording ended successfully, blob size:', blob.size);

                            // Create playback wavesurfer for the recorded audio
                            this.createPlaybackWaveSurfer();
                        } else {
                            console.error('Recording ended with empty or invalid blob');
                            this.showError('Recording failed - no audio data captured');
                        }
                    });

                    recordPlugin.on('record-progress', (time) => {
                        this.currentDuration = Math.floor(time / 1000);
                        console.log('📊 Record progress:', time, 'ms, duration:', this.currentDuration, 's');

                        // Auto-stop at max duration
                        if (this.currentDuration >= this.maxDuration) {
                            this.stopRecording();
                        }
                    });

                    recordPlugin.on('record-error', (error) => {
                        console.error('Recording error:', error);
                        this.isRecording = false;
                        this.stopTimer();
                        this.showError('Recording failed: ' + error.message);
                    });

                    // Mark as initialized once everything is set up
                    this.isInitialized = true;
                    console.log('✅ WaveSurfer recording initialized successfully');
                } else {
                    console.error('Failed to get record plugin from manager');
                    this.showError('Failed to initialize recording plugin');
                }

                // Global error handler via manager
                const wavesurfer = recorderManager.getWavesurfer(this.instanceKey);
                if (wavesurfer) {
                    wavesurfer.unAll(); // Clear all existing listeners
                    wavesurfer.on('error', (error) => {
                        console.error('WaveSurfer error:', error);
                        this.showError('Audio system error: ' + error.message);
                    });
                }

            } catch (error) {
                console.error('Failed to create WaveSurfer instance:', error);
                this.showError('Failed to initialize audio recorder');
                this.isInitialized = false;
            } finally {
                // Always reset the initializing flag
                this.isInitializing = false;
            }
        },

        async createPlaybackWaveSurfer() {
            if (!this.recordedUrl || !this.instanceKey) return;

            try {
                const container = this.$refs.playbackWaveform;
                if (!container) {
                    console.error('Playback waveform container not found');
                    return;
                }

                // Destroy existing playback instance first
                if (this.playbackWavesurfer) {
                    try {
                        this.playbackWavesurfer.destroy();
                    } catch (error) {
                        console.warn('Error destroying previous playback wavesurfer:', error);
                    }
                    this.playbackWavesurfer = null;
                }

                // Check theme for colors
                const isDark = document.documentElement.classList.contains('dark');

                // Map size to height
                const getWaveformHeight = (size) => {
                    switch (size) {
                        case 'sm': return 24; // h-6
                        case 'lg': return 40; // h-10
                        case 'md':
                        default: return 32; // h-8
                    }
                };

                // Create isolated wavesurfer instance for recorder playback
                this.playbackWavesurfer = WaveSurfer.create({
                    container: container,
                    height: getWaveformHeight(this.size),
                    waveColor: isDark ? '#71717a' : '#d4d4d8', // zinc-500/300
                    progressColor: '#0ea5e9', // sky-500
                    cursorColor: isDark ? '#0284c7' : '#0369a1', // sky-600/700
                    barWidth: 2,
                    barGap: 1,
                    responsive: true,
                    normalize: true,
                    hideScrollbar: true,
                });

                // Set up playback event listeners
                this.playbackWavesurfer.on('play', () => {
                    this.isPlaying = true;
                });

                this.playbackWavesurfer.on('pause', () => {
                    this.isPlaying = false;
                });

                this.playbackWavesurfer.on('finish', () => {
                    this.isPlaying = false;
                });

                this.playbackWavesurfer.on('error', (error) => {
                    console.error('Playback error:', error);
                    this.showError('Playback error: ' + error.message);
                });

                // Load the recorded audio
                await this.playbackWavesurfer.load(this.recordedUrl);

            } catch (error) {
                console.error('Failed to create playback WaveSurfer:', error);
                this.showError('Failed to create audio preview');
            }
        },

        async startRecording() {
            console.log('🔴 startRecording called - Fresh initialization approach');

            if (!this.isSupported || this.isRecording || !this.instanceKey) {
                return;
            }

            try {
                // Always create fresh WaveSurfer + RecordPlugin on each record click
                console.log('🔄 Creating fresh WaveSurfer instance for recording...');
                await this.createWaveSurfer();

                if (!this.isInitialized) {
                    this.showError('Failed to initialize recorder');
                    return;
                }

                // Start recording with the fresh instance
                const recordPlugin = recorderManager.getRecord(this.instanceKey);
                if (recordPlugin) {
                    console.log('🎬 Starting recording with fresh plugin...');
                    await recordPlugin.startRecording();
                } else {
                    this.showError('Recording plugin not found');
                }
            } catch (error) {
                console.error('Failed to start recording:', error);
                this.showError('Failed to start recording. Please check microphone permissions.');
            }
        },

        stopRecording() {
            if (!this.isRecording || !this.instanceKey) return;

            const recordPlugin = recorderManager.getRecord(this.instanceKey);
            if (recordPlugin) {
                recordPlugin.stopRecording();
            }
        },

        async togglePlayback() {
            if (!this.hasRecording) return;

            // Create playback wavesurfer if it doesn't exist
            if (!this.playbackWavesurfer) {
                await this.createPlaybackWaveSurfer();
            }

            // Toggle playback using our isolated instance
            if (this.playbackWavesurfer) {
                this.playbackWavesurfer.playPause();
            }
        },


        deleteRecording() {
            // Stop playback first if it's currently playing
            if (this.isPlaying && this.playbackWavesurfer) {
                try {
                    this.playbackWavesurfer.pause();
                } catch (error) {
                    console.warn('Error pausing playback during delete:', error);
                }
            }

            // Destroy isolated playback wavesurfer instance
            if (this.playbackWavesurfer) {
                try {
                    this.playbackWavesurfer.destroy();
                } catch (error) {
                    console.warn('Error destroying playback wavesurfer:', error);
                }
                this.playbackWavesurfer = null;
            }

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

            // Note: Don't clear containers with innerHTML - WaveSurfer manages its own DOM
        },

        async submitRecording() {
            if (!this.recordedBlob || this.isUploading) return;

            this.isUploading = true;
            this.uploadProgress = 0;

            try {
                if (typeof this.submitCallback === 'function') {
                    // Create File instance from recorded blob
                    const file = new File([this.recordedBlob], 'voice-note.webm', {
                        type: this.recordedBlob.type || 'audio/webm'
                    });

                    // Use Livewire's upload method with proper callbacks

                    this.$wire.upload(
                        'audio',
                        file,
                        // Success callback
                        (uploadedFilename) => {
                            console.log('✅ Upload completed:', uploadedFilename);
                            this.uploadProgress = 100;
                            this.$wire.finalizeNoteUpload(uploadedFilename);
                        },
                        // Error callback
                        (error) => {
                            console.error('❌ Upload error:', error);
                            reject(new Error(error));
                        },
                        // Progress callback
                        (event) => {
                            this.uploadProgress = Math.round(event.detail.progress);
                            console.log('📈 Upload progress:', this.uploadProgress + '%');
                        },
                        // Cancel callback
                        () => {
                            console.log('⚠️ Upload cancelled');
                            reject(new Error('Upload cancelled'));
                        }
                    );


                    // Reset after successful upload
                    setTimeout(() => {
                        this.resetAfterUpload();
                    }, 500);

                } else {
                    console.warn('No submit callback provided');
                    this.resetAfterUpload();
                }
            } catch (error) {
                console.error('Submit error:', error);
                this.isUploading = false;
                this.uploadProgress = 0;
                this.showError('Failed to submit recording: ' + error.message);
            }
        },

        resetAfterUpload() {
            console.log('🧹 Resetting recorder after successful upload...');

            this.isUploading = false;
            this.uploadProgress = 0;

            // Clean up current recording
            this.deleteRecording();

            // Completely destroy recorder instance - fresh one will be created on next record click
            if (this.instanceKey) {
                console.log('💥 Destroying recorder instance completely...');
                recorderManager.destroy(this.instanceKey);
                this.isInitialized = false;
            }

            console.log('✅ Reset complete - ready for fresh recording');
        },

        resetWaveSurfer() {
            // This method is deprecated - we handle reset in createWaveSurfer now
            // Just reset the state without touching WaveSurfer instances
            this.isRecording = false;
            this.currentDuration = 0;
            this.stopTimer();
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

        // Listen for Livewire upload events to provide better UX
        setupLivewireListeners() {
            if (this.$refs.recordingWaveform && this.$refs.recordingWaveform.closest('[wire\\:id]')) {
                const livewireComponent = this.$refs.recordingWaveform.closest('[wire\\:id]');

                // Store references to avoid duplicate listeners
                if (!this._livewireListenersAdded) {
                    this._uploadFinishHandler = () => {
                        console.log('Livewire upload finished, resetting recorder');
                        // Don't call resetAfterUpload here - it's already called in submitRecording
                        // Just ensure we're in clean state
                        if (this.isUploading) {
                            this.isUploading = false;
                            this.uploadProgress = 0;
                        }
                    };

                    this._uploadErrorHandler = (event) => {
                        console.error('Livewire upload error:', event.detail);
                        this.isUploading = false;
                        this.uploadProgress = 0;
                        this.showError('Upload failed: ' + (event.detail?.message || 'Unknown error'));
                    };

                    // Add voice-note:canceled listener to reset recorder state
                    this._voiceNoteCanceledHandler = () => {
                        console.log('Voice note canceled, resetting recorder completely');
                        // Stop any ongoing recording
                        if (this.isRecording) {
                            this.stopRecording();
                        }
                        // Clear any recorded but unuploaded content
                        this.deleteRecording();
                        // Reset the waveform display
                        this.resetWaveSurfer();
                        // Reset upload state
                        this.isUploading = false;
                        this.uploadProgress = 0;
                    };

                    livewireComponent.addEventListener('livewire-upload-finish', this._uploadFinishHandler);
                    livewireComponent.addEventListener('livewire-upload-error', this._uploadErrorHandler);

                    // Listen for global voice-note:canceled event
                    window.addEventListener('voice-note:canceled', this._voiceNoteCanceledHandler);

                    this._livewireListenersAdded = true;
                }
            }
        },

        destroy() {
            console.log('Destroying voice recorder...');

            // Clean up Livewire listeners
            if (this._livewireListenersAdded && this.$refs.recordingWaveform) {
                const livewireComponent = this.$refs.recordingWaveform.closest('[wire\\:id]');
                if (livewireComponent) {
                    livewireComponent.removeEventListener('livewire-upload-finish', this._uploadFinishHandler);
                    livewireComponent.removeEventListener('livewire-upload-error', this._uploadErrorHandler);
                }
                // Remove global voice-note:canceled listener
                window.removeEventListener('voice-note:canceled', this._voiceNoteCanceledHandler);
                this._livewireListenersAdded = false;
            }

            // Stop any ongoing operations
            this.stopTimer();

            // Stop recording if active via manager
            if (this.isRecording && this.instanceKey) {
                try {
                    const recordPlugin = recorderManager.getRecord(this.instanceKey);
                    if (recordPlugin) {
                        recordPlugin.stopRecording();
                    }
                } catch (error) {
                    console.warn('Error stopping recording during destroy:', error);
                }
            }

            // Destroy recorder instance via manager
            if (this.instanceKey) {
                recorderManager.destroy(this.instanceKey);
            }

            // Mark as no longer initialized
            this.isInitialized = false;

            // Destroy isolated playback instance if exists
            if (this.playbackWavesurfer) {
                try {
                    this.playbackWavesurfer.destroy();
                } catch (error) {
                    console.warn('Error destroying playback wavesurfer during component destroy:', error);
                }
                this.playbackWavesurfer = null;
            }

            // Clean up object URLs
            if (this.recordedUrl) {
                URL.revokeObjectURL(this.recordedUrl);
                this.recordedUrl = null;
            }

            // Reset all state
            this.recordedBlob = null;
            this.isRecording = false;
            this.hasRecording = false;
            this.isPlaying = false;
            this.isUploading = false;
            this.currentDuration = 0;
            this.uploadProgress = 0;
        }
    }
}
