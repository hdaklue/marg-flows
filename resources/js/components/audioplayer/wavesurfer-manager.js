import WaveSurfer from 'wavesurfer.js';

/**
 * Voice Note Wavesurfer Manager
 * Ensures only one voice note wavesurfer instance exists (separate from main audio annotations)
 * Scoped to window.voiceNote to avoid conflicts with main audio system
 */
class VoiceNoteWavesurferManager {
    constructor() {
        if (window.voiceNote?.wavesurfer) {
            return window.voiceNote.wavesurfer;
        }
        
        // Initialize voiceNote namespace
        if (!window.voiceNote) {
            window.voiceNote = {};
        }
        
        this.wavesurfer = null;
        this.currentContainer = null;
        this.currentCallback = null;
        this.isInitialized = false;
        
        // Store in global scope under voiceNote namespace
        window.voiceNote.wavesurfer = this;
    }

    /**
     * Initialize or switch wavesurfer to a new container
     * @param {HTMLElement} container - Container element for wavesurfer
     * @param {string} audioUrl - URL of the audio file
     * @param {Function} onReady - Callback when wavesurfer is ready
     * @param {Object} options - Additional wavesurfer options
     */
    async init(container, audioUrl, onReady = null, options = {}) {
        // Destroy existing instance if switching containers
        if (this.wavesurfer && this.currentContainer !== container) {
            this.destroy();
        }

        // If same container and already initialized, just load new audio
        if (this.wavesurfer && this.currentContainer === container) {
            await this.loadAudio(audioUrl);
            if (onReady) onReady(this.wavesurfer);
            return this.wavesurfer;
        }

        // Create new wavesurfer instance
        const defaultOptions = {
            container: container,
            waveColor: '#e4e4e7', // zinc-300
            progressColor: '#0ea5e9', // sky-500
            cursorColor: '#0369a1', // sky-700
            barWidth: 2,
            barGap: 1,
            responsive: true,
            height: 32,
            normalize: true,
            backend: 'WebAudio',
            ...options
        };

        try {
            this.wavesurfer = WaveSurfer.create(defaultOptions);
            this.currentContainer = container;
            this.currentCallback = onReady;

            // Load audio
            await this.loadAudio(audioUrl);

            // Set up event listeners
            this.setupEventListeners();

            if (onReady) {
                onReady(this.wavesurfer);
            }

            this.isInitialized = true;
            return this.wavesurfer;

        } catch (error) {
            console.error('Wavesurfer initialization failed:', error);
            this.cleanup();
            throw error;
        }
    }

    /**
     * Load new audio into existing wavesurfer instance
     * @param {string} audioUrl - URL of the audio file
     */
    async loadAudio(audioUrl) {
        if (!this.wavesurfer) {
            throw new Error('Wavesurfer not initialized');
        }

        try {
            await this.wavesurfer.load(audioUrl);
        } catch (error) {
            console.error('Failed to load audio:', error);
            throw error;
        }
    }

    /**
     * Setup common event listeners
     */
    setupEventListeners() {
        if (!this.wavesurfer) return;

        this.wavesurfer.on('ready', () => {
            console.log('Wavesurfer ready');
            // Trigger ready callback if it exists
            if (this.currentCallback) {
                this.currentCallback(this.wavesurfer);
            }
        });

        this.wavesurfer.on('error', (error) => {
            console.error('Wavesurfer error:', error);
        });

        this.wavesurfer.on('finish', () => {
            console.log('Audio finished playing');
        });

        this.wavesurfer.on('play', () => {
            console.log('Audio started playing');
        });

        this.wavesurfer.on('pause', () => {
            console.log('Audio paused');
        });
    }

    /**
     * Play/pause toggle
     */
    togglePlay() {
        if (!this.wavesurfer) return;
        this.wavesurfer.playPause();
    }

    /**
     * Play audio
     */
    play() {
        if (!this.wavesurfer) return;
        this.wavesurfer.play();
    }

    /**
     * Pause audio
     */
    pause() {
        if (!this.wavesurfer) return;
        this.wavesurfer.pause();
    }

    /**
     * Stop audio
     */
    stop() {
        if (!this.wavesurfer) return;
        this.wavesurfer.stop();
    }

    /**
     * Get current playing state
     */
    isPlaying() {
        return this.wavesurfer ? this.wavesurfer.isPlaying() : false;
    }

    /**
     * Get current time
     */
    getCurrentTime() {
        return this.wavesurfer ? this.wavesurfer.getCurrentTime() : 0;
    }

    /**
     * Get duration
     */
    getDuration() {
        return this.wavesurfer ? this.wavesurfer.getDuration() : 0;
    }

    /**
     * Seek to specific time
     * @param {number} time - Time in seconds
     */
    seekTo(time) {
        if (!this.wavesurfer) return;
        const duration = this.getDuration();
        if (duration > 0) {
            this.wavesurfer.seekTo(time / duration);
        }
    }

    /**
     * Set volume
     * @param {number} volume - Volume level (0-1)
     */
    setVolume(volume) {
        if (!this.wavesurfer) return;
        this.wavesurfer.setVolume(volume);
    }

    /**
     * Destroy wavesurfer instance
     */
    destroy() {
        if (this.wavesurfer) {
            this.wavesurfer.destroy();
            this.wavesurfer = null;
        }
        this.cleanup();
    }

    /**
     * Cleanup references
     */
    cleanup() {
        this.currentContainer = null;
        this.currentCallback = null;
        this.isInitialized = false;
    }

    /**
     * Check if wavesurfer is initialized
     */
    isReady() {
        return this.isInitialized && this.wavesurfer !== null;
    }

    /**
     * Get the wavesurfer instance (for advanced usage)
     */
    getInstance() {
        return this.wavesurfer;
    }
}

// Create and export singleton instance
const voiceNoteWavesurfer = new VoiceNoteWavesurferManager();
export default voiceNoteWavesurfer;