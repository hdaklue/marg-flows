import Autosize from '@marcreichel/alpine-autosize';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import designReviewApp from "./components/image-review";


Alpine.data('designReviewApp', designReviewApp)
Alpine.plugin(Autosize);
Livewire.start()
