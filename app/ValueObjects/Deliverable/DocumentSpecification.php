<?php

declare(strict_types=1);

namespace App\ValueObjects\Deliverable;

use InvalidArgumentException;

/**
 * ValueObject for document deliverable specifications.
 */
final class DocumentSpecification
{
    private const string TPYE = 'document';

    public function __construct(
        private readonly string $name,
        private readonly string $format,
        private readonly int $maxPages,
        private readonly string $template,
        private readonly string $description,
        private readonly array $tags,
        private readonly array $sections,
        private readonly array $requirements,
        private readonly array $constraints = [],
        private readonly ?string $language = null,
        private readonly ?int $minWords = null,
        private readonly ?int $maxWords = null,
        private readonly ?array $styles = null,
    ) {
        throw_if(
            $this->maxPages < 0,
            new InvalidArgumentException('Max pages must be non-negative.'),
        );

        throw_if(
            $this->minWords !== null
            && $this->maxWords !== null
            && $this->minWords > $this->maxWords,
            new InvalidArgumentException(
                'Minimum words cannot exceed maximum words.',
            ),
        );
    }

    public static function fromConfig(array $config): self
    {
        return new self(
            name: $config['name'] ?? 'Unknown Document',
            format: $config['format'] ?? 'pdf',
            maxPages: $config['max_pages'] ?? 0,
            template: $config['template'] ?? 'standard',
            description: $config['description'] ?? '',
            tags: $config['tags'] ?? [],
            sections: $config['sections'] ?? [],
            requirements: $config['requirements'] ?? [],
            constraints: $config['constraints'] ?? [],
            language: $config['language'] ?? null,
            minWords: $config['min_words'] ?? null,
            maxWords: $config['max_words'] ?? null,
            styles: $config['styles'] ?? null,
        );
    }

