<?php

declare(strict_types=1);

use App\Services\Document\ConfigBuilder\Blocks\DTO\ObjectiveConfigData;
use App\Services\Document\ConfigBuilder\Blocks\Objective;

describe('Objective Config Builder', function () {
    it('can create instance with default configuration', function () {
        $objective = new Objective();
        $config = $objective->build();

        expect($config)->toBeInstanceOf(ObjectiveConfigData::class);
        expect($config->class)->toBe('Objective');
        expect($config->inlineToolBar)->toBeFalse();
        expect($config->shortcut)->toBe('CMD+SHIFT+O');
        expect($config->tunes)->toContain('commentTune');
    });

    it('has default configuration values', function () {
        $objective = new Objective();
        $array = $objective->toArray();

        expect($array['config'])->toHaveKey('operators');
        expect($array['config'])->toHaveKey('defaultOperator');
        expect($array['config'])->toHaveKey('namePlaceholder');
        expect($array['config'])->toHaveKey('percentagePlaceholder');
        expect($array['config'])->toHaveKey('operatorLabels');
        expect($array['config']['operators'])->toContain('increase');
        expect($array['config']['operators'])->toContain('decrease');
        expect($array['config']['operators'])->toContain('equal');
        expect($array['config']['defaultOperator'])->toBe('increase');
        expect($array['config']['rtlSupport'])->toBeTrue();
        expect($array['config']['allowDecimals'])->toBeTrue();
    });

    it('can customize operators', function () {
        $objective = new Objective();
        $customOperators = ['increase', 'decrease'];

        $objective->operators($customOperators);
        $config = $objective->toArray();

        expect($config['config']['operators'])->toBe($customOperators);
    });

    it('can set default operator', function () {
        $objective = new Objective();

        $objective->defaultOperator('decrease');
        $config = $objective->toArray();

        expect($config['config']['defaultOperator'])->toBe('decrease');
    });

    it('can customize placeholders', function () {
        $objective = new Objective();

        $objective->namePlaceholder('Custom name placeholder');
        $objective->percentagePlaceholder('Custom percentage placeholder');
        $config = $objective->toArray();

        expect($config['config']['namePlaceholder'])->toBe('Custom name placeholder');
        expect($config['config']['percentagePlaceholder'])->toBe('Custom percentage placeholder');
    });

    it('can set percentage range', function () {
        $objective = new Objective();

        $objective->percentageRange(10, 90);
        $config = $objective->toArray();

        expect($config['config']['minPercentage'])->toBe(10);
        expect($config['config']['maxPercentage'])->toBe(90);
    });

    it('can toggle decimal support', function () {
        $objective = new Objective();

        $objective->allowDecimals(false);
        $config = $objective->toArray();

        expect($config['config']['allowDecimals'])->toBeFalse();

        $objective->allowDecimals(true);
        $config = $objective->toArray();

        expect($config['config']['allowDecimals'])->toBeTrue();
    });

    it('can toggle RTL support', function () {
        $objective = new Objective();

        $objective->rtlSupport(false);
        $config = $objective->toArray();

        expect($config['config']['rtlSupport'])->toBeFalse();

        $objective->rtlSupport(true);
        $config = $objective->toArray();

        expect($config['config']['rtlSupport'])->toBeTrue();
    });

    it('can customize operator labels', function () {
        $objective = new Objective();
        $customLabels = [
            'increase' => [
                'ar' => 'زيادة مخصصة',
                'en' => 'Custom Increase',
                'symbol' => '⬆',
            ],
        ];

        $objective->operatorLabels($customLabels);
        $config = $objective->toArray();

        expect($config['config']['operatorLabels']['increase']['ar'])->toBe('زيادة مخصصة');
        expect($config['config']['operatorLabels']['increase']['en'])->toBe('Custom Increase');
        expect($config['config']['operatorLabels']['increase']['symbol'])->toBe('⬆');
    });

    it('can customize shortcut', function () {
        $objective = new Objective();

        $objective->shortcut('CMD+ALT+O');
        $config = $objective->toArray();

        expect($config['shortcut'])->toBe('CMD+ALT+O');

        $objective->shortcut(null);
        $config = $objective->toArray();

        expect($config['shortcut'])->toBeNull();
    });

    it('can toggle inline toolbar', function () {
        $objective = new Objective();

        $objective->inlineToolBar(true);
        $config = $objective->toArray();

        expect($config['inlineToolBar'])->toBeTrue();

        $objective->inlineToolBar(false);
        $config = $objective->toArray();

        expect($config['inlineToolBar'])->toBeFalse();
    });

    it('can customize tunes', function () {
        $objective = new Objective();
        $customTunes = ['commentTune', 'customTune'];

        $objective->tunes($customTunes);
        $config = $objective->toArray();

        expect($config['tunes'])->toBe($customTunes);
    });

    it('can output as JSON', function () {
        $objective = new Objective();
        $json = $objective->toJson();

        expect($json)->toBeString();
        expect(json_decode($json, true))->toBeArray();
    });

    it('can output as pretty JSON', function () {
        $objective = new Objective();
        $prettyJson = $objective->toPrettyJson();

        expect($prettyJson)->toBeString();
        expect($prettyJson)->toContain("\n"); // Should contain newlines for pretty formatting
    });

    it('can chain configuration methods', function () {
        $objective = new Objective();

        $result = $objective
            ->operators(['increase', 'decrease'])
            ->defaultOperator('decrease')
            ->namePlaceholder('Test placeholder')
            ->percentageRange(0, 50)
            ->allowDecimals(false)
            ->rtlSupport(false)
            ->shortcut('CMD+O')
            ->inlineToolBar(true);

        expect($result)->toBeInstanceOf(Objective::class);

        $config = $result->toArray();
        expect($config['config']['operators'])->toBe(['increase', 'decrease']);
        expect($config['config']['defaultOperator'])->toBe('decrease');
        expect($config['config']['namePlaceholder'])->toBe('Test placeholder');
        expect($config['config']['maxPercentage'])->toBe(50);
        expect($config['config']['allowDecimals'])->toBeFalse();
        expect($config['config']['rtlSupport'])->toBeFalse();
        expect($config['shortcut'])->toBe('CMD+O');
        expect($config['inlineToolBar'])->toBeTrue();
    });

    it('maintains operator labels structure integrity', function () {
        $objective = new Objective();
        $config = $objective->toArray();

        $operatorLabels = $config['config']['operatorLabels'];

        foreach (['increase', 'decrease', 'equal'] as $operator) {
            expect($operatorLabels)->toHaveKey($operator);
            expect($operatorLabels[$operator])->toHaveKey('ar');
            expect($operatorLabels[$operator])->toHaveKey('en');
            expect($operatorLabels[$operator])->toHaveKey('symbol');
            expect($operatorLabels[$operator]['ar'])->toBeString();
            expect($operatorLabels[$operator]['en'])->toBeString();
            expect($operatorLabels[$operator]['symbol'])->toBeString();
        }
    });
});
