<?php

namespace Lulco\PhpStormMetaGenerator\Database;

class Table
{
    private $name;

    private $fields;

    private $foreignKeys;

    public function __construct(string $name, array $fields, array $foreignKeys = [])
    {
        $this->name = $name;
        $this->fields = $fields;
        $this->foreignKeys = $foreignKeys;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return ForeignKey[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }
}
