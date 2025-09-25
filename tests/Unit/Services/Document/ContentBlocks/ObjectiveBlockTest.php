<?php

declare(strict_types=1);

use App\Services\Document\ContentBlocks\ObjectiveBlock;
use BumpCore\EditorPhp\EditorPhp;
use Faker\Factory as FakerFactory;

describe('ObjectiveBlock', function () {
    beforeEach(function () {
        // Register the ObjectiveBlock for testing
        EditorPhp::register([
            'objective' => ObjectiveBlock::class,
        ]);
    });
    it('can create an instance with default data', function () {
        $block = new ObjectiveBlock;

        expect($block)->toBeInstanceOf(ObjectiveBlock::class);
        expect($block->isEmpty())->toBeTrue();
    });

    it('can create an instance with valid data', function () {
        $data = [
            'name' => 'زيادة الوعي بالعلامة التجارية',
            'operator' => ObjectiveBlock::OPERATOR_INCREASE,
            'percentage' => 25.5,
        ];

        $block = new ObjectiveBlock($data);

        expect($block->isEmpty())->toBeFalse();
        expect($block->getName())->toBe('زيادة الوعي بالعلامة التجارية');
        expect($block->getOperator())->toBe(ObjectiveBlock::OPERATOR_INCREASE);
        expect($block->getPercentage())->toBe(25.5);
    });

    it('validates fields through rules', function () {
        $block = new ObjectiveBlock;
        $rules = $block->rules();

        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('operator');
        expect($rules)->toHaveKey('percentage');
        expect($rules['name'])->toContain('nullable');
        expect($rules['operator'])->toContain('nullable');
        expect($rules['percentage'])->toContain('nullable');
    });

    it('generates realistic fake data', function () {
        $faker = FakerFactory::create();
        $fakeData = ObjectiveBlock::fake($faker);

        expect($fakeData)->toHaveKeys(['name', 'operator', 'percentage']);
        expect($fakeData['name'])->toBeString();
        expect($fakeData['operator'])->toBeIn(ObjectiveBlock::OPERATORS);
        expect($fakeData['percentage'])->toBeNumeric();
        expect($fakeData['percentage'])->toBeGreaterThanOrEqual(0);
        expect($fakeData['percentage'])->toBeLessThanOrEqual(100);
    });

    it('correctly identifies operator types', function () {
        $increaseBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_INCREASE]);
        $decreaseBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_DECREASE]);
        $equalBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_EQUAL]);

        expect($increaseBlock->isIncrease())->toBeTrue();
        expect($increaseBlock->isDecrease())->toBeFalse();
        expect($increaseBlock->isEqual())->toBeFalse();

        expect($decreaseBlock->isIncrease())->toBeFalse();
        expect($decreaseBlock->isDecrease())->toBeTrue();
        expect($decreaseBlock->isEqual())->toBeFalse();

        expect($equalBlock->isIncrease())->toBeFalse();
        expect($equalBlock->isDecrease())->toBeFalse();
        expect($equalBlock->isEqual())->toBeTrue();
    });

    it('returns correct operator symbols', function () {
        $increaseBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_INCREASE]);
        $decreaseBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_DECREASE]);
        $equalBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_EQUAL]);

        expect($increaseBlock->getOperatorSymbol())->toBe('↑');
        expect($decreaseBlock->getOperatorSymbol())->toBe('↓');
        expect($equalBlock->getOperatorSymbol())->toBe('=');
    });

    it('returns correct operator text in Arabic', function () {
        $increaseBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_INCREASE]);
        $decreaseBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_DECREASE]);
        $equalBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_EQUAL]);

        expect($increaseBlock->getOperatorTextArabic())->toBe('زيادة بنسبة');
        expect($decreaseBlock->getOperatorTextArabic())->toBe('انخفاض بنسبة');
        expect($equalBlock->getOperatorTextArabic())->toBe('يساوي');
    });

    it('returns correct operator text in English', function () {
        $increaseBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_INCREASE]);
        $decreaseBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_DECREASE]);
        $equalBlock = new ObjectiveBlock(['operator' => ObjectiveBlock::OPERATOR_EQUAL]);

        expect($increaseBlock->getOperatorTextEnglish())->toBe('increase by');
        expect($decreaseBlock->getOperatorTextEnglish())->toBe('decrease by');
        expect($equalBlock->getOperatorTextEnglish())->toBe('equals');
    });

    it('validates percentage correctly', function () {
        $validBlock = new ObjectiveBlock(['percentage' => 50]);
        $invalidLowBlock = new ObjectiveBlock(['percentage' => -10]); // Will be rejected by validation and default to 0
        $invalidHighBlock = new ObjectiveBlock(['percentage' => 150]); // Will be rejected by validation and default to 0
        $zeroBlock = new ObjectiveBlock(['percentage' => 0]);
        $hundredBlock = new ObjectiveBlock(['percentage' => 100]);

        expect($validBlock->hasValidPercentage())->toBeTrue();
        // Invalid values are rejected by validation, so they default to 0 which is valid
        expect($invalidLowBlock->hasValidPercentage())->toBeTrue();
        expect($invalidHighBlock->hasValidPercentage())->toBeTrue();
        expect($zeroBlock->hasValidPercentage())->toBeTrue();
        expect($hundredBlock->hasValidPercentage())->toBeTrue();

        // Verify that invalid values were actually rejected and defaulted
        expect($invalidLowBlock->getPercentage())->toBe(0.0);
        expect($invalidHighBlock->getPercentage())->toBe(0.0);
    });

    it('formats percentage display correctly', function () {
        $integerBlock = new ObjectiveBlock(['percentage' => 50]);
        $decimalBlock = new ObjectiveBlock(['percentage' => 25.5]);
        $zeroBlock = new ObjectiveBlock(['percentage' => 0]);

        expect($integerBlock->getFormattedPercentage())->toBe('50%');
        expect($decimalBlock->getFormattedPercentage())->toBe('25.5%');
        expect($zeroBlock->getFormattedPercentage())->toBe('0%');
    });

    it('correctly identifies empty blocks', function () {
        $emptyBlock = new ObjectiveBlock;
        $noNameBlock = new ObjectiveBlock([
            'operator' => ObjectiveBlock::OPERATOR_INCREASE,
            'percentage' => 50,
        ]);
        $invalidOperatorBlock = new ObjectiveBlock([
            'name' => 'Test',
            'operator' => 'invalid',
            'percentage' => 50,
        ]);
        $negativePercentageBlock = new ObjectiveBlock([
            'name' => 'Test',
            'operator' => ObjectiveBlock::OPERATOR_INCREASE,
            'percentage' => -5,
        ]);
        $validBlock = new ObjectiveBlock([
            'name' => 'Test',
            'operator' => ObjectiveBlock::OPERATOR_INCREASE,
            'percentage' => 50,
        ]);

        expect($emptyBlock->isEmpty())->toBeTrue();
        expect($noNameBlock->isEmpty())->toBeTrue();
        expect($invalidOperatorBlock->isEmpty())->toBeTrue();
        expect($negativePercentageBlock->isEmpty())->toBeTrue();
        expect($validBlock->isEmpty())->toBeFalse();
    });

    it('renders HTML output correctly', function () {
        $data = [
            'name' => 'زيادة الوعي بالعلامة التجارية',
            'operator' => ObjectiveBlock::OPERATOR_INCREASE,
            'percentage' => 25,
        ];

        $block = new ObjectiveBlock($data);
        $html = $block->render();

        expect($html)->toContain('objective-block');
        expect($html)->toContain('data-block-type="objective"');
        expect($html)->toContain('زيادة الوعي بالعلامة التجارية');
        expect($html)->toContain('25%');
        expect($html)->toContain('↑');
        expect($html)->toContain('زيادة بنسبة');
        expect($html)->toContain('increase by');
        expect($html)->toContain('dir="auto"');
        expect($html)->toContain('text-emerald-600'); // Increase color
    });

    it('renders different colors for different operators', function () {
        $increaseBlock = new ObjectiveBlock([
            'name' => 'Test',
            'operator' => ObjectiveBlock::OPERATOR_INCREASE,
            'percentage' => 25,
        ]);

        $decreaseBlock = new ObjectiveBlock([
            'name' => 'Test',
            'operator' => ObjectiveBlock::OPERATOR_DECREASE,
            'percentage' => 25,
        ]);

        $equalBlock = new ObjectiveBlock([
            'name' => 'Test',
            'operator' => ObjectiveBlock::OPERATOR_EQUAL,
            'percentage' => 25,
        ]);

        $increaseHtml = $increaseBlock->render();
        $decreaseHtml = $decreaseBlock->render();
        $equalHtml = $equalBlock->render();

        expect($increaseHtml)->toContain('text-emerald-600');
        expect($decreaseHtml)->toContain('text-red-600');
        expect($equalHtml)->toContain('text-sky-600');
    });

    it('returns empty string when rendering empty block', function () {
        $emptyBlock = new ObjectiveBlock;

        expect($emptyBlock->render())->toBe('');
    });

    it('handles HTML escaping in content', function () {
        $data = [
            'name' => '<script>alert("test")</script>زيادة الوعي',
            'operator' => ObjectiveBlock::OPERATOR_INCREASE,
            'percentage' => 25,
        ];

        $block = new ObjectiveBlock($data);
        $html = $block->render();

        expect($html)->not->toContain('<script>');
        expect($html)->toContain('&lt;script&gt;');
        expect($html)->toContain('زيادة الوعي');
    });

    it('includes proper accessibility attributes', function () {
        $data = [
            'name' => 'Test Objective',
            'operator' => ObjectiveBlock::OPERATOR_INCREASE,
            'percentage' => 25,
        ];

        $block = new ObjectiveBlock($data);
        $html = $block->render();

        expect($html)->toContain('aria-label="Objective operator"');
        expect($html)->toContain('aria-label="Percentage"');
    });

    it('supports RTL text direction', function () {
        $data = [
            'name' => 'هدف تجريبي',
            'operator' => ObjectiveBlock::OPERATOR_INCREASE,
            'percentage' => 25,
        ];

        $block = new ObjectiveBlock($data);
        $html = $block->render();

        expect($html)->toContain('dir="auto"');
        expect($html)->toContain('dir="rtl"');
        expect($html)->toContain('dir="ltr"');
    });

    it('can be converted to array', function () {
        $data = [
            'name' => 'Test Objective',
            'operator' => ObjectiveBlock::OPERATOR_INCREASE,
            'percentage' => 25,
        ];

        $block = new ObjectiveBlock($data);
        $array = $block->toArray();

        expect($array)->toHaveKey('type');
        expect($array)->toHaveKey('data');
        expect($array['data'])->toHaveKey('name');
        expect($array['data'])->toHaveKey('operator');
        expect($array['data'])->toHaveKey('percentage');
    });
});
