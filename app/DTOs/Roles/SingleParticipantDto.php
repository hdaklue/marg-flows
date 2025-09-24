<?php

declare(strict_types=1);

namespace App\DTOs\Roles;

use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class SingleParticipantDto extends ValidatedDTO
{
    public string $avatarUrl;

    public string $username;

    public string $id;

    public string $email;

    public string $name;

    public string $timezone;

    public array $role;

    public function name(): string
    {
        return $this->name;
    }

    public function roleKey(): string
    {
        return $this->role_name;
    }

    public function roleLabel(): string
    {
        return $this->role->getName();
    }

    public function roleDescription(): string
    {
        return $this->role->getDescription();
    }

    public function participantAvatar(): null|string
    {
        return $this->avatarUrl;
    }

    public function participantId(): string
    {
        return $this->id;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'avatarUrl' => ['required', 'string'],
            'email' => ['required'],
            'username' => ['required', 'string'],
            'id' => ['required', 'string'],
            'timezone' => ['sometimes'],
            'role' => ['sometimes'],
        ];
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function casts(): array
    {
        return [];
    }
}
