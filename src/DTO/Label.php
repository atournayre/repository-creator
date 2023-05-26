<?php

namespace App\DTO;

class Label
{
    public string $name;
    public string $color;
    public ?string $description;

    private function __construct()
    {
    }

    public static function create(string $name, string $color, ?string $description = null): self
    {
        $label = new self();
        $label->name = $name;
        $label->color = $color;
        $label->description = $description;

        return $label;
    }
}
