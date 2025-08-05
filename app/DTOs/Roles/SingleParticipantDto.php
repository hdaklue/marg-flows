<?php

declare(strict_types=1);

namespace App\DTOs\Roles;

use WendellAdriel\ValidatedDTO\SimpleDTO;

final class SingleParticipantDto extends SimpleDTO
{
    public string $participant_id;

    public string $participant_name;

    public string $participant_email;

    public ?string $participant_avatar = null;

    public string $role_id;

    public string $role_name;

    public string $role_label;

    public string $role_description;

    public function participantName(): string
    {
        return $this->participant_name;
    }

    public function roleValue(): string
    {
        return $this->role_name;
    }

    public function roleLabel(): string
    {
        return $this->role_label;
    }

    public function roleDescription(): string
    {
        return $this->role_description;
    }

    public function participantAvatar(): ?string
    {
        return $this->participant_avatar;
    }

    public function participantId(): string
    {
        return $this->participant_id;
    }

    public function roleId(): string
    {
        return $this->role_id;
    }

    protected function rules(): array
    {
        return [
            'participant_id' => ['required', 'string'],
            'participant_name' => ['required', 'string'],
            'participant_email' => ['required', 'email'],
            'participant_avatar' => ['nullable', 'string'],
            'role_id' => ['required', 'string'],
            'role_name' => ['required', 'string'],
            'role_label' => ['required', 'string'],
            'role_description' => ['required', 'string'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'participant_avatar' => null,
        ];
    }

    protected function casts(): array
    {
        return [

        ];
    }
}
