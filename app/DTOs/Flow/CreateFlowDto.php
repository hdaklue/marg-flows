<?php

declare(strict_types=1);

namespace App\DTOs\Flow;

use App\Enums\FlowStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Flow\TemplateService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use WendellAdriel\ValidatedDTO\Casting\CarbonCast;
use WendellAdriel\ValidatedDTO\Casting\EnumCast;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

class CreateFlowDto extends ValidatedDTO
{
    public string $title;

    public ?FlowStatus $status;

    public ?FlowTemplateDto $template;

    public bool $is_default;

    public ?int $order_column;

    public ?array $participants;

    public Carbon $start_date;

    public Carbon $due_date;

    public ?Carbon $completed_at;

    public array $settings;

    public ?string $id;

    public ?Tenant $tenant;

    public ?User $creator;

    public function hasParticipants(): bool
    {
        return ! empty($this->participants);
    }

    protected function rules(): array
    {

        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'status' => ['sometimes', Rule::enum(FlowStatus::class)],
            'order_column' => ['interger'],
            'start_date' => ['date', Rule::date()->todayOrAfter()],
            'due_date' => ['date', Rule::date()->afterOrEqual('start_date')],
            'completed_at' => ['date', Rule::date()->afterOrEqual('start_date')],
            'is_default' => 'boolean',
            'settings' => 'array',
            'participants' => 'array',
        ];
    }

    protected function defaults(): array
    {
        return [
            'is_default' => false,
            'status' => $this->evaluateDefaultStatus(),
            'template' => $this->getDefaultTemplate(),
        ];
    }

    protected function getDefaultTemplate(): FlowTemplateDto
    {
        return TemplateService::getDefault();
    }

    protected function evaluateDefaultStatus(): FlowStatus
    {
        return $this->start_date->isAfter(today()) ? FlowStatus::SCHEDULED : FlowStatus::ACTIVE;
    }

    protected function casts(): array
    {
        return [
            'status' => new EnumCast(FlowStatus::class),
            'start_date' => new CarbonCast(\config('app.timezone')),
            'due_date' => new CarbonCast(\config('app.timezone')),
            'completed_at' => new CarbonCast(\config('app.timezone')),
        ];
    }
}
