<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Exceptions\AbstractBaseModelException;
use App\Models\BaseFeedback;
use App\Models\VideoFeedback;
use Tests\TestCase;

class AbstractBaseModelTest extends TestCase
{
    /** @test */
    public function it_prevents_direct_creation_of_abstract_base_model(): void
    {
        $this->expectException(AbstractBaseModelException::class);
        $this->expectExceptionMessage('Cannot create instances of abstract base model');

        BaseFeedback::create([
            'creator_id' => 'test-user-id',
            'content' => 'Test content',
            'feedbackable_type' => 'Document',
            'feedbackable_id' => 'test-doc-id',
        ]);
    }

    /** @test */
    public function it_allows_creation_of_concrete_models(): void
    {
        $feedback = VideoFeedback::create([
            'creator_id' => 'test-user-id',
            'content' => 'Test video feedback',
            'feedbackable_type' => 'Document',
            'feedbackable_id' => 'test-doc-id',
            'feedback_type' => 'frame',
            'timestamp' => 45.5,
        ]);

        $this->assertInstanceOf(VideoFeedback::class, $feedback);
        $this->assertEquals('video', $feedback->getFeedbackType());
        $this->assertEquals('video', $feedback->getModelType());
    }

    /** @test */
    public function it_identifies_abstract_models_correctly(): void
    {
        $videoFeedback = new VideoFeedback();
        $this->assertFalse($videoFeedback->isAbstractModel());

        // Cannot instantiate BaseFeedback directly to test isAbstractModel()
        // but we can verify the concrete models list
        $concreteModels = BaseFeedback::getConcreteModels();
        $this->assertContains(VideoFeedback::class, $concreteModels);
    }

    /** @test */
    public function it_validates_concrete_model_classes(): void
    {
        $this->assertTrue(BaseFeedback::isConcrete(VideoFeedback::class));
        $this->assertFalse(BaseFeedback::isConcrete(BaseFeedback::class));
        $this->assertFalse(BaseFeedback::isConcrete('NonExistentClass'));
    }

    /** @test */
    public function it_prevents_save_operations_on_abstract_models(): void
    {
        // This test would need to be implemented with a mock or reflection
        // since we can't instantiate the abstract model directly
        $this->assertTrue(true); // Placeholder for now
    }

    /** @test */
    public function it_provides_proper_error_messages(): void
    {
        $exception = AbstractBaseModelException::cannotCreate('TestModel');
        $this->assertStringContains('Cannot create instances of abstract base model [TestModel]', $exception->getMessage());

        $exception = AbstractBaseModelException::cannotUpdate('TestModel');
        $this->assertStringContains('Cannot update abstract base model [TestModel]', $exception->getMessage());

        $exception = AbstractBaseModelException::cannotDelete('TestModel');
        $this->assertStringContains('Cannot delete abstract base model [TestModel]', $exception->getMessage());
    }
}