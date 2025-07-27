// resources/js/components/audioPlayer.js
import { Howl } from "howler";

if (!window.__AudioPlayerManager) {
    window.__AudioPlayerManager = {
        current: null,
        stopOthers(newPlayer) {
            if (this.current && this.current !== newPlayer) {
                const prev = this.current;
                this.current = newPlayer;

                setTimeout(() => {
                    if (this.current !== prev && prev.playing()) {
                        prev.pause();
                    }
                }, 0);
            } else {
                this.current = newPlayer;
            }
        },
        clear(player) {
            if (this.current === player) {
                this.current = null;
            }
        }
    };
}

export default function audioPlayer(url) {
    return {
        player: null,
        isPlaying: false,
        isLoaded: false,
        currentTime: 0,
        duration: 0,
        progress: 0,
        interval: null,
        isDragging: false,
        suppressPauseEvents: false,
        currentProgressBar: null,

        togglePlay() {
            if (!url) return;
            if (!this.player) this.initPlayer();
            this.player.playing() ? this.player.pause() : this.startPlayback();
        },

        startPlayback() {
            window.__AudioPlayerManager.stopOthers(this.player);
            this.player.play();
        },

        initPlayer() {
            let sources;
            try {
                sources = JSON.parse(url);
                if (!Array.isArray(sources) || !sources.every(src => typeof src === 'string')) {
                    throw new Error("Invalid source format");
                }
            } catch {
                sources = [url];
            }

            this.player = new Howl({
                src: sources,
                html5: true,
                preload: false,
                format: ['dolby', 'webm', 'mp3'],
                onload: () => this.waitForValidDuration(),
                onplay: () => {
                    this.isPlaying = true;
                    this.trackProgress();
                    this.waitForValidDuration();
                },
                onpause: () => {
                    this.isPlaying = false;
                    this.stopTracking();
                    if (!this.suppressPauseEvents) {
                        window.__AudioPlayerManager.clear(this.player);
                    }
                },
                onend: () => {
                    this.isPlaying = false;
                    this.currentTime = 0;
                    this.progress = 0;
                    this.stopTracking();
                    window.__AudioPlayerManager.clear(this.player);
                }
            });
        },

        waitForValidDuration() {
            const d = this.player.duration();
            if (isFinite(d) && !isNaN(d) && d > 0) {
                this.duration = d;
                this.isLoaded = true;
            } else {
                setTimeout(() => this.waitForValidDuration(), 100);
            }
        },

        startDrag(event, progressBarElement = null) {
            if (!this.isLoaded || !isFinite(this.duration)) return;

            // Use provided element, or try to find from Alpine.js refs, or use event target
            const progressBar = progressBarElement ||
                this.$refs?.progressBar ||
                event.currentTarget;

            if (!progressBar) return;

            this.currentProgressBar = progressBar;
            this.isDragging = true;
            this.suppressPauseEvents = true;

            if (this.player?.playing()) {
                this.player.pause();
                this.isPlaying = false;
            }

            this.updateDrag(event);
            this._boundUpdateDrag = this.updateDrag.bind(this);
            this._boundStopDrag = this.stopDrag.bind(this);

            window.addEventListener('mousemove', this._boundUpdateDrag);
            window.addEventListener('mouseup', this._boundStopDrag);
            window.addEventListener('touchmove', this._boundUpdateDrag, { passive: false });
            window.addEventListener('touchend', this._boundStopDrag);
        },

        updateDrag(event) {
            if (!this.isLoaded || !isFinite(this.duration) || !this.isDragging) return;

            const progressBar = this.currentProgressBar;
            if (!progressBar) return;

            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const rect = progressBar.getBoundingClientRect();
            const percent = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
            const time = percent * this.duration;

            if (!isFinite(time)) return;

            this.currentTime = time;
            this.progress = percent * 100;

            if (this.player) {
                this.player.seek(time);
            }
        },

        stopDrag() {
            this.isDragging = false;
            this.suppressPauseEvents = false;
            this.currentProgressBar = null; // Clear the reference

            if (this.player) {
                this.player.play();
                this.isPlaying = true;
                window.__AudioPlayerManager.stopOthers(this.player);
            }

            window.removeEventListener('mousemove', this._boundUpdateDrag);
            window.removeEventListener('mouseup', this._boundStopDrag);
            window.removeEventListener('touchmove', this._boundUpdateDrag);
            window.removeEventListener('touchend', this._boundStopDrag);
        },

        trackProgress() {
            const update = () => {
                if (!this.player || !this.isPlaying) return;
                this.currentTime = this.player.seek() || 0;
                this.progress = (this.currentTime / this.duration) * 100;
                this.interval = requestAnimationFrame(update);
            };
            if (!this.interval) {
                this.interval = requestAnimationFrame(update);
            }
        },

        stopTracking() {
            if (this.interval) {
                cancelAnimationFrame(this.interval);
                this.interval = null;
            }
        },

        seek(event) {
            if (!this.isLoaded || !isFinite(this.duration)) return;
            const rect = event.currentTarget.getBoundingClientRect();
            const percent = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
            const time = this.duration * percent;
            this.player.seek(time);
            this.currentTime = time;
            this.progress = percent * 100;
        },

        formatTime(sec) {
            const m = Math.floor(sec / 60);
            const s = Math.floor(sec % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        },

        cleanup() {
            this.stopTracking();
            if (this.player) {
                this.player.unload();
                if (window.__AudioPlayerManager.current === this.player) {
                    window.__AudioPlayerManager.clear(this.player);
                }
                this.player = null;
            }
        }
    };
}