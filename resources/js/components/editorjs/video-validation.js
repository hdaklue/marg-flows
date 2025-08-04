/**
 * Single source of truth for video upload validation
 * Used across all EditorJS video upload components
 */

export const VIDEO_VALIDATION_CONFIG = {
    // File size limits
    maxFileSize: 250 * 1024 * 1024, // 250MB in bytes
    chunkSize: 10 * 1024 * 1024,    // 10MB chunks

    // Supported formats (Video.js compatible only)
    supportedExtensions: ['mp4', 'webm', 'ogg', 'ogv'],
    supportedMimeTypes: [
        'video/mp4',
        'video/webm',
        'video/ogg'
    ],

    // Upload settings
    useChunkedUpload: true,

    // Error messages
    errorMessages: {
        invalidType: 'Invalid file format. Please select a video file.',
        unsupportedFormat: 'Unsupported video format. Please use MP4, WebM, or OGV format for best compatibility.',
        fileTooLarge: (fileSizeMB, maxSizeMB) => `File is too large (${fileSizeMB}MB). Maximum size allowed is ${maxSizeMB}MB.`,
        uploadFailed: 'Upload failed. Please try again.',
        processingFailed: 'Failed to process pasted video file.'
    }
};

/**
 * Validate if video format is supported
 */
export function isVideoFormatSupported(file) {
    const fileName = file.name.toLowerCase();
    const fileType = file.type.toLowerCase();

    // Check by file extension (more reliable than MIME type)
    const extension = fileName.split('.').pop();

    if (!VIDEO_VALIDATION_CONFIG.supportedExtensions.includes(extension)) {
        console.log(`Unsupported file extension: ${extension} for file: ${file.name}`);
        return false;
    }

    // Check by MIME type as secondary validation
    if (fileType && !VIDEO_VALIDATION_CONFIG.supportedMimeTypes.includes(fileType)) {
        console.log(`Unsupported MIME type: ${fileType} for file: ${file.name}`);
        return false;
    }

    return true;
}

/**
 * Validate file size
 */
export function isFileSizeValid(file) {
    return file.size <= VIDEO_VALIDATION_CONFIG.maxFileSize;
}

/**
 * Get formatted file size in MB
 */
export function getFileSizeMB(file) {
    return Math.round(file.size / (1024 * 1024));
}

/**
 * Get max file size in MB
 */
export function getMaxFileSizeMB() {
    return Math.round(VIDEO_VALIDATION_CONFIG.maxFileSize / (1024 * 1024));
}

/**
 * Comprehensive file validation
 */
export function validateVideoFile(file) {
    const errors = [];

    // Check if it's a video file
    if (!file.type.startsWith('video/')) {
        errors.push(VIDEO_VALIDATION_CONFIG.errorMessages.invalidType);
        return { isValid: false, errors };
    }

    // Check format compatibility
    if (!isVideoFormatSupported(file)) {
        errors.push(VIDEO_VALIDATION_CONFIG.errorMessages.unsupportedFormat);
        return { isValid: false, errors };
    }

    // Check file size
    if (!isFileSizeValid(file)) {
        const fileSizeMB = getFileSizeMB(file);
        const maxSizeMB = getMaxFileSizeMB();
        errors.push(VIDEO_VALIDATION_CONFIG.errorMessages.fileTooLarge(fileSizeMB, maxSizeMB));
        return { isValid: false, errors };
    }

    return { isValid: true, errors: [] };
}