    public function getType(): string
    {
        return self::TPYE;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getMaxPages(): int
    {
        return $this->maxPages;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getMinWords(): ?int
    {
        return $this->minWords;
    }

    public function getMaxWords(): ?int
    {
        return $this->maxWords;
    }

    public function getStyles(): ?array
    {
        return $this->styles;
    }

    public function getWordRange(): array
    {
        return [
            'min' => $this->minWords,
            'max' => $this->maxWords,
            'has_limit' => $this->minWords !== null || $this->maxWords !== null,
        ];
    }

    public function hasPageLimit(): bool
    {
        return $this->maxPages > 0;
    }

    public function hasWordLimit(): bool
    {
        return $this->minWords !== null || $this->maxWords !== null;
    }

    public function hasSections(): bool
    {
        return ! empty($this->sections);
    }

    public function getRequiredSections(): array
    {
        if (! $this->hasSections()) {
            return [];
        }

        return array_filter($this->sections, function ($section) {
            return is_array($section) ? $section['required'] ?? true : true;
        });
    }

    public function getOptionalSections(): array
    {
        if (! $this->hasSections()) {
            return [];
        }

        return array_filter($this->sections, function ($section) {
            return is_array($section)
                ? ($section['required'] ?? true) === false
                : false;
        });
    }

    public function getSectionCount(): int
    {
        return count($this->sections);
    }

    public function getRequiredSectionCount(): int
    {
        return count($this->getRequiredSections());
    }

    public function validate(array $fileData): bool
    {
        // Validate format if provided
        if (isset($fileData['format'])) {
            $fileFormat = strtolower($fileData['format']);
            if ($fileFormat !== strtolower($this->format)) {
                return false;
            }
        }

        // Validate page count if provided
        if (isset($fileData['pages']) && $this->hasPageLimit()) {
            $pages = (int) $fileData['pages'];
            if ($pages > $this->maxPages) {
                return false;
            }
        }

        // Validate word count if provided
        if (isset($fileData['word_count'])) {
            $wordCount = (int) $fileData['word_count'];

            if ($this->minWords !== null && $wordCount < $this->minWords) {
                return false;
            }

            if ($this->maxWords !== null && $wordCount > $this->maxWords) {
                return false;
            }
        }

        // Validate file size (basic check for reasonable document size)
        if (isset($fileData['size'])) {
            $sizeBytes = (int) $fileData['size'];
            $maxSizeBytes = $this->getMaxExpectedSize();

            if ($sizeBytes > $maxSizeBytes) {
                return false;
            }
        }

        return true;
    }

    public function getValidationRules(): array
    {
        $rules = [
            'format' => ['required', 'string', 'in:pdf,doc,docx,txt,md,rtf'],
        ];

        if ($this->hasPageLimit()) {
            $rules['pages'] = [
                'sometimes',
                'integer',
                'max:' . $this->maxPages,
            ];
        }

        if ($this->hasWordLimit()) {
            if ($this->minWords !== null) {
                $rules['word_count'][] = 'min:' . $this->minWords;
            }
            if ($this->maxWords !== null) {
                $rules['word_count'][] = 'max:' . $this->maxWords;
            }
        }

        return $rules;
    }

    public function matchesFormat(string $format): bool
    {
        return strtolower($this->format) === strtolower($format);
    }

    public function matchesPageCount(int $pages): bool
    {
        return ! $this->hasPageLimit() || $pages <= $this->maxPages;
    }

    public function matchesWordCount(int $words): bool
    {
        if ($this->minWords !== null && $words < $this->minWords) {
            return false;
        }

        if ($this->maxWords !== null && $words > $this->maxWords) {
            return false;
        }

        return true;
    }

    public function getMaxExpectedSize(): int
    {
        // Estimate maximum file size based on format and pages
        $baseSize = match (strtolower($this->format)) {
            'pdf' => 500000, // 500KB base for PDF
            'docx' => 100000, // 100KB base for DOCX
            'doc' => 150000, // 150KB base for DOC
            'txt' => 10000, // 10KB base for TXT
            'md' => 50000, // 50KB base for MD
            'rtf' => 200000, // 200KB base for RTF
            default => 250000, // 250KB default
        };

        // Multiply by page count if specified
        if ($this->hasPageLimit() && $this->maxPages > 0) {
            return $baseSize * $this->maxPages;
        }

        // Multiply by estimated pages based on word count
        if ($this->maxWords !== null) {
            $estimatedPages = ceil($this->maxWords / 250); // ~250 words per page

            return $baseSize * max(1, $estimatedPages);
        }

        return $baseSize * 10; // Default to 10 pages worth
    }

    public function getFormatDisplayName(): string
    {
        return match (strtolower($this->format)) {
            'pdf' => 'PDF Document',
            'docx' => 'Microsoft Word (DOCX)',
            'doc' => 'Microsoft Word (DOC)',
            'txt' => 'Plain Text',
            'md' => 'Markdown',
            'rtf' => 'Rich Text Format',
            default => strtoupper($this->format) . ' Document',
        };
    }

    public function hasRequirement(string $requirement): bool
    {
        return
            isset($this->requirements[$requirement])
            || in_array($requirement, $this->requirements);
    }

    public function getTemplateDetails(): array
    {
        return [
            'template' => $this->template,
            'has_template' => ! empty($this->template) && $this->template !== 'none',
            'template_display' => ucfirst(str_replace(
                '_',
                ' ',
                $this->template,
            )),
        ];
    }

    public function getEstimatedReadingTime(): ?array
    {
        if ($this->maxWords === null && $this->minWords === null) {
            return null;
        }

        $wordsPerMinute = 200; // Average reading speed

        $minTime = $this->minWords
            ? ceil($this->minWords / $wordsPerMinute)
            : null;
        $maxTime = $this->maxWords
            ? ceil($this->maxWords / $wordsPerMinute)
            : null;

        return [
            'min_minutes' => $minTime,
            'max_minutes' => $maxTime,
            'min_formatted' => $minTime
                ? $this->formatReadingTime((int) $minTime)
                : null,
            'max_formatted' => $maxTime
                ? $this->formatReadingTime((int) $maxTime)
                : null,
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'format' => $this->format,
            'format_display' => $this->getFormatDisplayName(),
            'max_pages' => $this->maxPages,
            'template' => $this->template,
            'template_details' => $this->getTemplateDetails(),
            'description' => $this->description,
            'tags' => $this->tags,
            'sections' => $this->sections,
            'required_sections' => $this->getRequiredSections(),
            'optional_sections' => $this->getOptionalSections(),
            'section_count' => $this->getSectionCount(),
            'requirements' => $this->requirements,
            'constraints' => $this->constraints,
            'language' => $this->language,
            'min_words' => $this->minWords,
            'max_words' => $this->maxWords,
            'word_range' => $this->getWordRange(),
            'styles' => $this->styles,
            'has_page_limit' => $this->hasPageLimit(),
            'has_word_limit' => $this->hasWordLimit(),
            'has_sections' => $this->hasSections(),
            'max_expected_size' => $this->getMaxExpectedSize(),
            'estimated_reading_time' => $this->getEstimatedReadingTime(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function equals(self $other): bool
    {
        return
            $this->name === $other->name
            && $this->format === $other->format
            && $this->maxPages === $other->maxPages
            && $this->template === $other->template
            && $this->minWords === $other->minWords
            && $this->maxWords === $other->maxWords;
    }

    private function formatReadingTime(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0
            ? "{$hours}h {$remainingMinutes}m"
            : "{$hours}h";
    }
}
