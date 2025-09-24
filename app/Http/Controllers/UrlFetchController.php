<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

final class UrlFetchController extends Controller
{
    public function fetchUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => 'Invalid URL provided',
            ], 400);
        }

        $url = $request->input('url');

        try {
            // Fetch the webpage content
            $response = Http::timeout(10)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; KluePortal/1.0; +https://klueportal.com)',
            ])->get($url);

            if (!$response->successful()) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Failed to fetch URL content',
                ], 400);
            }

            $html = $response->body();
            $meta = $this->extractMetadata($html, $url);

            return response()->json([
                'success' => 1,
                'link' => $url,
                'meta' => $meta,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'Error fetching URL: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function extractMetadata(string $html, string $url): array
    {
        $meta = [
            'title' => '',
            'description' => '',
            'image' => '',
        ];

        // Create a DOM document to parse HTML
        $dom = new DOMDocument();
        $previousSetting = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);

        $xpath = new DOMXPath($dom);

        // Extract title
        $titleNodes = $xpath->query('//title');
        if ($titleNodes->length > 0) {
            $meta['title'] = trim($titleNodes->item(0)->textContent);
        }

        // Try Open Graph title first
        $ogTitle = $xpath->query('//meta[@property="og:title"]/@content');
        if ($ogTitle->length > 0) {
            $meta['title'] = trim($ogTitle->item(0)->textContent);
        }

        // Extract description
        $descNodes = $xpath->query('//meta[@name="description"]/@content');
        if ($descNodes->length > 0) {
            $meta['description'] = trim($descNodes->item(0)->textContent);
        }

        // Try Open Graph description
        $ogDesc = $xpath->query('//meta[@property="og:description"]/@content');
        if ($ogDesc->length > 0) {
            $meta['description'] = trim($ogDesc->item(0)->textContent);
        }

        // Extract image
        $ogImage = $xpath->query('//meta[@property="og:image"]/@content');
        if ($ogImage->length > 0) {
            $imageUrl = trim($ogImage->item(0)->textContent);
            $meta['image'] = $this->resolveUrl($imageUrl, $url);
        }

        // Fallback to first image if no OG image
        if (empty($meta['image'])) {
            $images = $xpath->query('//img/@src');
            if ($images->length > 0) {
                $imageUrl = trim($images->item(0)->textContent);
                $meta['image'] = $this->resolveUrl($imageUrl, $url);
            }
        }

        return $meta;
    }

    private function resolveUrl(string $url, string $baseUrl): string
    {
        // If already absolute URL, return as is
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Parse base URL
        $parsedBase = parse_url($baseUrl);
        $scheme = $parsedBase['scheme'] ?? 'https';
        $host = $parsedBase['host'] ?? '';

        // Handle protocol-relative URLs
        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }

        // Handle absolute paths
        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $host . $url;
        }

        // Handle relative paths (basic implementation)
        $basePath = dirname($parsedBase['path'] ?? '/');
        if ($basePath === '.') {
            $basePath = '/';
        }

        return $scheme . '://' . $host . rtrim($basePath, '/') . '/' . ltrim($url, '/');
    }
}
