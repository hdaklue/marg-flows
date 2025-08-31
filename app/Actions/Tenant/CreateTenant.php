<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Events\Tenant\TenantCreated;
use App\Models\User;
use Exception;
use Hdaklue\MargRbac\Actions\Tenant\CreateTenant as PackageCreateTenant;
use Hdaklue\MargRbac\DTOs\Tenant\CreateTenantDto;
use Hdaklue\MargRbac\DTOs\Tenant\TenantMemberDto;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateTenant
{
    use AsAction;

    public function handle(array $data, User $user): void
    {
        try {
            // Transform array data to DTO format for package action
            $members = [];
            if (isset($data['members']) && is_array($data['members'])) {
                $members = array_map(function ($member) {
                    return TenantMemberDto::from([
                        'user_id' => $member['name'], // Assuming 'name' is actually user_id
                        'role' => $member['role'],
                    ]);
                }, $data['members']);
            }

            $dto = CreateTenantDto::from([
                'name' => $data['name'],
                'creator_id' => $user->id,
                'members' => $members,
            ]);

            // Call the package action to handle the core functionality
            $result = PackageCreateTenant::run($dto);
            
            // Get the created tenant and participants for app-specific logic
            $tenant = $result['tenant'] ?? null;
            $participants = $result['participants'] ?? collect();

            // Fire app-specific event
            if ($tenant && $participants->isNotEmpty()) {
                TenantCreated::dispatch($tenant, $participants, $user);
            }

        } catch (Exception $e) {
            Log::error('Tenant creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw $e; // Re-throw to let caller handle
        }
    }

}
