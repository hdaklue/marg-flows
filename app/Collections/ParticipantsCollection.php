<?php

declare(strict_types=1);

namespace App\Collections;

use App\DTOs\Roles\SingleParticipantDto;
use Hdaklue\Porter\Contracts\AssignableEntity;
use Illuminate\Support\Collection;

final class ParticipantsCollection extends Collection
{
    /**
     * Convert the collection to a collection of Dto ready Array.
     */
    public function toDto(): self
    {
        dd($this);

        return $this->map(fn($item) => [
            'userDto' => $item->assignable,
            'role' => $item->role_key,
        ]);
    }

    /**
     * @return Collection<int|string, mixed>
     */
    public function avatars(): Collection
    {
        return $this->pluck('assignable')->map(fn($item) => $item->getAvatarUrl());
    }

    /**
     * Convert the collection to a collection of SingleParticipantDto.
     *
     * @return self<SingleParticipantDto>
     */
    public function asDtoCollection(): self
    {
        return $this->map(fn($item) => SingleParticipantDto::fromArray([
            'avatarUrl' => $item->assignable->getAvatarUrl(),
            'username' => $item->assignable->getAttribute('username'),
            'id' => $item->assignable_id,
            'email' => $item->assignable->getAttribute('email'),
            'name' => $item->assignable->getAttribute('name'),
            'timezone' => $item->assignable->getTimezone(),
            'role' => $item->role_key,
        ]));

        // return $this->toDto()->mapInto(SingleParticipantDto::class);
    }

    public function getParticipantIds(): Collection
    {
        return collect($this->pluck('assignable.id'));
    }

    public function getParticipantsAsSelectArray(): array
    {
        return $this->pluck('assignable')
            ->mapWithKeys(fn($model) => [
                $model->getKey() => $model->getAttribute('name'),
            ])
            ->toArray();
    }

    public function exceptAssignable(AssignableEntity $user): self
    {
        return $this->reject(fn($item): bool => $item->model->getKey() === $user->getKey());
    }
}
