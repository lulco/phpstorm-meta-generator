<?php

namespace Lulco\PhpStormMetaGenerator\Database;

class Field
{
    private $name;

    private $type;

    private $nullable;

    public function __construct(string $name, string $type, bool $nullable = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }
}
