<?php

declare(strict_types=1);

namespace App\Collections\Role;

use App\Contracts\Role\AssignableEntity;
use App\DTOs\Roles\SingleParticipantDto;
use App\Enums\Role\RoleEnum;
use Illuminate\Support\Collection;

final class ParticipantsCollection extends Collection
{
    /**
     * Convert the collection to a collection of Dto ready Array.
     *
     * @return self<array{participant_id: int, participant_name: string, participant_email: string, participant_avatar: string, role_id: int, role_name: string, role_label: string, role_description: string}>
     */
    public function asDtoArray(): self
    {
        return $this->map(function ($item) {
            return [
                'participant_id' => $item->model->getKey(),
                'participant_name' => $item->model->getAttribute('name'),
                'participant_email' => $item->model->getAttribute('email'),
                'participant_avatar' => $item->model->getAttribute('avatar'),
                'role_id' => $item->role->getKey(),
                'role_name' => $item->role->getAttribute('name'),
                'role_label' => RoleEnum::from($item->role->getAttribute('name'))->getLabel(),
                'role_description' => RoleEnum::from($item->role->getAttribute('name'))->getDescription(),

            ];
        });
    }

    /**
     * @return Collection<int|string, mixed>
     */
    public function avatars(): Collection
    {

        return $this->pluck('model.avatar');
    }

    /**
     * Convert the collection to a collection of SingleParticipantDto.
     *
     * @return self<SingleParticipantDto>
     */
    public function asDtoCollection(): self
    {
        return $this->asDtoArray()->mapInto(
            SingleParticipantDto::class,
        );
    }

    public function getParticipantIds(): Collection
    {
        return collect($this->pluck('model.id'));
    }

    public function getParticipantsAsSelectArray(): array
    {
        return $this->pluck('model')->mapWithKeys(fn ($model) => [$model->getKey() => $model->getAttribute('name')])->toArray();
    }

    public function exceptAssignable(AssignableEntity|string|array $userId): self
    {
        if ($userId instanceof AssignableEntity) {
            $userId = [$userId->getKey()];
        }

        if (is_string($userId)) {
            $userId = [$userId];
        }

        return $this->filter(function ($item) use ($userId) {
            return ! in_array($item->model->getKey(), $userId);

        });
    }
}
