import { sort } from '@alpinejs/sort';
import Autosize from '@marcreichel/alpine-autosize';
import Tooltip from '@ryangjchandler/alpine-tooltip';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import audioAnnotation from "./components/audio-annotation";

import designReviewApp from "./components/image-review";
import videoAnnotationComponent from "./components/video-annotation";

import audioPlayer from './components/audio-player';
import documentEditor from './components/document';
import mentionableText from './components/mentionable';

import recorder from './components/voice-recorder';

import anchor from "@alpinejs/anchor";


// Make video annotation available globally for Blade component
window.videoAnnotationComponent = videoAnnotationComponent;

Alpine.data('designReviewApp', designReviewApp)
Alpine.data('videoAnnotation', videoAnnotationComponent)
Alpine.data('audioAnnotation', audioAnnotation)
Alpine.data('document', documentEditor);
Alpine.data('mentionableText', mentionableText);
Alpine.data('recorder', recorder);
Alpine.data('audioPlayer', audioPlayer);
Alpine.plugin(Autosize);
Alpine.plugin(Tooltip);
Alpine.plugin(sort);
Alpine.plugin(anchor);

Livewire.start()
