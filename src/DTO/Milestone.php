<?php

namespace App\DTO;

class Milestone
{
    private function __construct(
        public string $title,
        public ?string $description,
        public ?string $dueOn,
    )
    {
    }

    public static function create(string $title, ?string $description, ?string $dueOn): self
    {
        return new self($title, $description, $dueOn);
    }

    public function toArray(): array
    {
        $milestone = [
            'title' => $this->title,
            'description' => $this->description,
        ];

        if (null !== $this->dueOn) {
            $milestone['due_on'] = $this->dueOn;
        }
        return $milestone;
    }
}
