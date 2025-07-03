import { sort } from '@alpinejs/sort';
import Autosize from '@marcreichel/alpine-autosize';
import Tooltip from '@ryangjchandler/alpine-tooltip';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

import designReviewApp from "./components/image-review";



Alpine.data('designReviewApp', designReviewApp)
Alpine.plugin(Autosize);
Alpine.plugin(Tooltip);
Alpine.plugin(sort);

Livewire.start()
