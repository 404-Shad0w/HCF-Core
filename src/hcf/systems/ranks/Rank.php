<?php

namespace hcf\systems\ranks;

class Rank
{
    private string $name;
    private string $prefix;
    private array $permissions;

    public function __construct(string $name, string $prefix, array $permissions = [])
    {
        $this->name = $name;
        $this->prefix = $prefix;
        $this->permissions = $permissions;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function addPermission(string $permission): void
    {
        if (!in_array($permission, $this->permissions)) {
            $this->permissions[] = $permission;
        }
    }

    public function removePermission(string $permission): void
    {
        $key = array_search($permission, $this->permissions);
        if ($key !== false) {
            unset($this->permissions[$key]);
            $this->permissions = array_values($this->permissions); // Reindex array
        }
    }
}