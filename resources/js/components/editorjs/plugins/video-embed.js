/**
 * VideoEmbed Plugin for EditorJS
 * Allows embedding videos from YouTube URLs with Video.js integration
 */

// Import Video.js and YouTube plugin
import videojs from 'video.js';
import 'videojs-youtube';

// Import the plugin styles
import '../../../../css/components/editorjs/video-embed.css';

class VideoEmbed {
    static get toolbox() {
        return {
            title: 'Video',
            icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v-4l6 2-6 2z"/>
            </svg>`
        };
    }

    static get isInline() {
        return false;
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get enableLineBreaks() {
        return true;
    }

    static get tunes() {
        return [];
    }

    static get supportsPasteOperations() {
        return true;
    }

    constructor({ data, config, api, readOnly, block }) {
        this.api = api;
        this.blockAPI = block;
        this.readOnly = readOnly;
        this.config = config || {};
        this.data = data || {};

        // Initialize localization
        this.t = this.initializeLocalization();

        // Default configuration
        this.defaultConfig = {
            placeholder: this.t.placeholder,
            width: '100%',
            height: 'auto',
            aspectRatio: '16:9',
            useVideoJS: false, // Use direct iframe embedding by default
            allowDirectUrls: true
        };

        this.config = Object.assign(this.defaultConfig, this.config);

        // Initialize state
        this.wrapper = null;
        this.urlInput = null;
        this.videoContainer = null;
        this.isLoading = false;
        this.isRendering = false;
        this.currentVideoId = null;

        // Bind methods
        this.handleUrlInput = this.handleUrlInput.bind(this);
        this.handlePaste = this.handlePaste.bind(this);
    }

    initializeLocalization() {
        // Detect current locale from HTML lang attribute or other sources
        const htmlElement = document.documentElement;
        const currentLocale = htmlElement.lang || 'en';
        const locale = currentLocale.split('-')[0]; // Get base locale (e.g., 'en' from 'en-US')
        
        // Define translations for VideoEmbed plugin
        const translations = {
            'en': {
                placeholder: 'Paste a YouTube URL...',
                embedButton: 'Embed Video',
                captionPlaceholder: 'Add a caption for this video...',
                loading: 'Loading video...',
                retry: 'Try Again',
                errors: {
                    unsupportedFormat: 'Unsupported video URL format',
                    invalidYoutube: 'Invalid YouTube URL',
                    failedToLoad: 'Failed to load video. Please check the URL and try again.',
                    invalidUrl: 'Please enter a valid YouTube URL or direct video URL'
                }
            },
            'ar': {
                placeholder: 'الصق رابط يوتيوب...',
                embedButton: 'تضمين الفيديو',
                captionPlaceholder: 'أضف تسمية توضيحية لهذا الفيديو...',
                loading: 'جاري تحميل الفيديو...',
                retry: 'حاول مرة أخرى',
                errors: {
                    unsupportedFormat: 'تنسيق رابط الفيديو غير مدعوم',
                    invalidYoutube: 'رابط يوتيوب غير صالح',
                    failedToLoad: 'فشل في تحميل الفيديو. يرجى التحقق من الرابط والمحاولة مرة أخرى.',
                    invalidUrl: 'يرجى إدخال رابط يوتيوب صالح أو رابط فيديو مباشر'
                }
            }
        };
        
        return translations[locale] || translations['en'];
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.classList.add('video-embed');
        this.wrapper = wrapper;

        // Check if we have existing video data
        if (this.data.url) {
            // If we have a URL but no type, determine it
            if (!this.data.type) {
                this.data.type = this.isYouTubeUrl(this.data.url) ? 'youtube' : 'direct';
            }
            
            // If YouTube URL but no videoId, extract it
            if (this.data.type === 'youtube' && !this.data.videoId) {
                this.data.videoId = this.extractYouTubeId(this.data.url);
                this.data.embedUrl = `https://www.youtube.com/embed/${this.data.videoId}`;
            }
            
            // Render for existing saved content
            requestAnimationFrame(() => {
                this.renderVideo();
            });
        } else {
            this.renderUrlInput();
        }

