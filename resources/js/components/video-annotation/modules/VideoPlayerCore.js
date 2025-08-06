import videojs from 'video.js';
import 'video.js/dist/video-js.css';

/**
 * VideoPlayerCore - Handles basic video player setup, controls, and VideoJS integration
 */
export class VideoPlayerCore {
    constructor(config, sharedState) {
        this.config = config;
        this.sharedState = sharedState;
        this.player = null;
        this.videoElement = null;
        this.qualitySources = [];
        
        // Event throttling
        this.lastTimeUpdateDispatch = 0;
        this.lastBufferedUpdateDispatch = 0;
        
        // Browser detection
        this.isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
        this.isIOSSafari = this.isSafari && /iPhone|iPad|iPod/i.test(navigator.userAgent);
    }

    /**
     * Initialize the video player
     */
    init(videoElement) {
        this.videoElement = videoElement;
        this.detectQualitySources();
        this.setupVideoJS();
        this.setupPlayerEventListeners();
        return this;
    }

    /**
     * Detect browser type
     */
    detectBrowser() {
        return {
            isSafari: this.isSafari,
            isIOSSafari: this.isIOSSafari
        };
    }

    /**
     * Initialize quality sources from data attribute
     */
    detectQualitySources() {
        if (this.videoElement) {
            const qualitySourcesAttr = this.videoElement.getAttribute('data-quality-sources');
            if (qualitySourcesAttr) {
                try {
                    this.qualitySources = JSON.parse(qualitySourcesAttr);
                    if (localStorage.getItem('videoAnnotationDebug') === 'true') {
                        console.log('[VideoCore] Loaded', this.qualitySources.length, 'quality sources');
                    }
                } catch (e) {
                    console.warn('[VideoCore] Failed to parse quality sources:', e);
                    this.qualitySources = [];
                }
            }
        }
    }

    /**
     * Setup VideoJS player
     */
    setupVideoJS() {
        if (!this.videoElement) {
            console.error('[VideoCore] No video element for VideoJS setup');
            return;
        }
        
        if (this.videoElement.classList.contains('vjs-v8')) {
            return;
        }

        const options = {
            controls: false, // We use custom controls
            autoplay: false,
            preload: 'metadata',
            responsive: true,
            fluid: true,
            playsinline: true,
            html5: {
                vhs: {
                    overrideNative: !this.isIOSSafari
                }
            }
        };

        this.player = videojs(this.videoElement, options);
        
        // Set fluid responsive behavior
        this.applyVideoJSFluidClasses();
    }

