<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Document\DocumentTemplateTranslatorInterface;
use App\Services\Document\Contracts\DocumentTemplateContract;
use App\Services\Document\Templates\Translation\BaseTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Throwable;

final class DocumentDoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'document:doctor {--detailed : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose and validate document template system';

    private int $issuesFound = 0;

    private int $checksPerformed = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏥 Document Template System Doctor');
        $this->newLine();

        // Run all diagnostic checks
        $this->checkConfigurationFile();
        $this->checkTemplateDirectory();
        $this->checkConfiguredTemplates();
        $this->checkTranslatorService();
        $this->checkTranslationFiles();

        $this->newLine();
        $this->displaySummary();

        return $this->issuesFound > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function checkConfigurationFile(): void
    {
        $this->section('Configuration File');
        $this->checksPerformed++;

        $configPath = config_path('document.php');

        if (!File::exists($configPath)) {
            $this->error("❌ Configuration file missing: {$configPath}");
            $this->issuesFound++;

            return;
        }

        $this->success('✅ Configuration file exists');

        // Check if templates array is configured
        $templates = config('document.templates');
        if (empty($templates)) {
            $this->warning('⚠️  No templates configured in document.templates');
            $this->issuesFound++;
        } else {
            $this->success('✅ Templates configured: ' . count($templates));
            if ($this->option('detailed')) {
                foreach (array_keys($templates) as $key) {
                    $this->line("   - {$key}");
                }
            }
        }
    }

    private function checkTemplateDirectory(): void
    {
        $this->section('Template Directory');
        $this->checksPerformed++;

        $templatesPath = app_path('Services/Document/Templates');

        if (!File::exists($templatesPath)) {
            $this->error("❌ Templates directory missing: {$templatesPath}");
            $this->issuesFound++;

            return;
        }

        $this->success('✅ Templates directory exists');

        // Check for template subdirectories
        $directories = File::directories($templatesPath);
        $templateDirectories = array_filter(
            $directories,
            fn($dir) => basename($dir) !== 'Translation',
        );

        if (empty($templateDirectories)) {
            $this->warning('⚠️  No template directories found');
            $this->issuesFound++;
        } else {
            $this->success('✅ Template directories found: ' . count($templateDirectories));
            if ($this->option('detailed')) {
                foreach ($templateDirectories as $dir) {
                    $this->line('   - ' . basename($dir));
                }
            }
        }
    }

    private function checkConfiguredTemplates(): void
    {
        $this->section('Configured Templates');
        $this->checksPerformed++;

        $templates = config('document.templates', []);
        $templateIssues = 0;

        foreach ($templates as $key => $templateClass) {
            $this->checkTemplate($key, $templateClass, $templateIssues);
        }

        if ($templateIssues === 0) {
            $this->success('✅ All configured templates are valid');
        } else {
            $this->issuesFound += $templateIssues;
        }
    }

    private function checkTemplate(string $key, string $templateClass, int &$templateIssues): void
    {
        if ($this->option('verbose')) {
            $this->comment("Checking template: {$key} ({$templateClass})");
        }

        // Check if class exists
        if (!class_exists($templateClass)) {
            $this->error("❌ Template class not found: {$templateClass}");
            $templateIssues++;

            return;
        }

        // Check if implements DocumentTemplateContract
        try {
            $reflection = new ReflectionClass($templateClass);
            if (!$reflection->implementsInterface(DocumentTemplateContract::class)) {
                $this->error(
                    "❌ Template does not implement DocumentTemplateContract: {$templateClass}",
                );
                $templateIssues++;
            }

            // Check if template can be instantiated
            $instance = $templateClass::make();
            if (!$instance) {
                $this->error("❌ Cannot instantiate template: {$templateClass}");
                $templateIssues++;
            }

            // Check required methods
            $this->checkRequiredMethods($reflection, $templateClass, $templateIssues);
        } catch (Throwable $e) {
            $this->error("❌ Error checking template {$templateClass}: " . $e->getMessage());
            $templateIssues++;
        }
    }

    private function checkRequiredMethods(
        ReflectionClass $reflection,
        string $templateClass,
        int &$templateIssues,
    ): void {
        $requiredMethods = [
            'getName',
            'getDescription',
            'getBlocks',
            'getConfigArray',
            'getDataArray',
            'toJson',
            'toArray',
            'getAvailableTranslations',
        ];

        foreach ($requiredMethods as $method) {
            if (!$reflection->hasMethod($method)) {
                $this->error("❌ Missing method {$method} in {$templateClass}");
                $templateIssues++;
            }
        }
    }

    private function checkTranslatorService(): void
    {
        $this->section('Translator Service');
        $this->checksPerformed++;

        try {
            $translator = app(DocumentTemplateTranslatorInterface::class);
            $this->success('✅ Translator service is bound and resolvable');

            // Test basic functionality
            if (method_exists($translator, 'setLocale')) {
                $translator->setLocale('en');
                $this->success('✅ Translator setLocale method works');
            }
        } catch (Throwable $e) {
            $this->error('❌ Translator service issue: ' . $e->getMessage());
            $this->issuesFound++;
        }
    }

    private function checkTranslationFiles(): void
    {
        $this->section('Translation Files');
        $this->checksPerformed++;

        $templates = config('document.templates', []);
        $translationIssues = 0;

        foreach ($templates as $key => $templateClass) {
            $this->checkTemplateTranslations($key, $templateClass, $translationIssues);
        }

        if ($translationIssues === 0) {
            $this->success('✅ All translation files are valid');
        } else {
            $this->issuesFound += $translationIssues;
        }
    }

    private function checkTemplateTranslations(
        string $key,
        string $templateClass,
        int &$translationIssues,
    ): void {
        try {
            if (!class_exists($templateClass)) {
                return; // Already reported in template check
            }

            $availableTranslations = $templateClass::getAvailableTranslations();

            if (empty($availableTranslations)) {
                $this->warning("⚠️  No translations configured for {$key}");
                $translationIssues++;

                return;
            }

            foreach ($availableTranslations as $translationClass) {
                $this->checkTranslationClass($key, $translationClass, $translationIssues);
            }
        } catch (Throwable $e) {
            $this->error("❌ Error checking translations for {$key}: " . $e->getMessage());
            $translationIssues++;
        }
    }

    private function checkTranslationClass(
        string $templateKey,
        string $translationClass,
        int &$translationIssues,
    ): void {
        if (!class_exists($translationClass)) {
            $this->error("❌ Translation class not found: {$translationClass}");
            $translationIssues++;

            return;
        }

        try {
            $reflection = new ReflectionClass($translationClass);

            // Check if extends BaseTranslation
            if (!$reflection->isSubclassOf(BaseTranslation::class)) {
                $this->error(
                    "❌ Translation class does not extend BaseTranslation: {$translationClass}",
                );
                $translationIssues++;
            }

            // Check if can be instantiated
            $instance = new $translationClass();
            $translations = $instance->getTranslations();

            // Check structure
            if (!isset($translations['meta']) || !isset($translations['blocks'])) {
                $this->error("❌ Invalid translation structure in {$translationClass}");
                $translationIssues++;
            } else {
                if ($this->option('detailed')) {
                    $locale = $translationClass::getLocaleCode();
                    $this->line(
                        "   ✅ {$templateKey} ({$locale}) - "
                        . basename(str_replace('\\', '/', $translationClass)),
                    );
                }
            }
        } catch (Throwable $e) {
            $this->error(
                "❌ Error checking translation class {$translationClass}: " . $e->getMessage(),
            );
            $translationIssues++;
        }
    }

    private function section(string $title): void
    {
        $this->comment("📋 {$title}");
    }

    private function success(string $message): void
    {
        $this->line("<info>{$message}</info>");
    }

    private function warning(string $message): void
    {
        $this->line("<comment>{$message}</comment>");
    }

    private function displaySummary(): void
    {
        $this->line(str_repeat('=', 50));
        $this->comment('📊 DIAGNOSIS SUMMARY');
        $this->line(str_repeat('=', 50));

        $this->info("Checks performed: {$this->checksPerformed}");

        if ($this->issuesFound === 0) {
            $this->success('🎉 No issues found! Document template system is healthy.');
        } else {
            $this->error("❌ Issues found: {$this->issuesFound}");
            $this->newLine();
            $this->comment('💡 Suggestions:');
            $this->line('1. Fix any missing or invalid template classes');
            $this->line('2. Ensure all translation files exist and follow the correct structure');
            $this->line('3. Verify templates implement the DocumentTemplateContract interface');
            $this->line('4. Run this command again after making fixes');
        }
    }
}
