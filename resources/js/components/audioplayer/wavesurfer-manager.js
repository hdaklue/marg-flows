import WaveSurfer from 'wavesurfer.js';
import RecordPlugin from 'wavesurfer.js/dist/plugins/record.esm.js';

// Module-level state
let activeInstanceKey = null;
let playerInstances = new Map(); // Store all player instances
const MAX_ACTIVE_PLAYERS = 10; // Increased limit for better user experience
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

    // Always load audio immediately for both recorders and players
    if (audioUrl) {
        try {
            await wavesurfer.load(audioUrl);
            if (!withRecorder) {
                // For players, mark as loaded
                wavesurfer._isLoaded = true;
            }
        } catch (error) {
            console.error(`Failed to load audio:`, error);
            throw error;
        }
    } else if (!withRecorder) {
        // Players without audioUrl - set lazy load flags
        wavesurfer._audioUrl = null;
        wavesurfer._isLoaded = false;
    }

    return { wavesurfer, recordPlugin };
};

/**
 * Manages LRU cache for player instances
 * @private
 */
const managePlayerCache = (currentInstanceKey) => {
    // If we're at the limit and adding a new instance
    if (playerInstances.size >= MAX_ACTIVE_PLAYERS && !playerInstances.has(currentInstanceKey)) {
        // Find the oldest instance (first in Map iteration order)
        const oldestKey = playerInstances.keys().next().value;
        
        if (oldestKey && oldestKey !== activeInstanceKey) {
            console.log(`🗑️ LRU Cache: Destroying oldest player instance: ${oldestKey} (limit: ${MAX_ACTIVE_PLAYERS})`);
            const oldestInstance = playerInstances.get(oldestKey);
            
            try {
                // Destroy the wavesurfer instance - it will handle its own DOM cleanup
                oldestInstance.wavesurfer?.destroy();
            } catch (error) {
                console.warn('Error destroying oldest player:', error);
            }
            
            playerInstances.delete(oldestKey);
        }
    }
};

/**
 * Updates access order for LRU cache
 * @private
 */
const updateAccessOrder = (instanceKey) => {
    if (playerInstances.has(instanceKey)) {
        // Remove and re-add to move to end (most recently used)
        const instance = playerInstances.get(instanceKey);
        playerInstances.delete(instanceKey);
        playerInstances.set(instanceKey, instance);
    }
};

/**
 * Player Manager - Handles multiple audio player instances with LRU cache
 */
export const playerManager = {

    // Destroy all active players
    destroyAll() {
        for (const [instanceKey, instance] of playerInstances) {
            try {
                if (instance.wavesurfer) {
                    instance.wavesurfer.destroy();
                }
            } catch (error) {
                console.warn('Error destroying player:', error);
            }
        }
        playerInstances.clear();
        activeInstanceKey = null;
    },
    async togglePlay(instanceKey, container, audioUrl, onReady, options) {
        // Check if this instance already exists
        let instance = playerInstances.get(instanceKey);
        console.log(`🎵 togglePlay called for ${instanceKey}, exists: ${!!instance}, active: ${activeInstanceKey}`);
        
        // If clicking the same active player, just toggle play/pause
        if (activeInstanceKey === instanceKey && instance) {
            console.log(`▶️ Toggling play/pause for active player ${instanceKey}`);
            instance.wavesurfer.playPause();
            return;
        }

        // Pause the currently active player (but don't destroy it)
        if (activeInstanceKey && playerInstances.has(activeInstanceKey)) {
            const currentInstance = playerInstances.get(activeInstanceKey);
            try {
                if (currentInstance.wavesurfer.isPlaying()) {
                    currentInstance.wavesurfer.pause();
                }
            } catch (error) {
                console.warn('Error pausing current player:', error);
            }
        }

        // Manage cache before creating new instance
        if (!instance) {
            managePlayerCache(instanceKey);
        }

        // Create new instance if it doesn't exist
        if (!instance) {
            console.log(`🔄 Creating new WaveSurfer instance for ${instanceKey}`);
            const { wavesurfer } = await createInstance(container, audioUrl, onReady, options, false);
            instance = { 
                wavesurfer,
                container,
                audioUrl 
            };
            playerInstances.set(instanceKey, instance);
        } else {
            console.log(`♻️ Reusing existing instance for ${instanceKey}`);
            // Update access order for existing instance
            updateAccessOrder(instanceKey);
        }

        // Set as active and play
        activeInstanceKey = instanceKey;
        
        try {
            console.log(`🎬 Playing ${instanceKey}`);
            await instance.wavesurfer.play();
        } catch (error) {
            console.warn('Error playing audio:', error);
        }
    },

    isPlaying() {
        if (!activeInstanceKey || !playerInstances.has(activeInstanceKey)) return false;
        const instance = playerInstances.get(activeInstanceKey);
        return instance.wavesurfer ? instance.wavesurfer.isPlaying() : false;
    },

    getCurrentTime() {
        if (!activeInstanceKey || !playerInstances.has(activeInstanceKey)) return 0;
        const instance = playerInstances.get(activeInstanceKey);
        return instance.wavesurfer ? instance.wavesurfer.getCurrentTime() : 0;
    },

    getDuration() {
        if (!activeInstanceKey || !playerInstances.has(activeInstanceKey)) return 0;
        const instance = playerInstances.get(activeInstanceKey);
        return instance.wavesurfer ? instance.wavesurfer.getDuration() : 0;
    },

    getActiveInstanceKey() {
        return activeInstanceKey;
    },

    destroy(instanceKey) {
        const instance = playerInstances.get(instanceKey);
        if (instance) {
            try {
                // Explicitly stop playback first
                if (instance.wavesurfer && instance.wavesurfer.isPlaying()) {
                    instance.wavesurfer.pause();
                }
                // Then destroy the instance
                instance.wavesurfer?.destroy();
            } catch (error) {
                console.warn('Error destroying wavesurfer instance:', error);
            }
            playerInstances.delete(instanceKey);
            
            // If this was the active instance, clear the active key
            if (activeInstanceKey === instanceKey) {
                activeInstanceKey = null;
            }
        }
    },

    // Debug method to list active instances
    getActiveInstances() {
        return Array.from(playerInstances.keys());
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

        // Don't clear container with innerHTML - WaveSurfer manages its own DOM
        // The destroy() method above already handles cleanup properly

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

// Make playerManager available globally for cleanup from Livewire
window.playerManager = playerManager;

// Default export for backward compatibility
export default playerManager;
