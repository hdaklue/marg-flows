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
 * @property string $id
 * @property string $title
 * @property int $status
 * @property int $is_default
 * @property int $order_column
 * @property Carbon|null $start_date
 * @property Carbon|null $due_date
 * @property Carbon|null $completed_at
 * @property Carbon|null $canceled_at
 * @property array<array-key, mixed>|null $settings
 * @property mixed $blocks
 * @property string $tenant_id
 * @property string $creator_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read User $creator
 * @property-read string $progress_completed_date
 * @property-read string $progress_due_date
 * @property-read string $progress_start_date
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ModelHasRole> $participants
 * @property-read int|null $participants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SideNote> $sideNotes
 * @property-read int|null $side_notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Stage> $stages
 * @property-read int|null $stages_count
 * @property-read Tenant $tenant
 * @method static Builder<static>|Flow assignable()
 * @method static Builder<static>|Flow byStage(\App\Enums\FlowStatus|string $status)
 * @method static Builder<static>|Flow byStatus(\App\Enums\FlowStatus|string $status)
 * @method static Builder<static>|Flow byTenant(\App\Models\Tenant $tenant)
 * @method static \Database\Factories\FlowFactory factory($count = null, $state = [])
 * @method static Builder<static>|Flow forParticipant(\App\Contracts\Role\AssignableEntity $member)
 * @method static Builder<static>|Flow newModelQuery()
 * @method static Builder<static>|Flow newQuery()
 * @method static Builder<static>|Flow onlyTrashed()
 * @method static Builder<static>|Flow ordered(string $direction = 'asc')
 * @method static Builder<static>|Flow query()
 * @method static Builder<static>|Flow running()
 * @method static Builder<static>|Flow whereBlocks($value)
 * @method static Builder<static>|Flow whereCanceledAt($value)
 * @method static Builder<static>|Flow whereCompletedAt($value)
 * @method static Builder<static>|Flow whereCreatedAt($value)
 * @method static Builder<static>|Flow whereCreatorId($value)
 * @method static Builder<static>|Flow whereDeletedAt($value)
 * @method static Builder<static>|Flow whereDueDate($value)
 * @method static Builder<static>|Flow whereId($value)
 * @method static Builder<static>|Flow whereIsDefault($value)
 * @method static Builder<static>|Flow whereOrderColumn($value)
 * @method static Builder<static>|Flow whereSettings($value)
 * @method static Builder<static>|Flow whereStartDate($value)
 * @method static Builder<static>|Flow whereStatus($value)
 * @method static Builder<static>|Flow whereTenantId($value)
 * @method static Builder<static>|Flow whereTitle($value)
 * @method static Builder<static>|Flow whereUpdatedAt($value)
 * @method static Builder<static>|Flow withTrashed()
 * @method static Builder<static>|Flow withoutTrashed()
 * @property string|null $description
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Flow whereDescription($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	final class IdeHelperFlow {}
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperLoginLog {}
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
 * @method static Builder<static>|MemberInvitation newModelQuery()
 * @method static Builder<static>|MemberInvitation newQuery()
 * @method static Builder<static>|MemberInvitation query()
 * @method static Builder<static>|MemberInvitation sentBy(\App\Models\User $user)
 * @method static Builder<static>|MemberInvitation whereCreatedAt($value)
 * @method static Builder<static>|MemberInvitation whereId($value)
 * @method static Builder<static>|MemberInvitation whereReceiverId($value)
 * @method static Builder<static>|MemberInvitation whereRoleData($value)
 * @method static Builder<static>|MemberInvitation whereSenderId($value)
 * @method static Builder<static>|MemberInvitation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperMemberInvitation {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $model_type
 * @property string $model_id
 * @property string $roleable_type
 * @property string $roleable_id
 * @property string $role_id
 * @property-read Model|Eloquent $model
 * @property-read Role $role
 * @property-read Model|Eloquent $roleable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereRoleableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelHasRole whereRoleableType($value)
 * @mixin Eloquent
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	final class IdeHelperModelHasRole {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, \App\Models\ModelHasRole> $assignments
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	final class IdeHelperRole {}
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
 * @property-read Model|\Eloquent $sidenoteable
 * @method static Builder<static>|SideNote newModelQuery()
 * @method static Builder<static>|SideNote newQuery()
 * @method static Builder<static>|SideNote query()
 * @method static Builder<static>|SideNote whereContent($value)
 * @method static Builder<static>|SideNote whereCreatedAt($value)
 * @method static Builder<static>|SideNote whereId($value)
 * @method static Builder<static>|SideNote whereOwnerId($value)
 * @method static Builder<static>|SideNote whereSidenoteableId($value)
 * @method static Builder<static>|SideNote whereSidenoteableType($value)
 * @method static Builder<static>|SideNote whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperSideNote {}
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
 * @property-read Model|\Eloquent $stageable
 * @method static Builder<static>|Stage by(\App\Contracts\Stage\HasStages $entity)
 * @method static Builder<static>|Stage newModelQuery()
 * @method static Builder<static>|Stage newQuery()
 * @method static Builder<static>|Stage query()
 * @method static Builder<static>|Stage whereColor($value)
 * @method static Builder<static>|Stage whereCreatedAt($value)
 * @method static Builder<static>|Stage whereId($value)
 * @method static Builder<static>|Stage whereName($value)
 * @method static Builder<static>|Stage whereOrder($value)
 * @method static Builder<static>|Stage whereSettings($value)
 * @method static Builder<static>|Stage whereStageableId($value)
 * @method static Builder<static>|Stage whereStageableType($value)
 * @method static Builder<static>|Stage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperStage {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property int $active
 * @property string $creator_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $assignedRoles
 * @property-read int|null $assigned_roles_count
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Flow> $flows
 * @property-read int|null $flows_count
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
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	final class IdeHelperTenant {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $account_type
 * @property string $password
 * @property string|null $timezone
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $active_tenant_id
 * @property AccountType $accout_type
 * @property-read mixed $avatar
 * @property-read Collection<int, \App\Models\Tenant> $createdTenants
 * @property-read int|null $created_tenants_count
 * @property-read Collection<int, \App\Models\Flow> $flows
 * @property-read int|null $flows_count
 * @property-read Collection<int, \App\Models\MemberInvitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read mixed $inviter_name
 * @property-read \App\Models\LoginLog|null $latestLogin
 * @property-read Collection<int, \App\Models\LoginLog> $logins
 * @property-read int|null $logins_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\MemberInvitation|null $receivedInvitation
 * @property-read Collection<int, \App\Models\ModelHasRole> $roleAssignments
 * @property-read int|null $role_assignments_count
 * @method static Builder<static>|User appAdmin()
 * @method static Builder<static>|User assignedTo(\App\Contracts\Role\RoleableEntity $entity)
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User memberOf(\App\Models\Tenant $tenant)
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User notAssignedTo(\App\Contracts\Role\RoleableEntity $entity)
 * @method static Builder<static>|User notMemberOf(\App\Models\Tenant $tenant)
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User whereAccountType($value)
 * @method static Builder<static>|User whereActiveTenantId($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereTimezone($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	final class IdeHelperUser {}
}