        return wrapper;
    }

    renderUrlInput() {
        if (this.readOnly) {
            return;
        }

        const inputContainer = document.createElement('div');
        inputContainer.classList.add('video-embed__input-container');

        const urlInput = document.createElement('input');
        urlInput.type = 'url';
        urlInput.classList.add('video-embed__url-input');
        urlInput.placeholder = this.t.placeholder;
        urlInput.value = this.data.url || '';

        // Create submit button
        const submitButton = document.createElement('button');
        submitButton.classList.add('video-embed__submit-btn');
        submitButton.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>${this.t.embedButton}</span>
        `;

        // Event listeners
        urlInput.addEventListener('input', this.handleUrlInput);
        urlInput.addEventListener('paste', this.handlePaste);
        urlInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.processUrl(urlInput.value);
            }
        });

        submitButton.addEventListener('click', (e) => {
            e.preventDefault();
            this.processUrl(urlInput.value);
        });

        this.urlInput = urlInput;

        inputContainer.appendChild(urlInput);
        inputContainer.appendChild(submitButton);
        this.wrapper.appendChild(inputContainer);
    }

    renderVideo() {
        // Prevent multiple simultaneous renders
        if (this.isRendering) {
            console.log('Video already rendering, skipping duplicate render');
            return;
        }
        
        this.isRendering = true;
        
        // Remove existing content
        this.wrapper.innerHTML = '';

        const videoContainer = document.createElement('div');
        videoContainer.classList.add('video-embed__container');

        // Create video element
        const videoWrapper = document.createElement('div');
        videoWrapper.classList.add('video-embed__video-wrapper');
        videoWrapper.style.aspectRatio = this.config.aspectRatio;

        if (this.isYouTubeUrl(this.data.url)) {
            this.renderYouTubeVideo(videoWrapper);
        } else if (this.config.allowDirectUrls && this.isDirectVideoUrl(this.data.url)) {
            this.renderDirectVideo(videoWrapper);
        } else {
            this.renderError(this.t.errors.unsupportedFormat);
            this.isRendering = false;
            return;
        }

        videoContainer.appendChild(videoWrapper);

        // Add caption input if not readonly
        if (!this.readOnly) {
            const captionInput = this.createCaptionInput();
            if (captionInput) {
                videoContainer.appendChild(captionInput);
            }
        } else if (this.data.caption && this.data.caption.trim() !== '') {
            // Show caption in readonly mode
            const captionDisplay = document.createElement('div');
            captionDisplay.classList.add('video-embed__caption', 'video-embed__caption--readonly');
            captionDisplay.textContent = this.data.caption;
            videoContainer.appendChild(captionDisplay);
        }

        this.wrapper.appendChild(videoContainer);
        this.videoContainer = videoContainer;
        
        // Reset rendering flag
        this.isRendering = false;
    }

    renderYouTubeVideo(container) {
        const videoId = this.extractYouTubeId(this.data.url);
        if (!videoId) {
            this.renderError(this.t.errors.invalidYoutube);
            this.isRendering = false;
            return;
        }

        // Check if we're already rendering this exact video
        if (this.currentVideoId === videoId) {
            console.log('Same video already rendered, skipping:', videoId);
            this.isRendering = false;
            return;
        }

        // Store video ID for later use
        this.data.videoId = videoId;
        this.data.embedUrl = `https://www.youtube.com/embed/${videoId}`;
        this.currentVideoId = videoId;

        // Use direct YouTube iframe embedding for cleaner experience
        console.log('Using direct iframe for YouTube:', videoId);
        this.renderYouTubeIframe(container, videoId);
    }

    renderVideoJSPlayer(container, videoId) {
        // Create video element with data-setup - Video.js handles the rest automatically
        const video = document.createElement('video');
        video.classList.add('video-js', 'vjs-default-skin');
        video.controls = true;
        video.style.width = '100%';
        video.style.height = '100%';
        
        // Generate unique ID for the video element
        const uniqueId = `video-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        video.id = uniqueId;

        // Use data-setup attribute - Video.js will auto-initialize
        const dataSetup = {
            techOrder: ['youtube'],
            sources: [{
                type: 'video/youtube',
                src: `https://www.youtube.com/watch?v=${videoId}`
            }],
            fluid: true,
            responsive: true
        };
        
        video.setAttribute('data-setup', JSON.stringify(dataSetup));

        // Just add to container - Video.js handles everything else
        container.appendChild(video);
        
        // Let Video.js auto-initialize via data-setup attribute
        console.log('Video.js element created, will auto-initialize:', videoId);
    }


    renderYouTubeIframe(container, videoId) {
        // Clear container if Video.js failed
        container.innerHTML = '';

        const iframe = document.createElement('iframe');
        iframe.src = `https://www.youtube.com/embed/${videoId}?modestbranding=1&rel=0`;
        iframe.width = '100%';
        iframe.height = '100%';
        iframe.frameBorder = '0';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        iframe.allowFullscreen = true;
        iframe.style.border = 'none';
        iframe.setAttribute('aria-label', `YouTube video: ${this.data.caption || 'Embedded video'}`);

        container.appendChild(iframe);
    }

    renderDirectVideo(container) {
        // Use native HTML5 video for direct video files
        console.log('Using HTML5 video for direct video:', this.data.url);
        this.renderHtml5Video(container);
    }

    renderHtml5Video(container) {
        // Clear container
        container.innerHTML = '';
        
        const video = document.createElement('video');
        video.src = this.data.url;
        video.controls = true;
        video.preload = 'metadata';
        video.style.width = '100%';
        video.style.height = '100%';
        video.setAttribute('aria-label', `Video: ${this.data.caption || 'Embedded video'}`);

        container.appendChild(video);
    }

    getVideoType(url) {
        const extension = url.split('.').pop().toLowerCase();
        const types = {
            'mp4': 'video/mp4',
            'webm': 'video/webm',
            'ogg': 'video/ogg',
            'mov': 'video/quicktime',
            'avi': 'video/x-msvideo',
            'wmv': 'video/x-ms-wmv',
            'flv': 'video/x-flv',
            'mkv': 'video/x-matroska'
        };
        return types[extension] || 'video/mp4';
    }

    renderError(message) {
        this.wrapper.innerHTML = '';

        const errorContainer = document.createElement('div');
        errorContainer.classList.add('video-embed__error');

        errorContainer.innerHTML = `
            <div class="video-embed__error-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="video-embed__error-message">${message}</div>
        `;

        if (!this.readOnly) {
            const retryButton = document.createElement('button');
            retryButton.classList.add('video-embed__retry-btn');
            retryButton.textContent = this.t.retry;
            retryButton.addEventListener('click', () => {
                this.data = {};
                this.renderUrlInput();
            });
            errorContainer.appendChild(retryButton);
        }

        this.wrapper.appendChild(errorContainer);
    }

    createCaptionInput() {
        const captionInput = document.createElement('input');
        captionInput.type = 'text';
        captionInput.classList.add('video-embed__caption');
        captionInput.placeholder = this.t.captionPlaceholder;
        captionInput.value = this.data.caption || '';

        captionInput.addEventListener('input', (e) => {
            this.data.caption = e.target.value;
        });

        return captionInput;
    }


    handleUrlInput(e) {
        const url = e.target.value.trim();
        if (url && (this.isYouTubeUrl(url) || (this.config.allowDirectUrls && this.isDirectVideoUrl(url)))) {
            e.target.classList.remove('video-embed__url-input--error');
        }
    }

    handlePaste(e) {
        setTimeout(() => {
            const url = e.target.value.trim();
            if (url && (this.isYouTubeUrl(url) || (this.config.allowDirectUrls && this.isDirectVideoUrl(url)))) {
                this.processUrl(url);
            }
        }, 100);
    }

    async processUrl(url) {
        if (!url || url.trim() === '') {
            return;
        }

        url = url.trim();

        // Validate URL
        if (!this.isYouTubeUrl(url) && !(this.config.allowDirectUrls && this.isDirectVideoUrl(url))) {
            this.showUrlError(this.t.errors.invalidUrl);
            return;
        }

        // Signal editor is busy during processing
        document.dispatchEvent(new CustomEvent('editor:busy'));

        // Show loading state
        this.showLoading();

        try {
            // Store the URL
            this.data.url = url;
            this.data.type = this.isYouTubeUrl(url) ? 'youtube' : 'direct';

            // For YouTube URLs, extract video ID
            if (this.data.type === 'youtube') {
                const videoId = this.extractYouTubeId(url);
                if (!videoId) {
                    throw new Error(this.t.errors.invalidYoutube);
                }
                this.data.videoId = videoId;
            }

            // Render the video
            this.renderVideo();

            // Notify editor of changes
            this.notifyEditorChange();

        } catch (error) {
            console.error('Failed to process video URL:', error);
            this.renderError(this.t.errors.failedToLoad);
            // Signal editor is free even on error
            setTimeout(() => {
                document.dispatchEvent(new CustomEvent('editor:free'));
            }, 0);
        }
    }

    showLoading() {
        this.wrapper.innerHTML = '';

        const loadingContainer = document.createElement('div');
        loadingContainer.classList.add('video-embed__loading');

        loadingContainer.innerHTML = `
            <div class="video-embed__loading-spinner">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="animate-spin">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div class="video-embed__loading-text">${this.t.loading}</div>
        `;

        this.wrapper.appendChild(loadingContainer);
        this.isLoading = true;
    }

    showUrlError(message) {
        if (this.urlInput) {
            this.urlInput.classList.add('video-embed__url-input--error');
            
            // Remove existing error message
            const existingError = this.wrapper.querySelector('.video-embed__input-error');
            if (existingError) {
                existingError.remove();
            }

            // Add error message
            const errorElement = document.createElement('div');
            errorElement.classList.add('video-embed__input-error');
            errorElement.textContent = message;
            
            this.urlInput.parentNode.appendChild(errorElement);

            // Remove error after 3 seconds
            setTimeout(() => {
                if (errorElement.parentNode) {
                    errorElement.remove();
                }
                this.urlInput.classList.remove('video-embed__url-input--error');
            }, 3000);
        }
    }

    isYouTubeUrl(url) {
        const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|m\.youtube\.com)\/.+/i;
        return youtubeRegex.test(url);
    }

    isDirectVideoUrl(url) {
        const videoExtensions = /\.(mp4|webm|ogg|mov|avi|wmv|flv|mkv)$/i;
        return videoExtensions.test(url) || url.includes('blob:') || url.includes('data:video');
    }

    extractYouTubeId(url) {
        const regexes = [
            /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
            /youtube\.com\/watch\?.*v=([^&\n?#]+)/,
            /youtube\.com\/v\/([^&\n?#]+)/,
            /youtube\.com\/user\/[^\/]+#p\/u\/\d+\/([^&\n?#]+)/,
            /youtube\.com\/.*[?&]v=([^&\n?#]+)/
        ];

        for (const regex of regexes) {
            const match = url.match(regex);
            if (match && match[1]) {
                return match[1];
            }
        }

        return null;
    }

    convertEmbedToWatchUrl(embedUrl) {
        // Convert YouTube embed URL to watch URL
        const match = embedUrl.match(/youtube\.com\/embed\/([^?&]+)/);
        if (match && match[1]) {
            return `https://www.youtube.com/watch?v=${match[1]}`;
        }
        return embedUrl;
    }

    notifyEditorChange() {
        if (this.blockAPI && this.blockAPI.dispatchChange) {
            this.blockAPI.dispatchChange();
        }
        setTimeout(() => {
            document.dispatchEvent(new CustomEvent('editor:free'));
        }, 0);
    }

    save() {
        return this.data;
    }

    validate(savedData) {
        if (!savedData) return false;

        // Allow empty data for new blocks
        if (Object.keys(savedData).length === 0) return true;

        // Must have a URL
        if (!savedData.url) return false;

        // Must be a valid URL format
        if (!this.isYouTubeUrl(savedData.url) && !(this.config.allowDirectUrls && this.isDirectVideoUrl(savedData.url))) {
            return false;
        }

        return true;
    }

    static get sanitize() {
        return {
            url: {},
            caption: {
                br: true,
            },
            videoId: {},
            embedUrl: {},
            type: {}
        };
    }

    static get pasteConfig() {
        return {
            patterns: {
                youtube: /https?:\/\/(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[^\s]+/
            },
            tags: ['IFRAME']
        };
    }

    onPaste(event) {
        console.log('VideoEmbed onPaste called:', event);
        
        switch (event.type) {
            case 'pattern':
                const url = event.detail.data;
                console.log('Pattern paste detected, URL:', url);
                console.log('Full event detail:', event.detail);
                
                // Check if this is a YouTube URL and create video
                if (this.isYouTubeUrl(url)) {
                    console.log('YouTube URL detected:', url);
                    const videoId = this.extractYouTubeId(url);
                    if (videoId) {
                        console.log('Video ID extracted:', videoId);
                        
                        // Set the data directly
                        this.data = {
                            url: url,
                            type: 'youtube',
                            videoId: videoId,
                            embedUrl: `https://www.youtube.com/embed/${videoId}`
                        };
                        
                        // Re-render to show the video
                        this.renderVideo();
                        console.log('Video created from paste');
                    }
                }
                break;
            
            case 'tag':
                const iframe = event.detail.data;
                const src = iframe.src || iframe.getAttribute('src');
                console.log('Tag paste detected, src:', src);
                
                if (src && /youtube\.com\/embed\//.test(src)) {
                    const match = src.match(/youtube\.com\/embed\/([^?&]+)/);
                    if (match && match[1]) {
                        const videoId = match[1];
                        const watchUrl = `https://www.youtube.com/watch?v=${videoId}`;
                        
                        // Set the data directly
                        this.data = {
                            url: watchUrl,
                            type: 'youtube',
                            videoId: videoId,
                            embedUrl: src
                        };
                        
                        // Re-render to show the video
                        this.renderVideo();
                        console.log('Video created from iframe paste');
                    }
                }
                break;
        }
    }

    static extractYouTubeIdStatic(url) {
        const regexes = [
            /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
            /youtube\.com\/watch\?.*v=([^&\n?#]+)/,
            /youtube\.com\/v\/([^&\n?#]+)/,
            /youtube\.com\/.*[?&]v=([^&\n?#]+)/
        ];

        for (const regex of regexes) {
            const match = url.match(regex);
            if (match && match[1]) {
                return match[1];
            }
        }

        return null;
    }


    destroy() {
        // Clear references - Video.js handles its own cleanup
        this.wrapper = null;
        this.urlInput = null;
        this.videoContainer = null;
    }
}

export default VideoEmbed;