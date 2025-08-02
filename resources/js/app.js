import { sort } from '@alpinejs/sort';
import ui from '@alpinejs/ui';
import Autosize from '@marcreichel/alpine-autosize';
import Tooltip from '@ryangjchandler/alpine-tooltip';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import audioAnnotation from "./components/audio-annotation";

import designAnnotationApp from "./components/design-annotation";
import designReviewApp from "./components/image-review";
import videoAnnotationComponent from "./components/video-annotation";

import audioPlayer from './components/audio-player';
import documentEditor from './components/document';
import mentionableText from './components/mentionable';

import videoRecorder from './components/video-recorder';
import recorder from './components/voice-recorder';

import anchor from "@alpinejs/anchor";
import Hammer from 'hammerjs';
import './dist/components/alpine-sortable';





// Make Hammer.js available globally
window.Hammer = Hammer;
window.ui = ui;


// Make video annotation available globally for Blade component
window.videoAnnotationComponent = videoAnnotationComponent;

Alpine.data('designReviewApp', designReviewApp)
Alpine.data('designAnnotationApp', designAnnotationApp)
Alpine.data('videoAnnotation', videoAnnotationComponent)
Alpine.data('audioAnnotation', audioAnnotation)
Alpine.data('document', documentEditor);
Alpine.data('mentionableText', mentionableText);
Alpine.data('recorder', recorder);
Alpine.data('audioPlayer', audioPlayer);
Alpine.data('videoRecorder', videoRecorder);
Alpine.plugin(ui);
Alpine.plugin(Autosize);
Alpine.plugin(Tooltip);
Alpine.plugin(sort);
Alpine.plugin(anchor);

Livewire.start()


