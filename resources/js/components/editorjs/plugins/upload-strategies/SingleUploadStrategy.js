/**
 * Single Upload Strategy for VideoUpload Plugin
 * Handles small files that can be uploaded in one request
 */

export default class SingleUploadStrategy {
    constructor(config, t) {
        this.config = config;
        this.t = t;
    }

    /**
     * Determine if this strategy should be used for the given file
     */
    canHandle(file) {
        return file.size < this.config.maxSingleFileSize;
    }

    /**
     * Execute single file upload
     */
    async execute(file) {
        const formData = new FormData();
        formData.append(this.config.field, file);

        // Signal editor is busy during upload
        document.dispatchEvent(new CustomEvent('editor:busy'));

        try {
            const response = await fetch(this.config.endpoints.single, {
                method: 'POST',
                body: formData,
                headers: this.config.additionalRequestHeaders
            });

            // Parse JSON response
            let json;
            try {
                json = await response.json();
            } catch (parseError) {
                throw new Error(`Invalid response format: ${response.status}`);
            }

            // Validate response success
            if (json.success === 0 || json.success === false) {
                throw new Error(json.message || 'Upload failed');
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return json;
        } catch (error) {
            throw error;
        }
    }

    /**
     * Get strategy name for logging/debugging
     */
    getName() {
        return 'single';
    }
}