    /**
     * Setup player event listeners
     */
    setupPlayerEventListeners() {
        if (!this.player) return;

        this.player.ready(() => {
            console.log('[VideoCore] Player ready');
            this.sharedState.videoLoaded = true;
            
            // Load the selected source or first source
            this.loadInitialSource();
            
            // Update duration when metadata loads
            this.player.on('loadedmetadata', () => {
                const duration = this.player.duration() || 0;
                console.log('[VideoCore] Metadata loaded, duration:', duration);
                this.sharedState.duration = duration;
                this.updateAspectRatio();
                
                // Dispatch event for Alpine.js reactivity
                if (typeof window !== 'undefined') {
                    const event = new CustomEvent('video-duration-changed', {
                        detail: { duration }
                    });
                    document.dispatchEvent(event);
                }
                
                // Dispatch ready event
                if (this.sharedState.duration > 0) {
                    console.log('[VideoCore] Video ready for playback');
                }
            });
            
            // Handle loading events
            this.player.on('loadstart', () => {
                console.log('[VideoCore] Video load started');
            });
            
            this.player.on('canplay', () => {
                console.log('[VideoCore] Video can start playing');
                // Sync frame rate from config to SharedState
                const configFrameRate = this.config?.video?.frameRate;
                if (configFrameRate && this.sharedState) {
                    this.sharedState.frameRate = configFrameRate;
                    console.log(`[VideoCore] Frame rate set to ${configFrameRate}fps from config`);
                }
            });
            
            this.player.on('error', (error) => {
                console.error('[VideoCore] Video error:', error);
            });

            // Update current time
            this.player.on('timeupdate', () => {
                const currentTime = this.player.currentTime() || 0;
                this.sharedState.currentTime = currentTime;
                this.sharedState.currentFrameNumber = Math.floor(currentTime * this.sharedState.frameRate) + 1;
                this.updateBuffered();
                
                // Dispatch event for Alpine.js reactivity (throttled to avoid too many events)
                if (typeof window !== 'undefined' && this.sharedState.duration > 0) {
                    const now = Date.now();
                    if (!this.lastTimeUpdateDispatch || now - this.lastTimeUpdateDispatch > 100) { // Throttle to 10fps
                        this.lastTimeUpdateDispatch = now;
                        const event = new CustomEvent('video-time-updated', {
                            detail: { 
                                currentTime, 
                                duration: this.sharedState.duration,
                                progressPercentage: (currentTime / this.sharedState.duration) * 100
                            }
                        });
                        document.dispatchEvent(event);
                    }
                }
                
                // Debug logging for progress bar issues
                if (this.sharedState.duration > 0) {
                    const progressPercentage = (currentTime / this.sharedState.duration) * 100;
                    if (this.sharedState.contextDisplay?.debugMode) {
                        console.log(`[VideoCore] Time: ${currentTime.toFixed(2)}s, Duration: ${this.sharedState.duration.toFixed(2)}s, Progress: ${progressPercentage.toFixed(1)}%`);
                    }
                }
            });

            // Handle play/pause events
            this.player.on('play', () => {
                this.sharedState.isPlaying = true;
                this.handleProgressBarAutoHide();
                if (this.config.callbacks?.onPlay) {
                    this.config.callbacks.onPlay();
                }
                
                // Dispatch event for Alpine.js reactivity
                if (typeof window !== 'undefined') {
                    const event = new CustomEvent('video-play-state-changed', {
                        detail: { isPlaying: true }
                    });
                    document.dispatchEvent(event);
                }
            });

            this.player.on('pause', () => {
                this.sharedState.isPlaying = false;
                if (this.config.callbacks?.onPause) {
                    this.config.callbacks.onPause();
                }
                
                // Dispatch event for Alpine.js reactivity
                if (typeof window !== 'undefined') {
                    const event = new CustomEvent('video-play-state-changed', {
                        detail: { isPlaying: false }
                    });
                    document.dispatchEvent(event);
                }
            });

            // Handle volume changes
            this.player.on('volumechange', () => {
                this.sharedState.volume = this.player.volume();
                this.sharedState.isMuted = this.player.muted();
                if (this.config.callbacks?.onVolumeChange) {
                    this.config.callbacks.onVolumeChange(this.sharedState.volume, this.sharedState.isMuted);
                }
                
                // Dispatch event for Alpine.js reactivity
                if (typeof document !== 'undefined') {
                    const event = new CustomEvent('video-volume-changed', {
                        detail: { 
                            volume: this.sharedState.volume, 
                            isMuted: this.sharedState.isMuted 
                        }
                    });
                    document.dispatchEvent(event);
                }
            });

            // Handle fullscreen changes
            this.player.on('fullscreenchange', () => {
                this.sharedState.isFullscreen = this.player.isFullscreen();
                if (this.config.callbacks?.onFullscreenChange) {
                    this.config.callbacks.onFullscreenChange(this.sharedState.isFullscreen);
                }
            });
        });
    }

