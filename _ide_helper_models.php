<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property string $name
 * @property string $id
 * @property string $title
 * @property int $status
 * @property int $is_default
 * @property int $order_column
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $canceled_at
 * @property array<array-key, mixed>|null $settings
 * @property mixed $blocks
 * @property string $tenant_id
 * @property string $creator_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read \App\Models\User $creator
 * @property-read string $progress_completed_date
 * @property-read string $progress_due_date
 * @property-read string $progress_start_date
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SideNote> $sideNotes
 * @property-read int|null $side_notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stage> $stages
 * @property-read int|null $stages_count
 * @property-read \App\Models\Tenant $tenant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow assignable()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow byStage(\App\Enums\FlowStatus|string $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow byStatus(\App\Enums\FlowStatus|string $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow byTenant(\App\Models\Tenant $tenant)
 * @method static \Database\Factories\FlowFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow forParticipant(\App\Contracts\Role\AssignableEntity $member)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow ordered(string $direction = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow running()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereBlocks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereCanceledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereOrderColumn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow withoutTrashed()
 */
	final class Flow extends \Eloquent implements \App\Contracts\Role\HasParticipants, \App\Contracts\Stage\HasStages, \App\Contracts\HasStaticType, \App\Contracts\Role\RoleableEntity, \App\Contracts\ScopedToTenant, \App\Contracts\Sidenoteable, \Spatie\EloquentSortable\Sortable, \App\Contracts\Progress\TimeProgressable, \App\Contracts\Tenant\BelongsToTenantContract {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $user_id
 * @property string $ip_address
 * @property string $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoginLog whereUserId($value)
 */
	class LoginLog extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $sender_id
 * @property string $receiver_id
 * @property array<array-key, mixed> $role_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $receiver
 * @property-read \App\Models\User $sender
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberInvitation sentBy(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberInvitation whereReceiverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberInvitation whereRoleData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberInvitation whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberInvitation whereUpdatedAt($value)
 */
	class MemberInvitation extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read Role|null $role
 * @property int $id
 * @property string $model_type
 * @property string $model_id
 * @property string $roleable_type
 * @property string $roleable_id
 * @property string $role_id
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $model
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $roleable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereRoleableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereRoleableType($value)
 */
	final class ModelHasRole extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $assignments
 * @property-read int|null $assignments_count
 * @property-read \App\Models\Tenant $tenant
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role byTenant(\App\Models\Tenant $tenant)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $content
 * @property string $sidenoteable_type
 * @property string $sidenoteable_id
 * @property string $owner_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $sidenoteable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SideNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SideNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SideNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SideNote whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SideNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SideNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SideNote whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SideNote whereSidenoteableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SideNote whereSidenoteableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SideNote whereUpdatedAt($value)
 */
	class SideNote extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property string $stageable_type
 * @property string $stageable_id
 * @property string $color
 * @property array<array-key, mixed>|null $settings
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $stageable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage by(\App\Contracts\Stage\HasStages $entity)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage whereStageableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage whereStageableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stage whereUpdatedAt($value)
 */
	class Stage extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property int $active
 * @property string $creator_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Flow> $flows
 * @property-read int|null $flows_count
 * @property-read \App\Models\TenantUser|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $systemRoles
 * @property-read int|null $system_roles_count
 * @method static \Database\Factories\TenantFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant forParticipant(\App\Contracts\Role\AssignableEntity $member)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereUpdatedAt($value)
 */
	final class Tenant extends \Eloquent implements \App\Contracts\HasStaticType, \App\Contracts\Role\RoleableEntity, \App\Contracts\Tenant\BelongsToTenantContract {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $user_id
 * @property string $tenant_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantUser whereUserId($value)
 */
	class TenantUser extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $account_type
 * @property string $password
 * @property string|null $timezone
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $active_tenant_id
 * @property \App\Enums\Account\AccountType $accout_type
 * @property-read mixed $avatar
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tenant> $createdTenants
 * @property-read int|null $created_tenants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Flow> $flows
 * @property-read int|null $flows_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MemberInvitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read mixed $inviter_name
 * @property-read \App\Models\LoginLog|null $latestLogin
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoginLog> $logins
 * @property-read int|null $logins_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\MemberInvitation|null $receivedInvitation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User appAdmin()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User assignedTo(\App\Contracts\Role\RoleableEntity $entity)
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User memberOf(\App\Models\Tenant $tenant)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User notAssignedTo(\App\Contracts\Role\RoleableEntity $entity)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User notMemberOf(\App\Models\Tenant $tenant)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereActiveTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	final class User extends \Eloquent implements \App\Contracts\Role\AssignableEntity, \Filament\Models\Contracts\FilamentUser, \Filament\Models\Contracts\HasAvatar, \Filament\Models\Contracts\HasDefaultTenant, \Filament\Models\Contracts\HasTenants, \App\Contracts\Tenant\HasActiveTenantContract {}
}

