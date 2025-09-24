<?php

declare(strict_types=1);

namespace App\Services\Document\Templates\Translation;

use App\Contracts\Document\DocumentTemplateTranslatorInterface;
use App\Contracts\Document\TranslationProviderInterface;
use App\Exceptions\Document\TranslationKeyNotResolvedException;
use Hdaklue\PathBuilder\Facades\LaraPath;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Document template translator service.
 *
 * Provides translation functionality for document templates with caching,
 * secure class resolution, and proper error handling.
 *
 * @phpstan-type TranslationCache array<string, string>
 * @phpstan-type TranslatorCache array<string, object>
 */
final class DocumentTemplateTranslator implements DocumentTemplateTranslatorInterface
{
    protected string $locale;

    /**
     * @var TranslationCache
     */
    protected array $translationCache = [];

    /**
     * @var TranslatorCache
     */
    protected array $translatorCache = [];

    /**
     * @var array<string> List of allowed template classes for security
     */
    protected array $allowedTemplateClasses = [];

    protected string $fallbackLocale;

    /**
     * @param  array<string>  $allowedTemplateClasses
     */
    public function __construct(
        null|string $locale = null,
        string $fallbackLocale = 'en',
        array $allowedTemplateClasses = [],
    ) {
        $this->fallbackLocale = $fallbackLocale;
        $this->allowedTemplateClasses = $allowedTemplateClasses;
        $this->setLocale($locale ?? app()->getLocale());
    }

