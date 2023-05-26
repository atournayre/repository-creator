<?php

namespace App\DTO;

class Branch
{
    public string $name;
    public bool $isDefault;

    private function __construct()
    {
    }

    public static function create(string $name, bool $isDefault = false): self
    {
        $branch = new self();
        $branch->name = $name;
        $branch->isDefault = $isDefault;

        return $branch;
    }
}
