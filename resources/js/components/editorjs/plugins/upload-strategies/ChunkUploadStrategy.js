/**
 * Chunk Upload Strategy for VideoUpload Plugin
 * Handles large files that need to be uploaded in chunks
 */

export default class ChunkUploadStrategy {
    constructor(config, t) {
        this.config = config;
        this.t = t;
        this.progressCallback = null;
    }

    /**
     * Determine if this strategy should be used for the given file
     */
    canHandle(file) {
        return file.size >= this.config.maxSingleFileSize;
    }

    /**
     * Set progress callback function
     */
    setProgressCallback(callback) {
        this.progressCallback = callback;
        return this;
    }

    /**
     * Execute chunked file upload
     */
    async execute(file) {
        const fileKey = this.generateFileKey();
        const fileName = file.name;
        const chunkSize = this.config.chunkSize;
        const totalChunks = Math.ceil(file.size / chunkSize);

        console.log(`Starting chunked upload: ${fileName} (${totalChunks} chunks)`);

        // Signal editor is busy during upload
        document.dispatchEvent(new CustomEvent('editor:busy'));

        try {
            // Upload each chunk sequentially
            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                const start = chunkIndex * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                const chunk = file.slice(start, end);

                console.log(`Uploading chunk ${chunkIndex + 1}/${totalChunks}`);

                const chunkFile = new File([chunk], fileName, { type: file.type });
                const response = await this.uploadChunk(chunkFile, fileKey, fileName, chunkIndex, totalChunks);

                // Update progress based on chunk completion
                const progress = Math.round(((chunkIndex + 1) / totalChunks) * 100);
                if (this.progressCallback) {
                    this.progressCallback(progress);
                }

                // If this was the last chunk and upload is complete
                if (response.completed) {
                    console.log('Chunked upload completed successfully');
                    return response;
                }
            }

            throw new Error('Chunked upload completed but no final response received');

        } catch (error) {
            console.error('Chunked upload failed:', error);
            throw error;
        }
    }

    /**
     * Upload a single chunk
     */
    async uploadChunk(chunkFile, fileKey, fileName, chunkIndex, totalChunks) {
        const formData = new FormData();
        formData.append(this.config.field, chunkFile);
        formData.append('fileKey', fileKey);
        formData.append('fileName', fileName);
        formData.append('chunk', chunkIndex.toString());
        formData.append('chunks', totalChunks.toString());

        const response = await fetch(this.config.endpoints.chunk, {
            method: 'POST',
            body: formData,
            headers: {
                ...this.config.additionalRequestHeaders,
                // Don't set Content-Type for FormData, let browser set it with boundary
            }
        });

        // Parse JSON response
        let json;
        try {
            json = await response.json();
        } catch (parseError) {
            throw new Error(`Invalid response format for chunk ${chunkIndex}: ${response.status}`);
        }

        // Validate response success
        if (json.success === false || !response.ok) {
            throw new Error(json.message || `HTTP ${response.status}: ${response.statusText}`);
        }

        return json;
    }

    /**
     * Generate a unique file key for chunked uploads
     */
    generateFileKey() {
        return `video_${Date.now()}_${Math.random().toString(36).substring(2, 11)}`;
    }

    /**
     * Get strategy name for logging/debugging
     */
    getName() {
        return 'chunk';
    }
}