import { playerManager } from "../audioplayer/wavesurfer-manager.js";

export default function audioPlayer({ audioUrl = '', useVoiceNoteManager = false, size = 'md', containerRef = 'waveformContainer', instanceKey = null } = {}) {
    return {
        audioUrl: audioUrl,
        useVoiceNoteManager: useVoiceNoteManager,
        size: size,
        containerRef: containerRef,
        instanceKey: instanceKey,
        isLoaded: false,
        isPlaying: false,
        currentTime: 0,
        duration: 0,
        updateInterval: null,

        init() {
            // Start a timer to keep the UI in sync with the playerManager state.
            if (this.useVoiceNoteManager) {
                this.startTimeUpdates();
            }
        },

        onWavesurferReady(wavesurfer) {
            this.isLoaded = true;
            this.duration = wavesurfer.getDuration();
        },

        async togglePlay() {
            if (this.useVoiceNoteManager) {
                const container = this.$refs[this.containerRef];
                if (!container) {
                    console.error('Waveform container not found');
                    return;
                }

                // Clear the container before passing it to the manager
                container.innerHTML = '';

                const sizeConfig = {
                    sm: { height: 24, barWidth: 1.5, barGap: 0.5 },
                    md: { height: 32, barWidth: 2, barGap: 1 },
                    lg: { height: 40, barWidth: 3, barGap: 1.5 }
                };
                const config = sizeConfig[this.size] || sizeConfig.md;
                const isDark = document.documentElement.classList.contains('dark');
                const waveformOptions = {
                    height: config.height,
                    waveColor: isDark ? '#52525b' : '#e4e4e7',
                    progressColor: '#0ea5e9',
                    cursorColor: isDark ? '#0284c7' : '#0369a1',
                    barWidth: config.barWidth,
                    barGap: config.barGap,
                    responsive: true,
                    normalize: true,
                    backend: 'WebAudio'
                };

                await playerManager.togglePlay(
                    this.instanceKey,
                    container,
                    this.audioUrl,
                    this.onWavesurferReady.bind(this),
                    waveformOptions
                );
            } else {
                // This is the logic for a non-managed, standalone player.
                if (!this.wavesurfer) {
                    import('wavesurfer.js').then((WaveSurfer) => {
                        this.wavesurfer = WaveSurfer.default.create({ container: this.$refs[this.containerRef] /* ... add options ... */ });
                        this.wavesurfer.load(this.audioUrl);
                        this.wavesurfer.on('ready', () => this.wavesurfer.play());
                    });
                } else {
                    this.wavesurfer.playPause();
                }
            }
        },

        startTimeUpdates() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }

            this.updateInterval = setInterval(() => {
                if (this.useVoiceNoteManager && playerManager.getActiveInstanceKey() === this.instanceKey) {
                    this.currentTime = playerManager.getCurrentTime();
                    this.isPlaying = playerManager.isPlaying();
                } else {
                    // If this component is not the active one, ensure its state is paused.
                    this.isPlaying = false;
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
            const container = this.$refs[this.containerRef];
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
                playerManager.destroy(this.instanceKey);
            }
        }
    }
}
