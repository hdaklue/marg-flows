<?php

declare(strict_types=1);

namespace App\DTOs\Roles;

use App\Collections\Role\ParticipantsCollection;
use App\DTOs\ItemableDto;

final class ParticipantsDto extends ItemableDto
{
    public static function fromParticipantsCollection(ParticipantsCollection $data): self
    {
        return new self([
            'items' => $data->toDto(),
        ]);
    }
    // public static function fromParticipantsCollection(ParticipantsCollection $data): self
    // {
    //     return new self([
    //         'items' => $data->map(fn ($item) => new ParticipantsDto($item)),
    //     ]);
    // }

    protected function itemDTOClass(): string
    {
        return SingleParticipantDto::class;
    }
}
