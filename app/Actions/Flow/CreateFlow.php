<?php

declare(strict_types=1);

namespace App\Actions\Flow;

use App\DTOs\Flow\CreateFlowDto;
use App\Events\Flow\FlowCreated;
use App\Exceptions\Flow\FlowCreationException;
use App\Models\Flow;
use App\Models\Stage;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Hdaklue\Porter\RoleFactory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateFlow
{
    use AsAction;

    public function handle(CreateFlowDto $data, Tenant $tenant, User $creator)
    {

        try {
            return DB::transaction(function () use ($creator, $tenant, $data) {
                $flow = $this->makeFlow($data);
                $flow->tenant()->associate($tenant);
                $flow->creator()->associate($creator);

                $flow->save();

                $flow->assign($creator, RoleFactory::admin());

                FlowCreated::dispatch($flow, $creator);

                return $flow;
            });

        } catch (QueryException $e) {
            Log::error('Database error creating flow', [
                'tenant_id' => $tenant->getKey(),
                'creator_id' => $creator->id,
                'error' => $e->getMessage(),
            ]);

            throw new FlowCreationException('Failed to create flow due to database constraints');
        } catch (Exception $e) {
            Log::error('Unexpected error creating flow', [
                'tenant_id' => $tenant->getKey(),
                'creator_id' => $creator->id,
                'error' => $e->getMessage(),
            ]);
            throw new FlowCreationException('An unexpected error occurred while creating the flow');
        }
    }

    // protected function attachFlowStages(Flow $flow, CreateFlowDto $data, Tenant $tenant)
    // {

    //     $stages = $data->template->stages->map(function ($stage, $tenant) {
    //         $stage = new Stage([
    //             'name' => $stage->name,
    //             'color' => $stage->color,
    //             'order' => $stage->order,
    //             'settings' => $stage->settings,
    //         ]);

    //         return $stage;
    //     });

    //     $flow->stages()->createMany($stages->toArray());
    // }

    protected function makeFlow(CreateFlowDto $data): Flow
    {

        return new Flow([
            'title' => $data->title,
            'description' => $data->description,
            'started_at' => now(),
            'stage' => $data->stage->value,
        ]);
    }
}
