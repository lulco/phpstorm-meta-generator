<?php

namespace Lulco\PhpStormMetaGenerator\Tests;

use Lulco\PhpStormMetaGenerator\Database\Field;
use Lulco\PhpStormMetaGenerator\Database\ForeignKey;
use Lulco\PhpStormMetaGenerator\Database\Structure;
use Lulco\PhpStormMetaGenerator\Database\Table;
use Lulco\PhpStormMetaGenerator\Generator\NetteDatabaseMetaGenerator;
use PHPUnit\Framework\TestCase;

class StructureTest extends TestCase
{
    public function testSimpleOutput()
    {
        $bonuses = new Table('bonuses', [new Field('id', 'int'), new Field('title', 'string'), new Field('show_id', 'int'), new Field('season_id', 'int', true)], [new ForeignKey('show_id', 'shows'), new ForeignKey('season_id', 'seasons')]);
        $shows = new Table('shows', [new Field('id', 'int'), new Field('title', 'string')]);
        $seasons = new Table('seasons', [new Field('id', 'int'), new Field('title', 'string'), new Field('show_id', 'int')], [new ForeignKey('show_id', 'shows')]);

        $tables = [];
        $tables[] = $bonuses;
        $tables[] = $shows;
        $tables[] = $seasons;

        $structure = new Structure($tables);
        $metaGenerator = new NetteDatabaseMetaGenerator();
        $output = $metaGenerator->generate($structure);

        $this->assertEquals(file_get_contents(__DIR__ . '/Fixtures/structure.1'), $output);
    }
}
