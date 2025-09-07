<?php

declare(strict_types=1);

namespace App\DTOs;

use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class MentionableDTO extends ValidatedDTO
{
    public string $id;
    public string $name;
    public string $email;
    public null|string $avatar;
    public null|string $title;
    public null|string $department;

    protected function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'avatar' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function defaults(): array
    {
        return [
            'avatar' => null,
            'title' => null,
            'department' => null,
        ];
    }

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'email' => 'string',
            'avatar' => 'string',
            'title' => 'string',
            'department' => 'string',
        ];
    }

    public function toTributeFormat(): array
    {
        return [
            'key' => $this->id,
            'value' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'title' => $this->title,
            'department' => $this->department,
        ];
    }

    public function getDisplayName(): string
    {
        return $this->title ? "{$this->name} ({$this->title})" : $this->name;
    }
}
