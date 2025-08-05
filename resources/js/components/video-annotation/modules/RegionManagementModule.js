/**
 * RegionManagementModule - Handles video region creation, editing, and management
 * Manages time-based regions on the video timeline for annotations
 */
export class RegionManagementModule {
    constructor(core, sharedState, config) {
        this.core = core;
        this.sharedState = sharedState;
        this.config = config;
        
        // Region creation state
        this.creationInProgress = false;
        this.creationStartTime = null;
        this.tempRegion = null;
        
        // Region editing state
        this.editingRegion = null;
        this.resizeHandle = null; // 'start' | 'end' | null
        this.resizeStartX = 0;
        this.resizeOriginalTime = 0;
        
        // Region display state
        this.hoveredRegion = null;
        this.selectedRegion = null;
    }
    
    /**
     * Initialize region management
     */
    init($refs, $dispatch) {
        this.$refs = $refs;
        this.$dispatch = $dispatch;
        return this;
    }
    
    /**
     * Start region creation at current time
     */
    startRegionCreation() {
        if (!this.config.annotations?.enableVideoAnnotations) return;
        
        this.creationInProgress = true;
        this.creationStartTime = this.sharedState.currentTime;
        this.sharedState.touchInterface.mode = 'REGION_CREATE';
        
        // Create temporary region for preview
        this.tempRegion = {
            id: `temp-${Date.now()}`,
            startTime: this.creationStartTime,
            endTime: this.creationStartTime,
            title: '',
            description: '',
            temporary: true
        };
        
        // Pause video during region creation
        if (this.sharedState.isPlaying) {
            this.core.togglePlayPause();
        }
        
        this.$dispatch('video-annotation:region-creation-started', {
            startTime: this.creationStartTime
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[RegionManagement] Started region creation at ${this.creationStartTime}s`);
        }
    }
    
    /**
     * Update region creation end time
     */
    updateRegionCreation(endTime) {
        if (!this.creationInProgress || !this.tempRegion) return;
        
        const startTime = this.creationStartTime;
        
        // Ensure end time is after start time
        if (endTime > startTime) {
            this.tempRegion.endTime = endTime;
        } else {
            // If dragging backwards, swap start and end times
            this.tempRegion.startTime = endTime;
            this.tempRegion.endTime = startTime;
        }
        
        // Update region preview
        this.$dispatch('video-annotation:region-preview-updated', {
            region: { ...this.tempRegion }
        });
    }
    
    /**
     * Finish region creation
     */
    finishRegionCreation() {
        if (!this.creationInProgress || !this.tempRegion) return;
        
        const region = {
            id: `region-${Date.now()}`,
            startTime: this.tempRegion.startTime,
            endTime: this.tempRegion.endTime,
            startFrame: this.sharedState.getFrameNumber(this.tempRegion.startTime),
            endFrame: this.sharedState.getFrameNumber(this.tempRegion.endTime),
            title: `Region ${this.sharedState.regions.length + 1}`,
            description: '',
            color: this.getNextRegionColor(),
            temporary: false
        };
        
        // Add region to shared state
        this.sharedState.addRegion(region);
        
        // Clean up creation state
        this.creationInProgress = false;
        this.creationStartTime = null;
        this.tempRegion = null;
        this.sharedState.touchInterface.mode = 'NORMAL';
        
        this.$dispatch('video-annotation:region-created', { region });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[RegionManagement] Created region: ${region.id} (${region.startTime}s - ${region.endTime}s)`);
        }
        
        return region;
    }
    
