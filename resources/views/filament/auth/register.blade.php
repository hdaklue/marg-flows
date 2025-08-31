<x-filament-panels::page.simple>
    {{ $this->content }}

    <div class="">
        <p class="mt-8 text-sm text-center text-gray-600 dark:text-gray-400">
            {{ __('auth.register.already_have_account') }}
            <a href="{{ filament()->getLoginUrl() }}" class="font-medium text-primary-600 hover:text-primary-500">
                {{ __('auth.register.sign_in') }}
            </a>
        </p>
    </div>

</x-filament-panels::page.simple>
