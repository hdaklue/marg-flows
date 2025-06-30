<?php

declare(strict_types=1);

namespace App\Actions\Flow;

use App\Actions\Roleable\AddParticipant;
use App\Enums\FlowStatus;
use App\Enums\Role\RoleEnum;
use App\Models\Flow;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

// TODO:Move to top order after create
class CreateFlow
{
    use AsAction;

    public function handle(array $data, Tenant $tenant, User $creator)
    {
        $flow = Flow::make([
            'title' => $data['title'],
            'start_date' => $data['start_date'],
            'due_date' => $data['due_date'],
            'status' => $data['status'] ?? $this->getStatus(Carbon::parse($data['start_date'])),
        ]);
        $flow->tenant()->associate($tenant);
        $flow->creator()->associate($creator);
        $flow->save();
        $flow->addParticipant($creator, RoleEnum::ADMIN->value, true);

        if ($data['participants']) {
            User::whereIn('id', $data['participants'])
                ->get()->each(function ($user) use ($flow, $tenant) {
                    $role = RoleEnum::from($user->rolesOn($tenant)->first()->name);
                    $this->addParticipants($flow, $user, $role);
                });

        }

    }

    protected function getStatus(Carbon $date)
    {
        return $date->isAfter(today()) ? FlowStatus::SCHEDULED->value : FlowStatus::ACTIVE->value;
    }

    protected function addParticipants(Flow $flow, User $participant, RoleEnum $role)
    {
        AddParticipant::dispatch($flow, $participant, $role, $flow->tenant);
    }
}
