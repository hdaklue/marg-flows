import WaveSurfer from 'wavesurfer.js';
import RecordPlugin from 'wavesurfer.js/dist/plugins/record.esm.js';

// Module-level state
let activePlayer = {
    wavesurfer: null,
    instanceKey: null,
};
let voiceRecorder = null; // This remains separate and untouched

/**
 * Creates and configures a new WaveSurfer instance.
 * @private
 */
const createInstance = async (container, audioUrl, onReady, options, withRecorder = false) => {
    const wavesurfer = WaveSurfer.create({
        container,
        waveColor: options.waveColor,
        progressColor: options.progressColor,
        cursorColor: options.cursorColor,
        barWidth: 2,
        barGap: 1,
        responsive: true,
        height: 32,
        normalize: true,
        backend: 'WebAudio',
        ...options,
    });

    // Pass the created instance to the onReady callback if provided
    wavesurfer.on('ready', () => {
        if (onReady) {
            onReady(wavesurfer);
        }
    });

    wavesurfer.on('error', (error) => {
        console.error(`Wavesurfer error:`, error);
    });

    // Initialize record plugin if requested
    let recordPlugin = null;
    if (withRecorder) {
        recordPlugin = wavesurfer.registerPlugin(
            RecordPlugin.create({
                renderRecordedAudio: false,
                scrollingWaveform: false,
                continuousWaveform: true,
                continuousWaveformDuration: options.maxDuration || 180,
                mediaRecorderOptions: {
                    mimeType: 'audio/webm;codecs=opus'
                }
            })
        );
    }

    if (audioUrl) {
        try {
            await wavesurfer.load(audioUrl);
        } catch (error) {
            console.error(`Failed to load audio:`, error);
            throw error;
        }
    }

    return { wavesurfer, recordPlugin };
};

/**
 * Player Manager - Handles a single, dynamic audio player instance
 */
export const playerManager = {

    // Destroy all active players
    destroyAll() {
        if (activePlayer.wavesurfer) {
            try {
                activePlayer.wavesurfer.destroy();
            } catch (error) {
                console.warn('Error destroying active player:', error);
            }
            activePlayer = { wavesurfer: null, instanceKey: null };
        }
    },
    async togglePlay(instanceKey, container, audioUrl, onReady, options) {
        // If the user clicks the same player that is already active, just toggle play/pause.
        if (activePlayer.instanceKey === instanceKey) {
            if (activePlayer.wavesurfer) {
                activePlayer.wavesurfer.playPause();
            }
            return;
        }

        // If a different player is clicked, properly transition:
        // 1. Pause current player
        if (activePlayer.wavesurfer) {
            try {
                if (activePlayer.wavesurfer.isPlaying()) {
                    activePlayer.wavesurfer.pause();
                }
            } catch (error) {
                console.warn('Error pausing current player:', error);
            }

            // 2. Destroy the old player
            try {
                activePlayer.wavesurfer.destroy();
            } catch (error) {
                console.warn('Error destroying current player:', error);
            }
        }

        // 3. Create and initialize the new player with new URL
        const { wavesurfer: newWavesurfer } = await createInstance(container, audioUrl, onReady, options, false);

        // 4. Assign the new player
        activePlayer = {
            wavesurfer: newWavesurfer,
            instanceKey: instanceKey,
        };

        // 5. Play the new audio (different URL)
        try {
            await newWavesurfer.play();
        } catch (error) {
            console.warn('Error playing new audio:', error);
        }
    },

    isPlaying() {
        return activePlayer.wavesurfer ? activePlayer.wavesurfer.isPlaying() : false;
    },

    getCurrentTime() {
        return activePlayer.wavesurfer ? activePlayer.wavesurfer.getCurrentTime() : 0;
    },

    getDuration() {
        return activePlayer.wavesurfer ? activePlayer.wavesurfer.getDuration() : 0;
    },

    getActiveInstanceKey() {
        return activePlayer.instanceKey;
    },

    destroy(instanceKey) {
        // Only destroy if the key matches the active instance
        if (activePlayer.instanceKey === instanceKey) {
            activePlayer.wavesurfer?.destroy();
            activePlayer = { wavesurfer: null, instanceKey: null };
        }
    },
};

/**
 * Recorder Manager - Handles voice recorder instances with recording capability
 */
export const recorderManager = {
    instances: new Map(),


    // Stop all active recordings
    stopAllRecording() {
        for (const [instanceKey, instance] of this.instances) {
            if (instance.recordPlugin) {
                try {
                    if (instance.recordPlugin.isRecording()) {
                        instance.recordPlugin.stopRecording();
                    }
                } catch (error) {
                    console.warn(`Error stopping recording for ${instanceKey}:`, error);
                }
            }
        }
    },

    async init(instanceKey, container, audioUrl = null, onReady = null, options = {}) {
        // Destroy existing instance with same key
        if (this.instances.has(instanceKey)) {
            this.destroy(instanceKey);
        }

        // Clear container to ensure clean slate
        container.innerHTML = '';

        const defaultOptions = {
            waveColor: '#71717a', // zinc-500
            progressColor: '#10b981', // emerald-500
            cursorColor: '#059669', // emerald-600
            interact: false,
            maxDuration: 180,
            ...options,
        };

        const { wavesurfer, recordPlugin } = await createInstance(
            container,
            audioUrl,
            onReady,
            defaultOptions,
            true // withRecorder = true
        );

        // Store the instance
        this.instances.set(instanceKey, {
            wavesurfer,
            recordPlugin,
            container
        });

        return { wavesurfer, recordPlugin };
    },

    getRecord(instanceKey) {
        const instance = this.instances.get(instanceKey);
        return instance?.recordPlugin || null;
    },

    getWavesurfer(instanceKey) {
        const instance = this.instances.get(instanceKey);
        return instance?.wavesurfer || null;
    },

    togglePlay(instanceKey) {
        const instance = this.instances.get(instanceKey);
        if (instance?.wavesurfer) {
            instance.wavesurfer.playPause();
        }
    },

    isPlaying(instanceKey) {
        const instance = this.instances.get(instanceKey);
        return instance?.wavesurfer ? instance.wavesurfer.isPlaying() : false;
    },

    getCurrentTime(instanceKey) {
        const instance = this.instances.get(instanceKey);
        return instance?.wavesurfer ? instance.wavesurfer.getCurrentTime() : 0;
    },

    getDuration(instanceKey) {
        const instance = this.instances.get(instanceKey);
        return instance?.wavesurfer ? instance.wavesurfer.getDuration() : 0;
    },

    destroy(instanceKey) {
        const instance = this.instances.get(instanceKey);
        if (instance) {
            try {
                // Clean up record plugin listeners first
                if (instance.recordPlugin) {
                    instance.recordPlugin.unAll();
                }
                // Then destroy the wavesurfer instance
                if (instance.wavesurfer) {
                    instance.wavesurfer.unAll();
                    instance.wavesurfer.destroy();
                }
            } catch (error) {
                console.warn('Error destroying recorder wavesurfer:', error);
            }
            this.instances.delete(instanceKey);
        }
    },

    destroyAll() {
        for (const [instanceKey] of this.instances) {

            this.destroy(instanceKey);
        }
    },
};

// Event listeners for cleanup
window.addEventListener('wv-manager:player:destroy', () => {
    console.log('listening inside the manager');
    playerManager.destroyAll();
});

window.addEventListener('wv-manager:recorder:destroy', () => {
    console.log('listening inside the manager');
    recorderManager.destroyAll();
});

// Default export for backward compatibility
export default playerManager;