    /**
     * Load the initial video source
     */
    loadInitialSource() {
        if (!this.player || this.qualitySources.length === 0) {
            console.log('[VideoCore] No quality sources to load');
            return;
        }

        // Find selected source or use highest quality (first in list) instead of 360p
        const selectedSource = this.qualitySources.find(source => source.selected) || 
                               this.qualitySources.find(source => source.quality === '1080' || source.label === '1080p') ||
                               this.qualitySources[0];
        console.log('[VideoCore] Loading initial source:', selectedSource);

        if (selectedSource) {
            this.player.src({
                src: selectedSource.src,
                type: selectedSource.type || 'video/mp4'
            });
            
            // Store current resolution info
            this.sharedState.currentResolution = selectedSource;
            this.sharedState.currentResolutionSrc = selectedSource.src;
            this.sharedState.qualitySources = this.qualitySources;
            
            // Trigger Alpine.js reactivity by dispatching an event
            if (typeof window !== 'undefined') {
                setTimeout(() => {
                    const event = new CustomEvent('quality-sources-updated', {
                        detail: { qualitySources: this.qualitySources }
                    });
                    document.dispatchEvent(event);
                }, 100);
            }
        }
    }

    /**
     * Change video resolution/quality
     */
    changeResolution(source) {
        if (!this.player || !source) return;

        console.log('[VideoCore] Changing resolution to:', source);
        const currentTime = this.player.currentTime();
        const wasPlaying = !this.player.paused();

        this.player.src({
            src: source.src,
            type: source.type || 'video/mp4'
        });

        // Restore playback position and state
        this.player.one('loadedmetadata', () => {
            this.player.currentTime(currentTime);
            if (wasPlaying) {
                this.player.play();
            }
        });

        // Update shared state
        this.sharedState.currentResolution = source;
        this.sharedState.currentResolutionSrc = source.src;
        
        // Update the qualitySources array to mark the new selection
        this.qualitySources = this.qualitySources.map(s => ({
            ...s,
            selected: s.src === source.src
        }));
        this.sharedState.qualitySources = this.qualitySources;
        
        // Dispatch event to update Alpine.js component
        if (typeof window !== 'undefined') {
            const event = new CustomEvent('quality-sources-updated', {
                detail: { qualitySources: this.qualitySources }
            });
            document.dispatchEvent(event);
        }
    }

    /**
     * Update buffered progress
     */
    updateBuffered() {
        if (this.player && this.sharedState.duration > 0) {
            const buffered = this.player.buffered();
            if (buffered.length > 0) {
                const bufferedEnd = buffered.end(buffered.length - 1);
                const bufferedPercentage = (bufferedEnd / this.sharedState.duration) * 100;
                this.sharedState.bufferedPercentage = bufferedPercentage;
                
                // Dispatch event for Alpine.js reactivity (throttled to avoid too many events)
                if (typeof window !== 'undefined') {
                    const now = Date.now();
                    if (!this.lastBufferedUpdateDispatch || now - this.lastBufferedUpdateDispatch > 500) { // Throttle to 2fps for buffered updates
                        this.lastBufferedUpdateDispatch = now;
                        const event = new CustomEvent('video-buffered-updated', {
                            detail: { 
                                bufferedPercentage,
                                bufferedEnd,
                                duration: this.sharedState.duration
                            }
                        });
                        document.dispatchEvent(event);
                    }
                }
            }
        }
    }

    /**
     * Handle progress bar auto hide behavior
     */
    handleProgressBarAutoHide() {
        if (this.sharedState.progressBarMode === 'auto-hide') {
            this.sharedState.showProgressBar = true;
            
            if (this.sharedState.progressBarTimeout) {
                clearTimeout(this.sharedState.progressBarTimeout);
            }
            
            this.sharedState.progressBarTimeout = setTimeout(() => {
                if (this.sharedState.isPlaying && !this.sharedState.isDragging) {
                    this.sharedState.showProgressBar = false;
                }
            }, this.config.timing.progressBarAutoHideDelay);
        }
    }