    /**
     * Cancel region creation
     */
    cancelRegionCreation() {
        if (!this.creationInProgress) return;
        
        this.creationInProgress = false;
        this.creationStartTime = null;
        this.tempRegion = null;
        this.sharedState.touchInterface.mode = 'NORMAL';
        
        this.$dispatch('video-annotation:region-creation-cancelled');
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log('[RegionManagement] Cancelled region creation');
        }
    }
    
    /**
     * Select a region
     */
    selectRegion(regionId) {
        const region = this.sharedState.getRegion(regionId);
        if (!region) return;
        
        this.selectedRegion = region;
        this.sharedState.activeRegion = region;
        
        this.$dispatch('video-annotation:region-selected', { region });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[RegionManagement] Selected region: ${regionId}`);
        }
    }
    
    /**
     * Deselect current region
     */
    deselectRegion() {
        this.selectedRegion = null;
        this.sharedState.activeRegion = null;
        
        this.$dispatch('video-annotation:region-deselected');
    }
    
    /**
     * Start region editing
     */
    startRegionEdit(regionId, handle = null) {
        const region = this.sharedState.getRegion(regionId);
        if (!region) return;
        
        this.editingRegion = region;
        this.resizeHandle = handle; // 'start', 'end', or null for move
        this.sharedState.touchInterface.mode = 'REGION_EDIT';
        
        this.$dispatch('video-annotation:region-edit-started', { 
            region, 
            handle: this.resizeHandle 
        });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[RegionManagement] Started editing region: ${regionId} (handle: ${handle})`);
        }
    }
    
    /**
     * Update region during edit
     */
    updateRegionEdit(newTime) {
        if (!this.editingRegion) return;
        
        const region = this.editingRegion;
        
        if (this.resizeHandle === 'start') {
            // Resize from start
            if (newTime < region.endTime) {
                region.startTime = newTime;
                region.startFrame = this.sharedState.getFrameNumber(newTime);
            }
        } else if (this.resizeHandle === 'end') {
            // Resize from end
            if (newTime > region.startTime) {
                region.endTime = newTime;
                region.endFrame = this.sharedState.getFrameNumber(newTime);
            }
        } else {
            // Move entire region
            const duration = region.endTime - region.startTime;
            region.startTime = newTime;
            region.endTime = newTime + duration;
            region.startFrame = this.sharedState.getFrameNumber(region.startTime);
            region.endFrame = this.sharedState.getFrameNumber(region.endTime);
        }
        
        this.$dispatch('video-annotation:region-updated', { region });
    }
    
    /**
     * Finish region editing
     */
    finishRegionEdit() {
        if (!this.editingRegion) return;
        
        const region = this.editingRegion;
        
        // Clean up editing state
        this.editingRegion = null;
        this.resizeHandle = null;
        this.sharedState.touchInterface.mode = 'NORMAL';
        
        this.$dispatch('video-annotation:region-edit-finished', { region });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[RegionManagement] Finished editing region: ${region.id}`);
        }
    }
    
    /**
     * Delete a region
     */
    deleteRegion(regionId) {
        const region = this.sharedState.getRegion(regionId);
        if (!region) return;
        
        this.sharedState.removeRegion(regionId);
        
        // Clean up any active editing
        if (this.editingRegion?.id === regionId) {
            this.editingRegion = null;
            this.resizeHandle = null;
            this.sharedState.touchInterface.mode = 'NORMAL';
        }
        
        // Clean up selection
        if (this.selectedRegion?.id === regionId) {
            this.selectedRegion = null;
        }
        
        this.$dispatch('video-annotation:region-deleted', { regionId });
        
        if (this.sharedState.contextDisplay.debugMode) {
            console.log(`[RegionManagement] Deleted region: ${regionId}`);
        }
    }
    
    /**
     * Toggle region visibility
     */
    toggleRegionVisibility(regionId) {
        if (this.sharedState.hiddenRegions.has(regionId)) {
            this.sharedState.hiddenRegions.delete(regionId);
        } else {
            this.sharedState.hiddenRegions.add(regionId);
        }
        
        this.$dispatch('video-annotation:region-visibility-toggled', { 
            regionId, 
            hidden: this.sharedState.hiddenRegions.has(regionId) 
        });
    }
    
    /**
     * Get region position on progress bar
     */
    getRegionPosition(region) {
        if (!region || !this.sharedState.duration) {
            return { left: 0, width: 0 };
        }
        
        const startPercentage = (region.startTime / this.sharedState.duration) * 100;
        const endPercentage = (region.endTime / this.sharedState.duration) * 100;
        const widthPercentage = endPercentage - startPercentage;
        
        return {
            left: Math.max(0, Math.min(100, startPercentage)),
            width: Math.max(0, Math.min(100 - startPercentage, widthPercentage))
        };
    }
    
    /**
     * Get all visible regions for rendering
     */
    getVisibleRegions() {
        return this.sharedState.regions
            .filter(region => !this.sharedState.hiddenRegions.has(region.id))
            .map(region => ({
                ...region,
                position: this.getRegionPosition(region),
                isActive: this.sharedState.activeRegion?.id === region.id,
                isSelected: this.selectedRegion?.id === region.id,
                isEditing: this.editingRegion?.id === region.id,
                isHovered: this.hoveredRegion?.id === region.id
            }));
    }
    
    /**
     * Get regions that contain the current time
     */
    getCurrentRegions() {
        return this.sharedState.getActiveRegions();
    }
    
    /**
     * Get next color for new regions
     */
    getNextRegionColor() {
        const colors = [
            '#3b82f6', // blue
            '#10b981', // emerald
            '#f59e0b', // amber
            '#ef4444', // red
            '#8b5cf6', // violet
            '#06b6d4', // cyan
            '#84cc16', // lime
            '#f97316'  // orange
        ];
        
        return colors[this.sharedState.regions.length % colors.length];
    }
    
    /**
     * Handle region hover
     */
    handleRegionHover(regionId, event) {
        const region = this.sharedState.getRegion(regionId);
        if (!region) return;
        
        this.hoveredRegion = region;
        
        // Show region tooltip
        this.sharedState.showRegionTooltip = regionId;
        
        this.$dispatch('video-annotation:region-hovered', { region, event });
    }
    
    /**
     * Handle region hover end
     */
    handleRegionHoverEnd(regionId) {
        if (this.hoveredRegion?.id === regionId) {
            this.hoveredRegion = null;
        }
        
        // Hide region tooltip
        if (this.sharedState.showRegionTooltip === regionId) {
            this.sharedState.showRegionTooltip = null;
        }
        
        this.$dispatch('video-annotation:region-hover-ended', { regionId });
    }
    
    /**
     * Handle region click
     */
    handleRegionClick(regionId, event) {
        if (event) {
            event.stopPropagation();
        }
        
        const region = this.sharedState.getRegion(regionId);
        if (!region) return;
        
        // Select the region
        this.selectRegion(regionId);
        
        // Seek to region start
        this.core.seekTo(region.startTime);
        
        this.$dispatch('video-annotation:region-clicked', { region });
    }
    
    /**
     * Update region metadata
     */
    updateRegionMetadata(regionId, metadata) {
        const region = this.sharedState.getRegion(regionId);
        if (!region) return;
        
        // Update region properties
        Object.assign(region, metadata);
        
        this.$dispatch('video-annotation:region-metadata-updated', { region });
    }
    
    /**
     * Export regions data
     */
    exportRegions() {
        return this.sharedState.regions.map(region => ({
            id: region.id,
            startTime: region.startTime,
            endTime: region.endTime,
            startFrame: region.startFrame,
            endFrame: region.endFrame,
            title: region.title,
            description: region.description,
            color: region.color
        }));
    }
    
    /**
     * Import regions data
     */
    importRegions(regionsData) {
        if (!Array.isArray(regionsData)) return;
        
        regionsData.forEach(regionData => {
            const region = {
                ...regionData,
                id: regionData.id || `region-${Date.now()}-${Math.random()}`
            };
            
            this.sharedState.addRegion(region);
        });
        
        this.$dispatch('video-annotation:regions-imported', { count: regionsData.length });
    }
    
    /**
     * Dispose of region management
     */
    dispose() {
        this.cancelRegionCreation();
        this.editingRegion = null;
        this.selectedRegion = null;
        this.hoveredRegion = null;
        this.sharedState.showRegionTooltip = null;
    }
    
    /**
     * Public API for Alpine.js integration
     */
    getAlpineMethods() {
        return {
            // Region creation methods
            startRegionCreation: this.startRegionCreation.bind(this),
            updateRegionCreation: this.updateRegionCreation.bind(this),
            finishRegionCreation: this.finishRegionCreation.bind(this),
            cancelRegionCreation: this.cancelRegionCreation.bind(this),
            
            // Region selection methods
            selectRegion: this.selectRegion.bind(this),
            deselectRegion: this.deselectRegion.bind(this),
            
            // Region editing methods
            startRegionEdit: this.startRegionEdit.bind(this),
            updateRegionEdit: this.updateRegionEdit.bind(this),
            finishRegionEdit: this.finishRegionEdit.bind(this),
            
            // Region management methods
            deleteRegion: this.deleteRegion.bind(this),
            toggleRegionVisibility: this.toggleRegionVisibility.bind(this),
            updateRegionMetadata: this.updateRegionMetadata.bind(this),
            
            // Region interaction methods
            handleRegionClick: this.handleRegionClick.bind(this),
            handleRegionHover: this.handleRegionHover.bind(this),
            handleRegionHoverEnd: this.handleRegionHoverEnd.bind(this),
            
            // Region data methods
            getVisibleRegions: this.getVisibleRegions.bind(this),
            getCurrentRegions: this.getCurrentRegions.bind(this),
            getRegionPosition: this.getRegionPosition.bind(this),
            exportRegions: this.exportRegions.bind(this),
            importRegions: this.importRegions.bind(this),
            
            // Getters for reactive properties
            get regions() { return this.sharedState.regions; },
            get activeRegion() { return this.sharedState.activeRegion; },
            get regionCreationActive() { return this.creationInProgress; },
            get tempRegion() { return this.tempRegion; },
            get editingRegion() { return this.editingRegion; },
            get selectedRegion() { return this.selectedRegion; }
        };
    }
}