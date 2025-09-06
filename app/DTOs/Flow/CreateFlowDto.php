<?php

declare(strict_types=1);

namespace App\DTOs\Flow;

use App\Enums\FlowStage;
use App\Services\Flow\TemplateService;
use Illuminate\Validation\Rule;
use WendellAdriel\ValidatedDTO\Casting\EnumCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class CreateFlowDto extends ValidatedDTO
{
    public string $title;

    public ?FlowStage $stage;

    public ?string $description;

    public ?string $id;

    protected function rules(): array
    {

        return [
            'title' => ['required', 'string', 'min:3', 'max:100'],
            'stage' => ['sometimes', Rule::enum(FlowStage::class)],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'stage' => $this->evaluateDefaultStage(),
        ];
    }

    protected function getDefaultTemplate(): FlowTemplateDto
    {
        return TemplateService::getDefault();
    }

    protected function evaluateDefaultStage(): FlowStage
    {

        return FlowStage::ACTIVE;
    }

    protected function casts(): array
    {
        return [
            'stage' => new EnumCast(FlowStage::class),
        ];
    }
}