    public function setLocale(string $locale): static
    {
        $this->validateLocale($locale);

        // Only clear translation cache if locale actually changed
        if (isset($this->locale) && $this->locale !== $locale) {
            $this->clearTranslationCacheForLocale($this->locale);
        }

        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function translateBlock(
        string $templateKey,
        string $blockKey,
        array $params = [],
    ): string {
        // Handle dot notation keys
        if (str_contains($blockKey, '.')) {
            return $this->translateWithDotNotation($templateKey, $blockKey, $params);
        }

        $cacheKey = $this->buildCacheKey('block', $templateKey, $blockKey);

        if (isset($this->translationCache[$cacheKey])) {
            return $this->formatTranslation($this->translationCache[$cacheKey], $params);
        }

        try {
            $translator = $this->resolveTemplateTranslator($templateKey);
            $translations = $translator->getBlockTranslations();
            $translation = $translations[$blockKey] ?? null;

            if ($translation === null) {
                throw new TranslationKeyNotResolvedException(
                    $blockKey,
                    $templateKey,
                    $this->locale,
                );
            }

            $this->translationCache[$cacheKey] = $translation;

            return $this->formatTranslation($translation, $params);
        } catch (TranslationKeyNotResolvedException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Translation error in translateBlock', [
                'template_key' => $templateKey,
                'block_key' => $blockKey,
                'locale' => $this->locale,
                'error' => $e->getMessage(),
            ]);

            throw new TranslationKeyNotResolvedException($blockKey, $templateKey, $this->locale);
        }
    }

    public function translateMeta(string $templateKey, string $metaKey): string
    {
        $cacheKey = $this->buildCacheKey('meta', $templateKey, $metaKey);

        if (isset($this->translationCache[$cacheKey])) {
            return $this->translationCache[$cacheKey];
        }

        try {
            $translator = $this->resolveTemplateTranslator($templateKey);
            $translations = $translator->getMetaTranslations();
            $translation = $translations[$metaKey] ?? null;

            if ($translation === null) {
                throw new TranslationKeyNotResolvedException($metaKey, $templateKey, $this->locale);
            }

            $this->translationCache[$cacheKey] = $translation;

            return $translation;
        } catch (TranslationKeyNotResolvedException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Translation error in translateMeta', [
                'template_key' => $templateKey,
                'meta_key' => $metaKey,
                'locale' => $this->locale,
                'error' => $e->getMessage(),
            ]);

            throw new TranslationKeyNotResolvedException($metaKey, $templateKey, $this->locale);
        }
    }

    /**
     * Handle translation with dot notation support.
     */
    protected function translateWithDotNotation(
        string $templateKey,
        string $key,
        array $params = [],
    ): string {
        $cacheKey = $this->buildCacheKey('dot', $templateKey, $key);

        if (isset($this->translationCache[$cacheKey])) {
            return $this->formatTranslation($this->translationCache[$cacheKey], $params);
        }

        try {
            $translator = $this->resolveTemplateTranslator($templateKey);

            if ($translator instanceof TranslationProviderInterface) {
                $translation = $translator->getTranslationByKey($key);

                if ($translation !== null) {
                    $this->translationCache[$cacheKey] = $translation;

                    return $this->formatTranslation($translation, $params);
                }
            }

            throw new TranslationKeyNotResolvedException($key, $templateKey, $this->locale);
        } catch (TranslationKeyNotResolvedException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Translation error in translateWithDotNotation', [
                'template_key' => $templateKey,
                'key' => $key,
                'locale' => $this->locale,
                'error' => $e->getMessage(),
            ]);

            throw new TranslationKeyNotResolvedException($key, $templateKey, $this->locale);
        }
    }

    /**
     * Resolve template translator with caching and security validation.
     */
    protected function resolveTemplateTranslator(string $templateKey): object
    {
        $translatorCacheKey = "{$this->locale}_{$templateKey}";

        if (isset($this->translatorCache[$translatorCacheKey])) {
            return $this->translatorCache[$translatorCacheKey];
        }

        $templateClass = $this->getTemplateClass($templateKey);
        $this->validateTemplateClass($templateClass);

        $availableTranslations = $templateClass::getAvailableTranslations();

        // Try current locale first
        $translator = $this->findTranslatorForLocale($availableTranslations, $this->locale);

        // Fallback to configured fallback locale if current locale not found
        if ($translator === null && $this->locale !== $this->fallbackLocale) {
            $translator = $this->findTranslatorForLocale(
                $availableTranslations,
                $this->fallbackLocale,
            );
        }

        if ($translator === null) {
            throw new InvalidArgumentException(
                "No translation found for template: {$templateKey}, locale: {$this->locale}, fallback: {$this->fallbackLocale}",
            );
        }

        $this->translatorCache[$translatorCacheKey] = $translator;

        return $translator;
    }

    /**
     * Find translator instance for specific locale.
     *
     * @param  array<class-string>  $availableTranslations
     */
    protected function findTranslatorForLocale(
        array $availableTranslations,
        string $locale,
    ): null|object {
        foreach ($availableTranslations as $translationClass) {
            $this->validateTranslationClass($translationClass);

            if ($translationClass::getLocaleCode() === $locale) {
                return new $translationClass();
            }
        }

        return null;
    }

    /**
     * Get template class with improved path building.
     */
    protected function getTemplateClass(string $templateKey): string
    {
        // Sanitize template key to prevent path traversal
        $sanitizedKey = preg_replace('/[^a-zA-Z0-9_]/', '', $templateKey);

        if ($sanitizedKey !== $templateKey) {
            throw new InvalidArgumentException("Invalid template key format: {$templateKey}");
        }

        $studlyKey = Str::studly($sanitizedKey);

        $path = LaraPath::base('App/Services/Document/Templates')
            ->add($studlyKey)
            ->add($studlyKey)
            ->toString();

        $className = str_replace('/', '\\', $path);

        if (!class_exists($className)) {
            throw new InvalidArgumentException("Template class not found: {$className}");
        }

        return $className;
    }

    /**
     * Validate template class for security.
     */
    protected function validateTemplateClass(string $className): void
    {
        if (
            !empty($this->allowedTemplateClasses)
            && !in_array($className, $this->allowedTemplateClasses, true)
        ) {
            throw new InvalidArgumentException("Template class not allowed: {$className}");
        }

        // Ensure class extends expected base class or implements expected interface
        if (!method_exists($className, 'getAvailableTranslations')) {
            throw new InvalidArgumentException("Invalid template class: {$className}");
        }
    }

    /**
     * Validate translation class for security.
     */
    protected function validateTranslationClass(string $className): void
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException("Translation class not found: {$className}");
        }

        // Check if it implements the new interface
        if (is_subclass_of($className, TranslationProviderInterface::class)) {
            return;
        }

        // Legacy validation for backward compatibility
        if (
            !method_exists($className, 'getLocaleCode')
            || !method_exists($className, 'getBlockTranslations')
            || !method_exists($className, 'getMetaTranslations')
        ) {
            throw new InvalidArgumentException("Invalid translation class: {$className}");
        }
    }

    /**
     * Validate locale format.
     */
    protected function validateLocale(string $locale): void
    {
        if (!preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $locale)) {
            throw new InvalidArgumentException("Invalid locale format: {$locale}");
        }
    }

    /**
     * Enhanced parameter replacement with better type handling.
     */
    protected function formatTranslation(string $translation, array $params): string
    {
        if (empty($params)) {
            return $translation;
        }

        // Sort by key length descending to prevent partial replacements
        uksort($params, function ($a, $b) {
            return strlen((string) $b) - strlen((string) $a);
        });

        foreach ($params as $key => $value) {
            $placeholder = ":{$key}";
            $replacement = match (true) {
                is_string($value) => $value,
                is_numeric($value) => (string) $value,
                is_bool($value) => $value ? '1' : '0',
                is_null($value) => '',
                default => json_encode($value) ?: '',
            };

            $translation = str_replace($placeholder, $replacement, $translation);
        }

        return $translation;
    }

    /**
     * Build consistent cache key.
     */
    protected function buildCacheKey(string $type, string $templateKey, string $key): string
    {
        return "{$type}_{$this->locale}_{$templateKey}_{$key}";
    }

    /**
     * Clear translation cache for specific locale.
     */
    protected function clearTranslationCacheForLocale(string $locale): void
    {
        $keysToRemove = array_filter(
            array_keys($this->translationCache),
            fn($key) => (
                str_starts_with($key, "block_{$locale}_")
                || str_starts_with($key, "meta_{$locale}_")
            ),
        );

        foreach ($keysToRemove as $key) {
            unset($this->translationCache[$key]);
        }

        // Also clear translator cache for this locale
        $translatorKeysToRemove = array_filter(
            array_keys($this->translatorCache),
            fn($key) => str_starts_with($key, "{$locale}_"),
        );

        foreach ($translatorKeysToRemove as $key) {
            unset($this->translatorCache[$key]);
        }
    }
}