    /**
     * Apply VideoJS fluid classes for aspect ratio
     */
    applyVideoJSFluidClasses() {
        const playerEl = this.player?.el();
        if (!playerEl) return;

        // Remove any existing aspect ratio classes but keep vjs-fluid
        const aspectRatioClasses = ['vjs-16-9', 'vjs-4-3', 'vjs-9-16'];
        aspectRatioClasses.forEach(cls => playerEl.classList.remove(cls));
        
        if (!playerEl.classList.contains('vjs-fluid')) {
            playerEl.classList.add('vjs-fluid');
        }
    }

    /**
     * Update aspect ratio based on video dimensions
     */
    updateAspectRatio() {
        if (!this.player) return;

        const width = this.player.videoWidth();
        const height = this.player.videoHeight();
        
        if (width && height) {
            const aspectRatio = width / height;
            const playerEl = this.player.el();
            
            // Check for common aspect ratios with tolerance
            const commonRatios = [
                { ratio: 16/9, class: 'vjs-16-9', tolerance: 0.1 },
                { ratio: 4/3, class: 'vjs-4-3', tolerance: 0.1 },
                { ratio: 9/16, class: 'vjs-9-16', tolerance: 0.1 }
            ];
            
            let specificClass = null;
            for (const { ratio, class: className, tolerance } of commonRatios) {
                if (Math.abs(aspectRatio - ratio) <= tolerance) {
                    specificClass = className;
                    break;
                }
            }
            
            // Apply specific aspect ratio class if detected
            if (specificClass) {
                playerEl.classList.add(specificClass);
            }
        }
    }

    /**
     * Play/pause the video
     */
    togglePlayPause() {
        if (!this.player) return;
        
        if (this.sharedState.isPlaying) {
            this.player.pause();
        } else {
            this.player.play();
        }
    }

    /**
     * Seek to specific time
     */
    seekTo(time) {
        if (this.player && typeof time === 'number' && time >= 0) {
            this.player.currentTime(time);
            if (this.config.callbacks?.onSeek) {
                this.config.callbacks.onSeek(time);
            }
        }
    }

    /**
     * Set volume
     */
    setVolume(volume) {
        if (this.player && typeof volume === 'number') {
            this.player.volume(Math.max(0, Math.min(1, volume)));
        }
    }

    /**
     * Toggle mute
     */
    toggleMute() {
        if (this.player) {
            this.player.muted(!this.player.muted());
        }
    }

    /**
     * Toggle fullscreen
     */
    toggleFullscreen() {
        if (this.player) {
            if (this.player.isFullscreen()) {
                this.player.exitFullscreen();
            } else {
                this.player.requestFullscreen();
            }
        }
    }

    /**
     * Get current frame rate
     */
    getFrameRate() {
        // Use frame rate from config if available, otherwise default to 30fps
        return this.config?.video?.frameRate || 30;
    }

    /**
     * Round time to nearest frame
     */
    roundToNearestFrame(time) {
        const frameRate = this.getFrameRate();
        const frameDuration = 1 / frameRate;
        return Math.round(time / frameDuration) * frameDuration;
    }

    /**
     * Get frame number from time
     */
    getFrameNumber(time) {
        const frameRate = this.getFrameRate();
        return Math.floor(time * frameRate) + 1;
    }

    /**
     * Dispose of the player
     */
    dispose() {
        if (this.player && typeof this.player.dispose === 'function') {
            this.player.dispose();
            this.player = null;
        }
    }

    /**
     * Public API for external access
     */
    get publicAPI() {
        return {
            togglePlayPause: this.togglePlayPause.bind(this),
            seekTo: this.seekTo.bind(this),
            setVolume: this.setVolume.bind(this),
            toggleMute: this.toggleMute.bind(this),
            toggleFullscreen: this.toggleFullscreen.bind(this),
            getFrameRate: this.getFrameRate.bind(this),
            roundToNearestFrame: this.roundToNearestFrame.bind(this),
            getFrameNumber: this.getFrameNumber.bind(this),
            player: this.player
        };
    }
}