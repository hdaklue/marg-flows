<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class DocumentCreateTemplateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'document:create-template {name? : The name of the template}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new document template scaffold';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $templateName = $this->argument('name') ?: $this->askForTemplateName();

        if (empty($templateName)) {
            $this->error('Template name is required.');

            return self::FAILURE;
        }

        $className = Str::studly($templateName);
        $templateKey = Str::lower($className);
        $templatePath = app_path("Services/Document/Templates/{$className}");

        // Check if template already exists
        if (File::exists($templatePath)) {
            $this->error("Template '{$className}' already exists at {$templatePath}");

            return self::FAILURE;
        }

        $this->info("Creating template: {$className}");

        // Create directories
        $this->createDirectories($templatePath);

        // Generate template files
        $this->generateTemplateClass($className, $templateKey, $templatePath);
        $this->generateTranslationFiles($className, $templateKey, $templatePath, $templateName);

        // Update config
        $this->updateDocumentConfig($className, $templateKey);

        $this->newLine();
        $this->info("✅ Template '{$className}' created successfully!");
        $this->line("📁 Location: {$templatePath}");
        $this->line('📝 Config updated: config/document.php');

        $this->newLine();
        $this->comment('Next steps:');
        $this->line('1. Customize the template blocks in the getBlocks() method');
        $this->line('2. Update translations in the translation files');
        $this->line('3. Run document:doctor to validate the setup');

        return self::SUCCESS;
    }

    private function askForTemplateName(): string
    {
        return $this->ask('What is the name of the template?', 'MyTemplate');
    }

    private function createDirectories(string $templatePath): void
    {
        $this->info('📁 Creating directories...');

        File::ensureDirectoryExists($templatePath);
        File::ensureDirectoryExists("{$templatePath}/Translations");

        $this->line("   - {$templatePath}");
        $this->line("   - {$templatePath}/Translations");
    }

    private function generateTemplateClass(
        string $className,
        string $templateKey,
        string $templatePath,
    ): void {
        $this->info('📝 Generating template class...');

        $namespace = "App\\Services\\Document\\Templates\\{$className}";
        $stubPath = base_path('stubs/template.stub');
        $stub = File::get($stubPath);

        $content = str_replace(
            ['{{ namespace }}', '{{ className }}', '{{ templateKey }}'],
            [$namespace, $className, $templateKey],
            $stub,
        );

        $filePath = "{$templatePath}/{$className}.php";
        File::put($filePath, $content);

        $this->line("   - {$filePath}");
    }

    private function generateTranslationFiles(
        string $className,
        string $templateKey,
        string $templatePath,
        string $templateName,
    ): void {
        $this->info('🌐 Generating translation files...');

        $translationsPath = "{$templatePath}/Translations";

        // Generate English translation
        $this->generateTranslationFile('En', $translationsPath, $className, [
            'templateName' => $templateName,
            'templateDescription' => "{$templateName} Template",
        ]);

        // Generate Arabic translation
        $this->generateTranslationFile('Ar', $translationsPath, $className, [
            'templateNameAr' => $templateName, // User can customize this
            'templateDescriptionAr' => "قالب {$templateName}",
        ]);
    }

    private function generateTranslationFile(
        string $locale,
        string $translationsPath,
        string $className,
        array $replacements,
    ): void {
        $namespace = "App\\Services\\Document\\Templates\\{$className}\\Translations";
        $stubPath = base_path('stubs/translation-' . strtolower($locale) . '.stub');
        $stub = File::get($stubPath);

        $replacementKeys = array_merge(
            ['{{ namespace }}'],
            array_map(fn($key) => "{{ {$key} }}", array_keys($replacements)),
        );

        $replacementValues = array_merge([$namespace], array_values($replacements));

        $content = str_replace($replacementKeys, $replacementValues, $stub);

        $filePath = "{$translationsPath}/{$locale}.php";
        File::put($filePath, $content);

        $this->line("   - {$filePath}");
    }

    private function updateDocumentConfig(string $className, string $templateKey): void
    {
        $this->info('⚙️ Updating configuration...');

        $configPath = config_path('document.php');
        $configContent = File::get($configPath);

        // Add use statement if not exists
        $useStatement = "use App\\Services\\Document\\Templates\\{$className}\\{$className};";
        if (!str_contains($configContent, $useStatement)) {
            $configContent = str_replace(
                "use App\Services\Document\Templates\General\General;",
                "use App\Services\Document\Templates\General\General;\n{$useStatement}",
                $configContent,
            );
        }

        // Add template to templates array
        $templateEntry = "        '{$templateKey}' => {$className}::class,";
        $configContent = str_replace(
            "'general' => General::class,",
            "'general' => General::class,\n{$templateEntry}",
            $configContent,
        );

        File::put($configPath, $configContent);

        $this->line('   - Added to config/document.php');
    }
}
