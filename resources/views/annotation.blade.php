<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Annotator - Filament Style</title>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/7.7.15/wavesurfer.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wavesurfer.js/7.7.15/plugins/regions.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/heroicons/2.0.18/heroicons.min.js"></script>
    <style>
        /* Filament-inspired color palette */
        :root {
            --primary-50: #eff6ff;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --success-500: #10b981;
            --warning-500: #f59e0b;
            --danger-500: #ef4444;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            margin: 0;
            padding: 1rem;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Card component */
        .card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid var(--gray-200);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary-600);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-700);
        }

        .btn-secondary {
            background: white;
            color: var(--gray-700);
            border-color: var(--gray-300);
        }

        .btn-secondary:hover:not(:disabled) {
            background: var(--gray-50);
        }

        .btn-success {
            background: var(--success-500);
            color: white;
        }

        .btn-danger {
            background: var(--danger-500);
            color: white;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Waveform container */
        .waveform-container {
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            background: white;
            margin: 1rem 0;
            overflow: hidden;
        }

        /* Controls */
        .controls {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
            margin: 1rem 0;
        }

        .time-display {
            font-family: 'SF Mono', 'Monaco', monospace;
            font-size: 0.75rem;
            color: var(--gray-500);
            background: var(--gray-100);
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid var(--gray-200);
        }

        /* Volume control */
        .volume-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .volume-slider {
            width: 80px;
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            appearance: none;
            cursor: pointer;
        }

        .volume-slider::-webkit-slider-thumb {
            width: 16px;
            height: 16px;
            background: var(--primary-600);
            border-radius: 50%;
            appearance: none;
            cursor: pointer;
        }

        /* Annotations list */
        .annotations-list {
            margin-top: 1.5rem;
        }

        .annotation-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            background: white;
        }

        .annotation-info {
            flex: 1;
        }

        .annotation-text {
            font-weight: 500;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .annotation-meta {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .annotation-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .modal {
            background: white;
            border-radius: 0.75rem;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            margin: 1rem;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .modal-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        /* Status indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .status-loading {
            background: #fef3c7;
            color: #92400e;
        }

        .status-ready {
            background: #dcfce7;
            color: #166534;
        }

        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Icons */
        .icon {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .annotation-item {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }

            .annotation-actions {
                justify-content: flex-end;
            }
        }
    </style>
</head>

<body>
    <!-- Alpine WaveSurfer Plugin -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('wavesurfer', (config = {}) => ({
                wavesurfer: null,
                regionsPlugin: null,
                isReady: false,
                isPlaying: false,
                currentTime: 0,
                duration: 0,
                volume: 1,
                loading: false,
                error: null,
                regions: [],
                selectedRegion: null,
                selectionMode: false,
                onNewSelection: config.onNewSelection || null,
                isDragging: false,

                init() {
                    this.$nextTick(() => {
                        this.initWaveSurfer();
                    });
                },

                initWaveSurfer() {
                    if (!window.WaveSurfer) {
                        console.error('WaveSurfer is not loaded');
                        return;
                    }

                    const defaultConfig = {
                        container: this.$el,
                        waveColor: '#3b82f6',
                        progressColor: '#1d4ed8',
                        cursorColor: '#ffffff',
                        barWidth: 2,
                        barRadius: 3,
                        responsive: true,
                        height: 80,
                        normalize: true,
                        backend: 'WebAudio',
                        mediaControls: false,
                        interact: true
                    };

                    try {
                        this.wavesurfer = WaveSurfer.create({
                            ...defaultConfig,
                            ...config
                        });

                        this.initRegionsPlugin();
                        this.setupEventListeners();
                    } catch (error) {
                        this.error = error.message;
                        console.error('WaveSurfer initialization failed:', error);
                    }
                },

                initRegionsPlugin() {
                    if (!window.WaveSurfer.Regions) {
                        console.warn('WaveSurfer Regions plugin not available');
                        return;
                    }

                    try {
                        this.regionsPlugin = this.wavesurfer.registerPlugin(
                            WaveSurfer.Regions.create({
                                dragSelection: {
                                    slop: 5
                                }
                            })
                        );

                        this.setupRegionEvents();
                    } catch (error) {
                        console.error('Regions plugin initialization failed:', error);
                    }
                },

                setupEventListeners() {
                    this.wavesurfer.on('ready', () => {
                        this.isReady = true;
                        this.duration = this.wavesurfer.getDuration();
                        this.loading = false;
                        this.error = null;
                    });

                    this.wavesurfer.on('play', () => {
                        this.isPlaying = true;
                    });

                    this.wavesurfer.on('pause', () => {
                        this.isPlaying = false;
                    });

                    this.wavesurfer.on('finish', () => {
                        this.isPlaying = false;
                        this.currentTime = 0;
                    });

                    this.wavesurfer.on('audioprocess', (time) => {
                        this.currentTime = time;
                    });

                    this.wavesurfer.on('loading', (percent) => {
                        this.loading = percent < 100;
                    });

                    this.wavesurfer.on('error', (error) => {
                        this.error = error;
                        this.loading = false;
                        this.isReady = false;
                    });
                },

                setupRegionEvents() {
                    if (!this.regionsPlugin) return;

                    this.regionsPlugin.on('region-creating', () => {
                        this.isDragging = true;
                    });

                    this.regionsPlugin.on('region-created', (region) => {
                        this.regions.push({
                            id: region.id,
                            start: region.start,
                            end: region.end,
                            color: region.color,
                            content: region.content || ''
                        });
                        this.selectedRegion = region.id;

                        if (this.isDragging && this.onNewSelection) {
                            this.onNewSelection({
                                id: region.id,
                                start: region.start,
                                end: region.end,
                                duration: region.end - region.start,
                                color: region.color,
                                content: region.content || ''
                            });
                        }

                        this.isDragging = false;
                    });

                    this.regionsPlugin.on('region-updated', (region) => {
                        const index = this.regions.findIndex(r => r.id === region.id);
                        if (index !== -1) {
                            this.regions[index] = {
                                id: region.id,
                                start: region.start,
                                end: region.end,
                                color: region.color,
                                content: region.content || ''
                            };
                        }
                    });

                    this.regionsPlugin.on('region-removed', (region) => {
                        this.regions = this.regions.filter(r => r.id !== region.id);
                        if (this.selectedRegion === region.id) {
                            this.selectedRegion = null;
                        }
                    });

                    this.regionsPlugin.on('region-clicked', (region, e) => {
                        this.selectedRegion = region.id;
                        e.stopPropagation();
                    });

                    this.regionsPlugin.on('region-double-clicked', (region) => {
                        this.playRegion(region.id);
                    });
                },

                // Player methods
                play() {
                    if (this.wavesurfer && this.isReady) {
                        this.wavesurfer.play();
                    }
                },

                pause() {
                    if (this.wavesurfer) {
                        this.wavesurfer.pause();
                    }
                },

                stop() {
                    if (this.wavesurfer) {
                        this.wavesurfer.stop();
                    }
                },

                playPause() {
                    if (this.wavesurfer && this.isReady) {
                        this.wavesurfer.playPause();
                    }
                },

                seekTo(progress) {
                    if (this.wavesurfer && this.isReady) {
                        this.wavesurfer.seekTo(progress);
                    }
                },

                setVolume(vol) {
                    if (this.wavesurfer) {
                        this.volume = vol;
                        this.wavesurfer.setVolume(vol);
                    }
                },

                loadFile(file) {
                    if (this.wavesurfer && file) {
                        this.loading = true;
                        this.error = null;
                        this.isReady = false;
                        this.clearRegions();
                        this.wavesurfer.loadBlob(file);
                    }
                },

                // Region methods
                enableSelection() {
                    this.selectionMode = true;
                    if (this.regionsPlugin) {
                        this.regionsPlugin.enableDragSelection();
                    }
                },

                disableSelection() {
                    this.selectionMode = false;
                    if (this.regionsPlugin) {
                        this.regionsPlugin.disableDragSelection();
                    }
                },

                removeRegion(regionId) {
                    if (!this.regionsPlugin) return;

                    const regions = this.regionsPlugin.getRegions();
                    const region = regions.find(r => r.id === regionId);
                    if (region) {
                        region.remove();
                    }
                },

                clearRegions() {
                    if (this.regionsPlugin) {
                        this.regionsPlugin.clearRegions();
                    }
                    this.regions = [];
                    this.selectedRegion = null;
                },

                playRegion(regionId) {
                    const region = this.regions.find(r => r.id === regionId);
                    if (region && this.wavesurfer && this.isReady) {
                        this.wavesurfer.play(region.start, region.end);
                    }
                },

                updateRegionContent(regionId, content, color) {
                    if (!this.regionsPlugin) return;

                    const regions = this.regionsPlugin.getRegions();
                    const region = regions.find(r => r.id === regionId);
                    if (region) {
                        region.setContent(content);
                        if (color) {
                            region.setOptions({
                                color
                            });
                        }
                    }

                    // Update local regions array
                    const index = this.regions.findIndex(r => r.id === regionId);
                    if (index !== -1) {
                        this.regions[index].content = content;
                        if (color) {
                            this.regions[index].color = color;
                        }
                    }
                },

                formatTime(seconds) {
                    if (!seconds || isNaN(seconds)) return '0:00';
                    const minutes = Math.floor(seconds / 60);
                    const remainingSeconds = Math.floor(seconds % 60);
                    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
                },

                formatRegionTime(region) {
                    const start = this.formatTime(region.start);
                    const end = this.formatTime(region.end);
                    const duration = this.formatTime(region.end - region.start);
                    return `${start} - ${end} (${duration})`;
                },

                destroy() {
                    if (this.regionsPlugin) {
                        this.regionsPlugin.destroy();
                        this.regionsPlugin = null;
                    }
                    if (this.wavesurfer) {
                        this.wavesurfer.destroy();
                        this.wavesurfer = null;
                    }
                }
            }));
        });

        function audioAnnotator() {
            return {
                showModal: false,
                modalMode: 'create',
                currentRegion: null,
                annotationText: '',
                annotationCategory: 'speech',
                annotationColor: '#ef4444',

                categories: [{
                        value: 'speech',
                        label: 'Speech',
                        color: '#3b82f6'
                    },
                    {
                        value: 'music',
                        label: 'Music',
                        color: '#10b981'
                    },
                    {
                        value: 'noise',
                        label: 'Noise',
                        color: '#f59e0b'
                    },
                    {
                        value: 'silence',
                        label: 'Silence',
                        color: '#6b7280'
                    }
                ],

                openAnnotationModal(mode, region) {
                    console.log(region);
                    this.modalMode = mode;
                    this.currentRegion = region;
                    this.annotationText = region.content || '';

                    // Find category by color
                    const category = this.categories.find(c => c.color === region.color);
                    this.annotationCategory = category ? category.value : 'speech';
                    this.annotationColor = region.color || '#3b82f6';

                    this.showModal = true;
                },

                saveAnnotation() {
                    const category = this.categories.find(c => c.value === this.annotationCategory);
                    const color = category ? category.color : this.annotationColor;

                    this.$refs.waveform.updateRegionContent(
                        this.currentRegion.id,
                        this.annotationText,
                        color
                    );

                    // Here you would save to your backend
                    this.saveToBackend({
                        id: this.currentRegion.id,
                        start: this.currentRegion.start,
                        end: this.currentRegion.end,
                        content: this.annotationText,
                        category: this.annotationCategory,
                        color: color
                    });

                    this.closeModal();
                },

                deleteAnnotation(regionId) {
                    if (confirm('Are you sure you want to delete this annotation?')) {
                        this.$refs.waveform.removeRegion(regionId);
                        // Delete from backend
                        this.deleteFromBackend(regionId);
                        this.closeModal();
                    }
                },

                closeModal() {
                    this.showModal = false;
                    this.currentRegion = null;
                    this.annotationText = '';
                },

                formatTime(seconds) {
                    if (!seconds || isNaN(seconds)) return '0:00';
                    const minutes = Math.floor(seconds / 60);
                    const remainingSeconds = Math.floor(seconds % 60);
                    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
                },

                saveToBackend(annotation) {
                    console.log('Saving annotation:', annotation);
                    // Implement your API call here
                },

                deleteFromBackend(regionId) {
                    console.log('Deleting annotation:', regionId);
                    // Implement your API call here
                },

                getCategoryLabel(region) {
                    const category = this.categories.find(c => c.color === region.color);
                    return category ? category.label : 'Unknown';
                }
            };
        }
    </script>

    <div class="container" x-data="audioAnnotator()">
        <!-- Header -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <h1 class="card-title">Audio Annotator</h1>
            </div>
        </div>

        <!-- Main Audio Interface -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <h2 class="card-title">Audio File</h2>
            </div>
            <div class="card-body">
                <!-- Waveform -->
                <div x-data="wavesurfer({
                    onNewSelection: (region) => openAnnotationModal('create', region)
                })" x-ref="waveform">
                    <!-- File Upload -->
                    <div class="form-group">
                        <label for="audioFile" class="form-label">Select Audio File</label>
                        <input type="file" id="audioFile" class="form-input" accept="audio/*"
                            @change="loadFile($event.target.files[0])">
                    </div>
                    <!-- Status -->
                    <div x-show="loading" class="status-indicator status-loading">
                        <svg class="icon animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Loading audio...
                    </div>

                    <div x-show="isReady && !loading" class="status-indicator status-ready">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Audio ready
                    </div>

                    <div x-show="error" class="status-indicator status-error">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span x-text="error"></span>
                    </div>

                    <!-- Waveform Container -->
                    <div class="waveform-container"></div>

                    <!-- Controls -->
                    <div class="controls">
                        <button @click="playPause()" :disabled="!isReady" class="btn btn-primary">
                            <svg x-show="!isPlaying" class="icon" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <svg x-show="isPlaying" class="icon" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span x-text="isPlaying ? 'Pause' : 'Play'"></span>
                        </button>

                        <button @click="stop()" :disabled="!isReady" class="btn btn-secondary">
                            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z">
                                </path>
                            </svg>
                            Stop
                        </button>

                        <button @click="selectionMode ? disableSelection() : enableSelection()" :disabled="!isReady"
                            class="btn" :class="selectionMode ? 'btn-success' : 'btn-secondary'">
                            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                </path>
                            </svg>
                            <span x-text="selectionMode ? 'Stop Annotating' : 'Start Annotating'"></span>
                        </button>

                        <button @click="clearRegions()" :disabled="!isReady || regions.length === 0"
                            class="btn btn-danger btn-sm">
                            Clear All
                        </button>

                        <div class="volume-control">
                            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z">
                                </path>
                            </svg>
                            <input type="range" min="0" max="1" step="0.1" x-model="volume"
                                @input="setVolume(parseFloat($event.target.value))" class="volume-slider"
                                :disabled="!isReady">
                            <span class="text-xs text-gray-500" x-text="Math.round(volume * 100) + '%'"></span>
                        </div>

                        <div x-show="isReady" class="time-display">
                            <span x-text="formatTime(currentTime)"></span> / <span
                                x-text="formatTime(duration)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Annotations List -->
        <div class="card" x-show="$refs.waveform?.regions?.length > 0">
            <div class="card-header">
                <h2 class="card-title">Annotations</h2>
            </div>
            <div class="card-body">
                <div class="annotations-list">
                    <template x-for="region in $refs.waveform?.regions || []" :key="region.id">
                        <div class="annotation-item">
                            <div class="annotation-info">
                                <div class="annotation-text" x-text="region.content || 'Untitled annotation'"></div>
                                <div class="annotation-meta">
                                    <span x-text="$refs.waveform.formatRegionTime(region)"></span>
                                    •
                                    <span x-text="getCategoryLabel(region)"></span>
                                </div>
                            </div>
                            <div class="annotation-actions">
                                <button @click="$refs.waveform.playRegion(region.id)"
                                    class="btn btn-secondary btn-sm">
                                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m-7 4h8a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    Play
                                </button>
                                <button @click="openAnnotationModal('edit', region)" class="btn btn-secondary btn-sm">
                                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                    Edit
                                </button>
                                <button @click="deleteAnnotation(region.id)" class="btn btn-danger btn-sm">
                                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div class="card"
            x-show="$refs.waveform?.isReady && (!$refs.waveform?.regions || $refs.waveform?.regions.length === 0)">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <svg class="icon" style="width: 3rem; height: 3rem; margin: 0 auto 1rem; color: var(--gray-400);"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 011 1v1a1 1 0 01-1 1h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7H3a1 1 0 01-1-1V5a1 1 0 011-1h4zM9 3v1h6V3H9z">
                    </path>
                </svg>
                <h3 style="color: var(--gray-500); margin-bottom: 0.5rem;">No annotations yet</h3>
                <p style="color: var(--gray-400); margin-bottom: 1.5rem;">Click "Start Annotating" and drag on the
                    waveform to create your first annotation.</p>
                <button @click="$refs.waveform.enableSelection()" class="btn btn-primary">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                        </path>
                    </svg>
                    Start Annotating
                </button>
            </div>
        </div>

        <!-- Annotation Modal -->
        <div x-show="showModal" class="modal-overlay" x-transition>
            <div class="modal" @click.away="closeModal()">
                <div class="modal-header">
                    <h3 class="modal-title" x-text="modalMode === 'create' ? 'Add Annotation' : 'Edit Annotation'">
                    </h3>
                </div>

                <div class="modal-body">
                    <!-- Time Range Display -->
                    <div class="form-group">
                        <label class="form-label">Time Range</label>
                        <div class="time-display" style="display: inline-block;">
                            <span x-text="formatTime(currentRegion?.start)"></span> -
                            <span x-text="formatTime(currentRegion?.end)"></span>
                            (<span x-text="formatTime(currentRegion?.duration)"></span>)
                        </div>
                    </div>

                    <!-- Annotation Text -->
                    <div class="form-group">
                        <label for="annotationText" class="form-label">Annotation Text</label>
                        <textarea id="annotationText" x-model="annotationText" class="form-input" rows="3"
                            placeholder="Enter your annotation..." style="resize: vertical;"></textarea>
                    </div>

                    <!-- Category Selection -->
                    <div class="form-group">
                        <label for="annotationCategory" class="form-label">Category</label>
                        <select id="annotationCategory" x-model="annotationCategory" class="form-input">
                            <template x-for="category in categories" :key="category.value">
                                <option :value="category.value" x-text="category.label"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Color Preview -->
                    <div class="form-group">
                        <label class="form-label">Color</label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div
                                :style="`width: 2rem; height: 1.5rem; border-radius: 0.25rem; border: 1px solid var(--gray-300); background-color: ${categories.find(c => c.value === annotationCategory)?.color || annotationColor}`">
                            </div>
                            <span class="text-sm text-gray-600"
                                x-text="categories.find(c => c.value === annotationCategory)?.label || 'Custom'"></span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button @click="saveAnnotation()" class="btn btn-primary">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Save Annotation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Styles for Text Utilities -->
    <style>
        .text-xs {
            font-size: 0.75rem;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .text-gray-500 {
            color: var(--gray-500);
        }

        .text-gray-600 {
            color: var(--gray-600);
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Transition animations */
        [x-transition] {
            transition: all 0.3s ease;
        }

        /* Better responsive design */
        @media (max-width: 768px) {
            .container {
                padding: 0.5rem;
            }

            .modal {
                margin: 0.5rem;
            }

            .modal-footer {
                flex-direction: column-reverse;
            }

            .modal-footer .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</body>

</html>
