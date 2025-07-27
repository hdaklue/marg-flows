import voiceNoteWavesurfer from "../audioplayer/wavesurfer-manager.js";

export default function audioPlayer({ audioUrl = '', useVoiceNoteManager = false, size = 'md' } = {}) {
    return {
        audioUrl: audioUrl,
        useVoiceNoteManager: useVoiceNoteManager,
        size: size,
        wavesurfer: null,
        isLoaded: false,
        isPlaying: false,
        currentTime: 0,
        duration: 0,
        updateInterval: null,

        init() {
            if (!this.audioUrl) {
                return;
            }

            this.initializeWavesurfer();
        },

        initializeWavesurfer() {
            try {
                const container = this.$refs.waveformContainer;
                if (!container) {
                    return;
                }

                container.innerHTML = '';

                // Size-based configuration
                const sizeConfig = {
                    sm: { height: 24, barWidth: 1.5, barGap: 0.5 },
                    md: { height: 32, barWidth: 2, barGap: 1 },
                    lg: { height: 40, barWidth: 3, barGap: 1.5 }
                };

                const config = sizeConfig[this.size] || sizeConfig.md;

                // Check theme for colors
                const isDark = document.documentElement.classList.contains('dark');
                const waveformOptions = {
                    height: config.height,
                    waveColor: isDark ? '#52525b' : '#e4e4e7', // zinc-600 dark, zinc-300 light
                    progressColor: '#0ea5e9', // sky-500
                    cursorColor: isDark ? '#0284c7' : '#0369a1', // sky-600 dark, sky-700 light
                    barWidth: config.barWidth,
                    barGap: config.barGap,
                    responsive: true,
                    normalize: true,
                    backend: 'WebAudio'
                };

                if (this.useVoiceNoteManager) {
                    // Use the voice note wavesurfer manager
                    this.wavesurfer = voiceNoteWavesurfer.init(
                        container,
                        this.audioUrl,
                        this.onWavesurferReady.bind(this),
                        waveformOptions
                    );
                } else {
                    import('wavesurfer.js').then((WaveSurfer) => {
                        this.wavesurfer = WaveSurfer.default.create({
                            container: container,
                            ...waveformOptions
                        });

                        this.setupEventListeners();
                        this.wavesurfer.load(this.audioUrl);
                    });
                }
            } catch (error) {
                console.error('Audio player: Failed to initialize wavesurfer:', error);
                this.showError();
            }
        },

        onWavesurferReady() {
            this.isLoaded = true;
            if (this.useVoiceNoteManager) {
                this.duration = voiceNoteWavesurfer.getDuration();
            } else {
                this.duration = this.wavesurfer.getDuration();
            }
            this.startTimeUpdates();
        },

        setupEventListeners() {
            if (!this.wavesurfer) return;

            this.wavesurfer.on('ready', () => {
                this.onWavesurferReady();
            });

            this.wavesurfer.on('play', () => {
                this.isPlaying = true;
            });

            this.wavesurfer.on('pause', () => {
                this.isPlaying = false;
            });

            this.wavesurfer.on('finish', () => {
                this.isPlaying = false;
            });

            this.wavesurfer.on('error', (error) => {
                console.error('Audio player: Wavesurfer error:', error);
                this.showError();
            });
        },

        togglePlay() {
            if (!this.isLoaded) return;

            if (this.useVoiceNoteManager) {
                voiceNoteWavesurfer.togglePlay();
                this.isPlaying = voiceNoteWavesurfer.isPlaying();
            } else {
                this.wavesurfer.playPause();
            }
        },

        startTimeUpdates() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }

            this.updateInterval = setInterval(() => {
                if (this.useVoiceNoteManager) {
                    this.currentTime = voiceNoteWavesurfer.getCurrentTime();
                    this.isPlaying = voiceNoteWavesurfer.isPlaying();
                } else if (this.wavesurfer) {
                    this.currentTime = this.wavesurfer.getCurrentTime();
                    this.isPlaying = this.wavesurfer.isPlaying();
                }
            }, 100);
        },

        formatTime(seconds) {
            if (!isFinite(seconds) || seconds < 0) return '0:00';
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        },

        showError() {
            const container = this.$refs.waveformContainer;
            if (container) {
                container.innerHTML = `
                    <div class="flex items-center justify-center h-full text-red-500 text-xs">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Failed to load audio
                    </div>
                `;
            }
        },

        cleanup() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }

            if (this.useVoiceNoteManager) {
                // Voice note manager handles its own cleanup
                voiceNoteWavesurfer.stop();
            } else if (this.wavesurfer) {
                this.wavesurfer.destroy();
                this.wavesurfer = null;
            }

            this.isLoaded = false;
            this.isPlaying = false;
            this.currentTime = 0;
            this.duration = 0;
        }
    }
}
