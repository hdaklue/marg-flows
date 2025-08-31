<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DTOs\LocationDto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use Lorisleiva\Actions\Concerns\AsAction;

final class GetUserLocation
{
    use AsAction;

    public function handle(string $ip): LocationDto
    {
        $uri = Uri::of('https://api.ipquery.io')
            ->withScheme('https')
            ->withPath("/{$ip}")
            ->withQuery(['format' => 'json'])
            ->getUri()
            ->toString();

        $response = Http::get($uri);

        return LocationDto::fromArray([
            'country' => $response['location']['country'],
            'country_code' => $response['location']['country_code'],
            'timezone' => $response['location']['timezone'],
        ]);
    }
}
