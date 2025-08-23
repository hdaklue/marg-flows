<x-filament-panels::page.simple class="w-full">
    {{ $this->content }}
    
    <div class="mt-8 text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('auth.login.no_account') }}
            <a href="{{ filament()->getRegistrationUrl() }}" class="font-medium text-primary-600 hover:text-primary-500">
                {{ __('auth.login.sign_up') }}
            </a>
        </p>
    </div>
</x-filament-panels::page.simple>