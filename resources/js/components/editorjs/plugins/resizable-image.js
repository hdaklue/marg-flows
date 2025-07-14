/**
 * ResizableImage Plugin for EditorJS
 * Allows image upload with drag-to-resize functionality
 */

// Import the plugin styles
import '../../../../css/components/editorjs/resizable-image.css';
class ResizableImage {
  static get toolbox() {
    return {
      title: 'Resizable Image',
      icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
      </svg>`
    };
  }

  static get isReadOnlySupported() {
    return true;
  }

  static get enableLineBreaks() {
    return true;
  }


  constructor({ data, config, api, readOnly }) {
    this.api = api;
    this.readOnly = readOnly;
    this.config = config || {};
    this.data = data || {};
    
    // Initialize state flags
    this.uploading = false;
    this.wrapper = null;
    this.fileInput = null;
    this.uploadContainer = null;
    this.eventHandlers = null;
    
    // Default configuration
    this.defaultConfig = {
      endpoints: {
        byFile: '/upload-image',
        byUrl: '/upload-image-by-url'
      },
      additionalRequestHeaders: {},
      field: 'image',
      types: 'image/*',
      captionPlaceholder: 'Enter image caption...',
      buttonContent: 'Select an image',
      uploader: null,
      actions: []
    };

    this.config = Object.assign(this.defaultConfig, this.config);
    
    // Plugin state
    this.isResizing = false;
    this.resizeHandle = null;
    this.startX = 0;
    this.startY = 0;
    this.startWidth = 0;
    this.startHeight = 0;
    this.aspectRatio = 1;
    this.currentImage = null;
    this.currentContainer = null;
    
    // Bind methods
    this.onUpload = this.onUpload.bind(this);
    this.onPaste = this.onPaste.bind(this);
    this.startResize = this.startResize.bind(this);
    this.onResize = this.onResize.bind(this);
    this.stopResize = this.stopResize.bind(this);
  }

  render() {
    const wrapper = document.createElement('div');
    wrapper.classList.add('resizable-image');
    
    // Store wrapper reference for later use
    this.wrapper = wrapper;

    // Check if we have valid image data
    if (this.data && this.data.file && this.data.file.url && this.data.file.url.trim() !== '') {
      this.createImageElement(wrapper);
    } else {
      // Always show upload interface for new blocks or invalid data
      this.createUploadInterface(wrapper);
    }

    return wrapper;
  }

  createUploadInterface(wrapper) {
    const uploadContainer = document.createElement('div');
    uploadContainer.classList.add('resizable-image__upload-container');

    // Create unique ID for the file input
    const inputId = `resizable-image-input-${Math.random().toString(36).substr(2, 9)}`;

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = this.config.types;
    fileInput.classList.add('resizable-image__file-input');
    fileInput.id = inputId;
    
    // Create label that triggers the file input
    const uploadLabel = document.createElement('label');
    uploadLabel.setAttribute('for', inputId);
    uploadLabel.classList.add('resizable-image__upload-button');
    uploadLabel.innerHTML = `
      <div class="resizable-image__upload-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
        </svg>
      </div>
      <div class="resizable-image__upload-text">${this.config.buttonContent}</div>
    `;
    
    // Store references for cleanup
    this.fileInput = fileInput;
    this.uploadContainer = uploadContainer;
    this.uploadLabel = uploadLabel;

    const handleFileChange = (e) => {
      e.preventDefault();
      const file = e.target.files[0];
      if (file && !this.uploading) {
        this.uploadFile(file);
      }
      // Clear input to allow same file selection
      e.target.value = '';
    };

    const handleLabelClick = (e) => {
      e.preventDefault();
      if (!this.readOnly && !this.uploading) {
        fileInput.click();
      }
    };

    // Attach event listeners
    fileInput.addEventListener('change', handleFileChange);
    uploadLabel.addEventListener('click', handleLabelClick);

    // Drag and drop support with improved handling
    const handleDragOver = (e) => {
      e.preventDefault();
      e.stopPropagation();
      uploadContainer.classList.add('resizable-image__upload-container--dragover');
    };

    const handleDragLeave = (e) => {
      e.preventDefault();
      e.stopPropagation();
      // Only remove class if actually leaving the container
      if (!uploadContainer.contains(e.relatedTarget)) {
        uploadContainer.classList.remove('resizable-image__upload-container--dragover');
      }
    };

    const handleDrop = (e) => {
      e.preventDefault();
      e.stopPropagation();
      uploadContainer.classList.remove('resizable-image__upload-container--dragover');
      
      if (!this.readOnly && !this.uploading && e.dataTransfer.files.length) {
        this.uploadFile(e.dataTransfer.files[0]);
      }
    };

    uploadContainer.addEventListener('dragover', handleDragOver);
    uploadContainer.addEventListener('dragleave', handleDragLeave);
    uploadContainer.addEventListener('drop', handleDrop);

    // Store event handlers for cleanup
    this.eventHandlers = {
      handleFileChange,
      handleLabelClick,
      handleDragOver,
      handleDragLeave,
      handleDrop
    };

    uploadContainer.appendChild(uploadLabel);
    uploadContainer.appendChild(fileInput);
    wrapper.appendChild(uploadContainer);
  }

  createImageElement(wrapper) {
    const imageContainer = document.createElement('div');
    imageContainer.classList.add('resizable-image__container');

    const image = document.createElement('img');
    image.classList.add('resizable-image__img');
    image.src = this.data.file.url;
    image.alt = this.data.caption || '';

    // Set initial dimensions if available
    if (this.data.width) {
      image.style.width = this.data.width + 'px';
    }
    if (this.data.height) {
      image.style.height = this.data.height + 'px';
    }

    // Wait for image to load to get natural dimensions
    image.addEventListener('load', () => {
      this.aspectRatio = image.naturalWidth / image.naturalHeight;
      
      if (!this.data.width && !this.data.height) {
        // Set default width to container width, maintain aspect ratio
        const containerWidth = Math.min(image.naturalWidth, wrapper.parentElement?.offsetWidth || 600);
        image.style.width = containerWidth + 'px';
        image.style.height = (containerWidth / this.aspectRatio) + 'px';
      }
    });

    imageContainer.appendChild(image);

    // Store references to this specific block's elements
    this.currentImage = image;
    this.currentContainer = imageContainer;

    // Apply dark mode if enabled
    if (this.data.darkMode) {
      imageContainer.classList.add('resizable-image__container--dark');
    }

    // Add resize handles if not read-only
    if (!this.readOnly) {
      this.addResizeHandles(imageContainer, image);
    }

    // Caption input
    const captionInput = document.createElement('input');
    captionInput.classList.add('resizable-image__caption');
    captionInput.placeholder = this.config.captionPlaceholder;
    captionInput.value = this.data.caption || '';
    captionInput.readOnly = this.readOnly;

    captionInput.addEventListener('input', () => {
      this.data.caption = captionInput.value;
    });

    wrapper.appendChild(imageContainer);
    wrapper.appendChild(captionInput);
  }

  addResizeHandles(container, image) {
    const handles = ['nw', 'ne', 'sw', 'se', 'n', 's', 'e', 'w'];
    
    handles.forEach(position => {
      const handle = document.createElement('div');
      handle.classList.add('resizable-image__handle', `resizable-image__handle--${position}`);
      handle.addEventListener('mousedown', (e) => this.startResize(e, position, image));
      container.appendChild(handle);
    });

    container.classList.add('resizable-image__container--resizable');
  }

  startResize(e, position, image) {
    e.preventDefault();
    this.isResizing = true;
    this.resizeHandle = position;
    this.startX = e.clientX;
    this.startY = e.clientY;
    this.startWidth = parseInt(getComputedStyle(image).width);
    this.startHeight = parseInt(getComputedStyle(image).height);

    document.addEventListener('mousemove', this.onResize);
    document.addEventListener('mouseup', this.stopResize);
    document.body.style.userSelect = 'none';
    document.body.style.cursor = getComputedStyle(e.target).cursor;

    // Prevent text selection and other default behaviors
    e.target.style.pointerEvents = 'none';
  }

  onResize(e) {
    if (!this.isResizing) return;

    const deltaX = e.clientX - this.startX;
    const deltaY = e.clientY - this.startY;
    const handle = this.resizeHandle;
    
    let newWidth = this.startWidth;
    let newHeight = this.startHeight;

    // Calculate new dimensions based on handle position
    switch (handle) {
      case 'se': // Southeast
        newWidth = this.startWidth + deltaX;
        newHeight = newWidth / this.aspectRatio;
        break;
      case 'sw': // Southwest
        newWidth = this.startWidth - deltaX;
        newHeight = newWidth / this.aspectRatio;
        break;
      case 'ne': // Northeast
        newWidth = this.startWidth + deltaX;
        newHeight = newWidth / this.aspectRatio;
        break;
      case 'nw': // Northwest
        newWidth = this.startWidth - deltaX;
        newHeight = newWidth / this.aspectRatio;
        break;
      case 'e': // East
        newWidth = this.startWidth + deltaX;
        newHeight = newWidth / this.aspectRatio;
        break;
      case 'w': // West
        newWidth = this.startWidth - deltaX;
        newHeight = newWidth / this.aspectRatio;
        break;
      case 'n': // North
        newHeight = this.startHeight - deltaY;
        newWidth = newHeight * this.aspectRatio;
        break;
      case 's': // South
        newHeight = this.startHeight + deltaY;
        newWidth = newHeight * this.aspectRatio;
        break;
    }

    // Apply minimum constraints
    const minWidth = 50;
    const minHeight = 50;
    
    if (newWidth < minWidth) {
      newWidth = minWidth;
      newHeight = newWidth / this.aspectRatio;
    }
    
    if (newHeight < minHeight) {
      newHeight = minHeight;
      newWidth = newHeight * this.aspectRatio;
    }

    // Apply the new dimensions to this specific image
    if (this.currentImage) {
      this.currentImage.style.width = newWidth + 'px';
      this.currentImage.style.height = newHeight + 'px';
    }
  }

  stopResize() {
    if (!this.isResizing) return;

    this.isResizing = false;
    this.resizeHandle = null;

    document.removeEventListener('mousemove', this.onResize);
    document.removeEventListener('mouseup', this.stopResize);
    document.body.style.userSelect = '';
    document.body.style.cursor = '';

    // Re-enable pointer events for handles in this container
    if (this.currentContainer) {
      this.currentContainer.querySelectorAll('.resizable-image__handle').forEach(handle => {
        handle.style.pointerEvents = '';
      });
    }

    // Save the new dimensions from this specific image
    if (this.currentImage) {
      this.data.width = parseInt(this.currentImage.style.width);
      this.data.height = parseInt(this.currentImage.style.height);
    }
  }

  onUpload(e) {
    const file = e.target.files[0];
    if (file) {
      this.uploadFile(file);
    }
  }

  onPaste(e) {
    // Handle paste events for images
    const items = e.clipboardData?.items;
    if (items) {
      for (let item of items) {
        if (item.type.indexOf('image') !== -1) {
          const file = item.getAsFile();
          if (file) {
            this.uploadFile(file);
            e.preventDefault();
          }
        }
      }
    }
  }

  openFileSelection() {
    if (!this.readOnly && !this.uploading && this.fileInput) {
      this.fileInput.click();
    }
  }

  uploadFile(file) {
    // Prevent multiple uploads
    if (this.uploading) {
      return;
    }
    
    this.uploading = true;
    this.showProgress();

    if (this.config.uploader && typeof this.config.uploader.uploadByFile === 'function') {
      // Use custom uploader
      this.config.uploader.uploadByFile(file)
        .then(response => this.onUploadSuccess(response))
        .catch(error => this.onUploadError(error))
        .finally(() => {
          this.uploading = false;
        });
    } else {
      // Use default upload method
      this.defaultUpload(file)
        .then(response => this.onUploadSuccess(response))
        .catch(error => this.onUploadError(error))
        .finally(() => {
          this.uploading = false;
        });
    }
  }

  defaultUpload(file) {
    const formData = new FormData();
    formData.append(this.config.field, file);

    return fetch(this.config.endpoints.byFile, {
      method: 'POST',
      body: formData,
      headers: this.config.additionalRequestHeaders
    }).then(response => response.json());
  }

  onUploadSuccess(response) {
    this.data = {
      file: {
        url: response.url || response.file?.url
      },
      caption: response.caption || this.data.caption || '',
      width: response.width || null,
      height: response.height || null,
      darkMode: this.data.darkMode || false
    };

    // Find the wrapper element more reliably
    const wrapper = this.wrapper || document.querySelector('.resizable-image');
    if (wrapper) {
      // Clean up existing event listeners
      this.cleanupEventListeners();
      
      // Clear content and re-render
      wrapper.innerHTML = '';
      this.createImageElement(wrapper);
      
      // Store wrapper reference
      this.wrapper = wrapper;
    }
  }

  onUploadError(error) {
    console.error('Upload failed:', error);
    this.api.notifier.show({
      message: 'Image upload failed',
      style: 'error'
    });
  }

  showProgress() {
    if (this.wrapper) {
      this.wrapper.innerHTML = `
        <div class="resizable-image__progress">
          <div class="resizable-image__progress-bar">
            <div class="resizable-image__progress-fill"></div>
          </div>
          <div class="resizable-image__progress-text">Uploading...</div>
        </div>
      `;
    }
  }

  save() {
    return this.data;
  }

  validate(savedData) {
    // Simple validation - check if we have valid data structure
    if (!savedData) return false;
    
    // Allow empty data for new blocks
    if (Object.keys(savedData).length === 0) return true;
    
    // For existing blocks, check if we have file data
    if (savedData.file && savedData.file.url) return true;
    
    return false;
  }

  static get sanitize() {
    return {
      url: {},
      caption: {
        br: true,
      },
      width: {},
      height: {}
    };
  }

  // Block tunes settings
  renderSettings() {
    const wrapper = document.createElement('div');
    wrapper.classList.add('resizable-image__settings');
    
    const settings = [
      {
        name: 'delete',
        icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
        </svg>`,
        title: 'Delete Image',
        onClick: () => this.deleteBlock()
      },
      {
        name: 'darkMode',
        icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
        </svg>`,
        title: 'Toggle Dark Mode',
        onClick: () => this.toggleDarkMode()
      }
    ];

    settings.forEach(setting => {
      const button = document.createElement('div');
      button.classList.add('resizable-image__setting-button');
      button.innerHTML = `
        <div class="resizable-image__setting-icon">${setting.icon}</div>
        <div class="resizable-image__setting-label">${setting.title}</div>
      `;
      button.addEventListener('click', setting.onClick);
      wrapper.appendChild(button);
    });

    return wrapper;
  }

  deleteBlock() {
    // Show confirmation dialog
    if (confirm('Are you sure you want to delete this image?')) {
      // Use EditorJS API to delete the block
      const currentBlockIndex = this.api.blocks.getCurrentBlockIndex();
      this.api.blocks.delete(currentBlockIndex);
      
      // Optionally delete the file from server
      if (this.data.file && this.data.file.url) {
        this.deleteFromServer(this.data.file.url);
      }
    }
  }

  toggleDarkMode() {
    // Toggle dark mode class on this specific image container
    if (this.currentContainer) {
      this.currentContainer.classList.toggle('resizable-image__container--dark');
      
      // Update data to persist the setting
      this.data.darkMode = !this.data.darkMode;
      
      // Show notification
      this.api.notifier.show({
        message: this.data.darkMode ? 'Dark mode enabled' : 'Dark mode disabled',
        style: 'success'
      });
    }
  }

  deleteFromServer(url) {
    // Extract file path from URL for deletion
    let filePath = url;
    if (filePath.includes('/storage/')) {
      const storageIndex = filePath.indexOf('/storage/');
      filePath = filePath.substring(storageIndex + '/storage/'.length);
    }

    // Call delete endpoint
    fetch(this.config.endpoints.delete || '/delete-image', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        ...this.config.additionalRequestHeaders
      },
      body: JSON.stringify({ path: filePath })
    }).catch(error => {
      console.warn('Failed to delete file from server:', error);
    });
  }

  cleanupEventListeners() {
    // Clean up upload interface event listeners
    if (this.fileInput && this.eventHandlers) {
      this.fileInput.removeEventListener('change', this.eventHandlers.handleFileChange);
    }
    
    if (this.uploadLabel && this.eventHandlers) {
      this.uploadLabel.removeEventListener('click', this.eventHandlers.handleLabelClick);
    }
    
    if (this.uploadContainer && this.eventHandlers) {
      this.uploadContainer.removeEventListener('dragover', this.eventHandlers.handleDragOver);
      this.uploadContainer.removeEventListener('dragleave', this.eventHandlers.handleDragLeave);
      this.uploadContainer.removeEventListener('drop', this.eventHandlers.handleDrop);
    }
    
    // Clean up resize event listeners
    if (this.isResizing) {
      this.stopResize();
    }
  }

  destroy() {
    // Cleanup all event listeners
    this.cleanupEventListeners();
    
    // Clear references
    this.fileInput = null;
    this.uploadLabel = null;
    this.uploadContainer = null;
    this.wrapper = null;
    this.eventHandlers = null;
  }
}

export default ResizableImage;