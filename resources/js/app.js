import { sort } from '@alpinejs/sort';
import ui from '@alpinejs/ui';
import Autosize from '@marcreichel/alpine-autosize';
import Tooltip from '@ryangjchandler/alpine-tooltip';
import AsyncAlpine from 'async-alpine';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Import TOAST UI Calendar CSS and make it globally available
// import Calendar from '@toast-ui/calendar';
// import '@toast-ui/calendar/dist/toastui-calendar.min.css';

// Make TOAST UI Calendar available globally
// window.ToastUICalendar = Calendar;

// Heavy components will be loaded asynchronously
// Only keep lightweight or critical components as direct imports here if needed

import anchor from "@alpinejs/anchor";
import Draggabilly from 'draggabilly';
import Hammer from 'hammerjs';
// import './dist/components/alpine-sortable';





// Make Hammer.js and Draggabilly available globally
window.Hammer = Hammer;
window.Draggabilly = Draggabilly;
window.ui = ui;


// Configure AsyncAlpine plugin
Alpine.plugin(AsyncAlpine);

// Register async components with Alpine.asyncData
Alpine.asyncData('audioAnnotation', () => import('./components/audio-annotation'));
Alpine.asyncData('designAnnotationApp', () => import('./components/design-annotation'));
Alpine.asyncData('designReviewApp', () => import('./components/image-review'));
Alpine.asyncData('videoAnnotation', () => import('./components/video-annotation'));
Alpine.asyncData('audioPlayer', () => import('./components/audio-player'));
Alpine.asyncData('document', () => import('./components/document'));
Alpine.asyncData('mentionableText', () => import('./components/mentionable'));
Alpine.asyncData('videoRecorder', () => import('./components/video-recorder'));
Alpine.asyncData('recorder', () => import('./components/voice-recorder'));
Alpine.asyncData('chunkedFileUpload', () => import('./components/ChunkedFileUpload/index.js'));

//Keep global assignments for components that need it
Alpine.asyncData('videoAnnotationComponent', () => import('./components/video-annotation').then(module => {
    window.videoAnnotationComponent = module.default;
    return module;
}));
Alpine.asyncData('chunkedFileUploadComponent', () => import('./components/ChunkedFileUpload/index.js').then(module => {
    window.chunkedFileUploadComponent = module.default;
    return module;
}));

Alpine.plugin(ui);
Alpine.plugin(Autosize);
Alpine.plugin(Tooltip);
Alpine.plugin(sort);
Alpine.plugin(anchor);

Livewire.start()


