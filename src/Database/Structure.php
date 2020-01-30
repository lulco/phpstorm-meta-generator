<?php

namespace Lulco\PhpStormMetaGenerator\Database;

class Structure
{
    private $tables = [];

    /**
     * @param Table[] $tables
     */
    public function __construct(array $tables)
    {
        $this->tables = $tables;
    }

    /**
     * @return Table[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }
}
