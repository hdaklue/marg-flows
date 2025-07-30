<?php

declare(strict_types=1);

namespace Database\Seeders\BusinessDB;

use App\Enums\Feedback\FeedbackStatus;
use App\Enums\Feedback\FeedbackUrgency;
use App\Models\Document;
use App\Models\GeneralFeedback;
use App\Models\User;
use Illuminate\Database\Seeder;

final class GeneralFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating general feedback in business database...');

        // Get test user from main database
        $testUser = User::where('email', 'test@example.com')->first();
        if (!$testUser) {
            $this->command->warn('Test user not found. Please run main DatabaseSeeder first.');
            return;
        }

        // Get documents from business database to associate feedback with
        $documents = Document::limit(5)->get();
        if ($documents->isEmpty()) {
            $this->command->warn('No documents found in business database. Please run DocumentSeeder first.');
            return;
        }

        $statuses = [
            FeedbackStatus::OPEN,
            FeedbackStatus::IN_PROGRESS,
            FeedbackStatus::RESOLVED,
            FeedbackStatus::URGENT,
        ];

        $urgencies = [
            FeedbackUrgency::LOW,
            FeedbackUrgency::NORMAL,
            FeedbackUrgency::HIGH,
            FeedbackUrgency::URGENT,
        ];

        $categories = [
            'ui', 'ux', 'content', 'functionality', 'performance', 
            'accessibility', 'security', 'bug', 'feature', 
            'improvement', 'question', 'other'
        ];

        // Create general feedback for each document
        foreach ($documents as $document) {
            // Create 3-5 general feedback entries per document
            $feedbackCount = rand(3, 5);
            
            for ($i = 1; $i <= $feedbackCount; $i++) {
                $category = fake()->randomElement($categories);
                
                GeneralFeedback::create([
                    'creator_id' => $testUser->id,
                    'content' => $this->generateGeneralComment($category),
                    'feedbackable_type' => $document->getMorphClass(),
                    'feedbackable_id' => $document->id,
                    'status' => fake()->randomElement($statuses),
                    'urgency' => fake()->randomElement($urgencies),
                    'feedback_category' => $category,
                    'metadata' => $this->generateMetadata($category),
                    'custom_data' => $this->generateCustomData($category),
                ]);
            }
        }

        // Create specialized general feedback for testing
        $this->createSpecializedGeneralFeedback($testUser, $documents->first());

        $totalGeneralFeedback = GeneralFeedback::count();
        $this->command->info("✅ Created {$totalGeneralFeedback} general feedback entries in business database");
    }

    private function generateGeneralComment(string $category): string
    {
        $comments = [
            'ui' => [
                'The user interface could be more intuitive in this section.',
                'Button styling is inconsistent with the design system.',
                'The color scheme works well for accessibility.',
                'Icons could be more recognizable for better usability.',
                'The layout adapts well to different screen sizes.',
            ],
            'ux' => [
                'User experience flow could be simplified here.',
                'The interaction pattern is confusing for new users.',
                'Great user journey - very intuitive and smooth.',
                'Navigation could be more discoverable.',
                'The feedback mechanism works excellently.',
            ],
            'content' => [
                'Content quality is excellent and well-researched.',
                'Some sections need more detailed explanations.',
                'The tone and voice are consistent throughout.',
                'Content could be more scannable with better formatting.',
                'Information architecture is well-organized.',
            ],
            'functionality' => [
                'This feature works as expected - great implementation.',
                'Functionality could be extended with additional options.',
                'The integration with other systems is seamless.',
                'Performance of this feature is excellent.',
                'Error handling could be more robust.',
            ],
            'performance' => [
                'Loading times are acceptable but could be improved.',
                'Great performance optimization - very fast response.',
                'Memory usage seems higher than expected.',
                'Database queries could be optimized further.',
                'Caching strategy is working effectively.',
            ],
            'accessibility' => [
                'Screen reader compatibility needs improvement.',
                'Keyboard navigation works well throughout.',
                'Color contrast meets WCAG standards.',
                'Alt text for images could be more descriptive.',
                'Focus indicators are clear and visible.',
            ],
            'security' => [
                'Input validation appears to be properly implemented.',
                'Authentication flow is secure and user-friendly.',
                'Consider adding additional security headers.',
                'Data encryption is properly implemented.',
                'Session management follows best practices.',
            ],
            'bug' => [
                'Found a reproducible bug in this functionality.',
                'Edge case causing unexpected behavior.',
                'Browser compatibility issue in older versions.',
                'Data validation error under specific conditions.',
                'Layout breaks on certain screen resolutions.',
            ],
            'feature' => [
                'Would love to see a dark mode option added.',
                'Export functionality would be very useful here.',
                'Consider adding keyboard shortcuts for power users.',
                'Integration with external tools would be valuable.',
                'Bulk operations would improve efficiency.',
            ],
            'improvement' => [
                'This could be enhanced with better visual feedback.',
                'Consider adding more granular permissions.',
                'The workflow could be streamlined further.',
                'User onboarding could be more comprehensive.',
                'Help documentation could be more detailed.',
            ],
            'question' => [
                'How is this data being processed and stored?',
                'What are the recommended browser requirements?',
                'Is there a way to customize this behavior?',
                'Can this feature be integrated with our existing tools?',
                'What are the performance implications of this change?',
            ],
            'other' => [
                'General feedback about the overall experience.',
                'Some thoughts on the implementation approach.',
                'Observations about user behavior patterns.',
                'Suggestions for future development direction.',
                'Comments on the project timeline and scope.',
            ],
        ];

        $categoryComments = $comments[$category] ?? [
            'General feedback that needs attention.',
            'This aspect could be improved.',
            'Overall implementation is good.',
            'Consider reviewing this area.',
        ];

        return fake()->randomElement($categoryComments);
    }

    private function generateMetadata(string $category): ?array
    {
        if (!fake()->boolean(80)) { // 80% chance of having metadata
            return null;
        }

        $baseMetadata = [
            'type' => 'general',
            'category' => $category,
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'reported_at' => now()->toISOString(),
            'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'platform' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
        ];

        // Add category-specific metadata
        return match ($category) {
            'ui' => array_merge($baseMetadata, [
                'component' => fake()->randomElement(['button', 'form', 'navigation', 'modal', 'dropdown']),
                'design_system_version' => '2.1.0',
                'screen_resolution' => fake()->randomElement(['1920x1080', '1366x768', '414x896', '375x667']),
                'color_scheme' => fake()->randomElement(['light', 'dark', 'auto']),
            ]),
            'ux' => array_merge($baseMetadata, [
                'user_journey_step' => fake()->randomElement(['onboarding', 'discovery', 'conversion', 'retention']),
                'interaction_type' => fake()->randomElement(['click', 'hover', 'scroll', 'keyboard', 'touch']),
                'user_type' => fake()->randomElement(['new', 'returning', 'power_user', 'admin']),
                'completion_rate' => fake()->randomFloat(2, 0.1, 1.0),
            ]),
            'performance' => array_merge($baseMetadata, [
                'load_time' => fake()->randomFloat(3, 0.1, 5.0),
                'memory_usage' => fake()->numberBetween(50, 500) . 'MB',
                'network_speed' => fake()->randomElement(['slow-3g', 'fast-3g', '4g', 'wifi']),
                'device_type' => fake()->randomElement(['desktop', 'tablet', 'mobile']),
            ]),
            'bug' => array_merge($baseMetadata, [
                'reproduction_steps' => [
                    'Step 1: Navigate to the page',
                    'Step 2: Click on the problematic element',
                    'Step 3: Observe the issue',
                ],
                'expected_behavior' => 'Should work as intended',
                'actual_behavior' => 'Does not work as expected',
                'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
                'affects_versions' => ['2.1.0', '2.0.5'],
            ]),
            'accessibility' => array_merge($baseMetadata, [
                'wcag_level' => fake()->randomElement(['A', 'AA', 'AAA']),
                'assistive_technology' => fake()->randomElement(['screen_reader', 'keyboard_only', 'voice_control']),
                'contrast_ratio' => fake()->randomFloat(2, 1.0, 21.0),
                'compliance_status' => fake()->randomElement(['compliant', 'partial', 'non_compliant']),
            ]),
            default => $baseMetadata,
        };
    }

    private function generateCustomData(string $category): ?array
    {
        if (!fake()->boolean(60)) { // 60% chance of having custom data
            return null;
        }

        return [
            'tags' => fake()->randomElements(['important', 'quick-fix', 'enhancement', 'breaking-change', 'documentation'], rand(1, 3)),
            'estimated_effort' => fake()->randomElement(['1h', '4h', '1d', '3d', '1w', '2w']),
            'stakeholders' => fake()->randomElements(['design-team', 'dev-team', 'product-manager', 'qa-team'], rand(1, 3)),
            'external_references' => [
                'ticket_id' => 'PROJ-' . fake()->numberBetween(1000, 9999),
                'design_file' => fake()->boolean(40) ? 'https://figma.com/file/' . fake()->uuid() : null,
                'documentation' => fake()->boolean(30) ? 'https://docs.example.com/feature/' . fake()->slug() : null,
            ],
            'metrics' => [
                'user_impact' => fake()->randomElement(['low', 'medium', 'high']),
                'business_value' => fake()->randomElement(['low', 'medium', 'high']),
                'technical_debt' => fake()->randomElement(['none', 'minor', 'moderate', 'significant']),
            ],
        ];
    }

    private function createSpecializedGeneralFeedback(User $user, Document $document): void
    {
        // High-priority bug report with detailed metadata
        GeneralFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Critical security vulnerability found - immediate attention required!',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::URGENT,
            'urgency' => FeedbackUrgency::URGENT,
            'feedback_category' => 'security',
            'metadata' => [
                'type' => 'security_vulnerability',
                'severity' => 'critical',
                'cve_score' => 9.8,
                'affected_endpoints' => ['/api/users', '/api/admin'],
                'vulnerability_type' => 'sql_injection',
                'discovered_by' => 'automated_scan',
                'scan_date' => now()->subDays(1)->toISOString(),
            ],
            'custom_data' => [
                'requires_immediate_action' => true,
                'security_team_notified' => true,
                'patch_available' => false,
                'workaround_available' => true,
                'external_references' => [
                    'security_ticket' => 'SEC-2024-001',
                    'vulnerability_report' => 'VUL-' . fake()->uuid(),
                ],
            ],
        ]);

        // Feature request with user research data
        GeneralFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Users are requesting bulk export functionality based on survey feedback.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::OPEN,
            'urgency' => FeedbackUrgency::NORMAL,
            'feedback_category' => 'feature',
            'metadata' => [
                'type' => 'feature_request',
                'user_research' => [
                    'survey_responses' => 247,
                    'requested_by_percentage' => 73,
                    'priority_ranking' => 2,
                ],
                'competitive_analysis' => [
                    'competitors_with_feature' => 8,
                    'total_competitors_analyzed' => 10,
                ],
                'estimated_development_time' => '2-3 sprints',
            ],
            'custom_data' => [
                'user_stories' => [
                    'As a user, I want to export multiple items at once',
                    'As an admin, I want to export filtered data sets',
                    'As a power user, I want to schedule automatic exports',
                ],
                'acceptance_criteria' => [
                    'Support CSV and JSON export formats',
                    'Handle up to 10,000 records per export',
                    'Provide progress indication for long exports',
                ],
                'stakeholders' => ['product-team', 'design-team', 'dev-team'],
            ],
        ]);

        // Performance improvement with metrics
        GeneralFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Page load times have increased significantly - optimization needed.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::IN_PROGRESS,
            'urgency' => FeedbackUrgency::HIGH,
            'feedback_category' => 'performance',
            'metadata' => [
                'type' => 'performance_degradation',
                'metrics' => [
                    'current_load_time' => 4.2,
                    'previous_load_time' => 1.8,
                    'degradation_percentage' => 133,
                    'first_contentful_paint' => 2.1,
                    'largest_contentful_paint' => 3.8,
                ],
                'affected_pages' => ['/dashboard', '/reports', '/settings'],
                'monitoring_period' => '7_days',
                'trend' => 'increasing',
            ],
            'custom_data' => [
                'optimization_candidates' => [
                    'database_queries' => 'N+1 queries detected',
                    'image_optimization' => 'Large uncompressed images',
                    'javascript_bundles' => 'Bundle size increased 40%',
                    'caching' => 'Cache hit rate decreased to 45%',
                ],
                'performance_budget' => [
                    'target_load_time' => 2.0,
                    'target_bundle_size' => '250KB',
                    'target_cache_hit_rate' => '80%',
                ],
            ],
        ]);

        // Accessibility improvement request
        GeneralFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Multiple accessibility issues identified during audit - need systematic improvements.',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::OPEN,
            'urgency' => FeedbackUrgency::HIGH,
            'feedback_category' => 'accessibility',
            'metadata' => [
                'type' => 'accessibility_audit',
                'audit_tool' => 'axe-core',
                'wcag_version' => '2.1',
                'compliance_level' => 'AA',
                'issues_found' => [
                    'missing_alt_text' => 15,
                    'color_contrast' => 8,
                    'keyboard_navigation' => 5,
                    'focus_indicators' => 12,
                    'aria_labels' => 7,
                ],
                'overall_score' => 72,
                'target_score' => 95,
            ],
            'custom_data' => [
                'remediation_plan' => [
                    'phase_1' => 'Fix critical issues (color contrast, missing alt text)',
                    'phase_2' => 'Improve keyboard navigation and focus management',
                    'phase_3' => 'Enhance ARIA implementation and screen reader support',
                ],
                'testing_strategy' => [
                    'automated_testing' => 'Integrate axe-core in CI/CD pipeline',
                    'manual_testing' => 'Weekly accessibility testing sessions',
                    'user_testing' => 'Quarterly testing with disabled users',
                ],
            ],
        ]);

        // Simple question without much metadata
        GeneralFeedback::create([
            'creator_id' => $user->id,
            'content' => 'Quick question: Is there a keyboard shortcut for saving drafts?',
            'feedbackable_type' => $document->getMorphClass(),
            'feedbackable_id' => $document->id,
            'status' => FeedbackStatus::OPEN,
            'urgency' => FeedbackUrgency::LOW,
            'feedback_category' => 'question',
            'metadata' => [
                'type' => 'user_question',
                'complexity' => 'simple',
                'expected_response_time' => '24h',
            ],
            'custom_data' => null, // Simple questions might not need custom data
        ]);
    }
}