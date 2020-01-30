<?php

namespace Lulco\PhpStormMetaGenerator\Database;

class ForeignKey
{
    private $field;

    private $table;

    public function __construct(string $field, string $table)
    {
        $this->field = $field;
        $this->table = $table;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getTable(): string
    {
        return $this->table;
    }
}
