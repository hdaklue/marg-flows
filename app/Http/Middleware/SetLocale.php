<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Jenssegers\Agent\Agent;

final class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Get locale from session, URL parameter, browser locale, or default
        $locale =
            $request->get('locale') ?? Session::get('locale') ?? $this->getBrowserLocale(
                $request,
            ) ?? config('app.locale');

        // Validate locale is supported
        if (array_key_exists($locale, config('app.available_locales'))) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        return $next($request);
    }

    private function getBrowserLocale(Request $request): null|string
    {
        $agent = new Agent();

        // $agent->setHttpHeaders($request->headers->all());
        // $agent->setUserAgent($request->header('User-Agent'));

        // Get browser languages in preference order
        $languages = $agent->languages();

        if (empty($languages)) {
            return null;
        }

        // Check each preferred language against supported locales
        foreach ($languages as $language) {
            // Extract just the language code (e.g., "en" from "en-US")
            $langCode = strtolower(explode('-', $language)[0]);

            if (array_key_exists($langCode, config('app.available_locales'))) {
                return $langCode;
            }
        }

        return null;
    }
}
