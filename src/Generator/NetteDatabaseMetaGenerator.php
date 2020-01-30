<?php

namespace Lulco\PhpStormMetaGenerator\Generator;

use Lulco\PhpStormMetaGenerator\Database\Structure;

class NetteDatabaseMetaGenerator
{
    private const PHPSTORM_META_NAMESPACE = 'PHPSTORM_META';

    public function generate(Structure $structure): string
    {
        $classes = [];
        $tables = [];
        $overrides = [];
        foreach ($structure->getTables() as $table) {
            $tables[$table->getName()] = $table;
            $className = $this->createClassName($table->getName());

            $classDeclarationRows = [
                "class $className extends ActiveRow",
                '{',
            ];

            $fields = [];
            $nullableFields = [];
            foreach ($table->getFields() as $field) {
                $type = $field->getType();
                if ($field->isNullable()) {
                    $nullableFields[] = $field->getName();
                    $type .= '|null';
                }
                $fields[$field->getName()] = [
                    'name' => $field->getName(),
                    'type' => $type,
                ];
            }

            foreach ($table->getForeignKeys() as $foreignKey) {
                $type = $this->createClassName($foreignKey->getTable());
                if (in_array($foreignKey->getField(), $nullableFields)) {
                    $type .= '|null';
                }
                $name = $this->createForeignKeyFieldName($foreignKey->getField());
                $fields[$name] = [
                    'name' => $name,
                    'type' => $type,
                ];
            }
            ksort($fields);

            $i = 1;
            foreach ($fields as $field) {
                $classDeclarationRows[] = '    /** @var ' . $field['type'] . ' */';
                $classDeclarationRows[] = '    public $' . $field['name'] . ';';
                if ($i++ < count($fields)) {
                    $classDeclarationRows[] = '';
                }
            }

            $classDeclarationRows[] = '}';
            $classes[$className] = implode("\n", array_map(function ($row) {
                return $row ? "    $row" : '';
            }, $classDeclarationRows));

            $selectionClassName = $this->createClassName($table->getName(), 'Selection');
            $selection = "    class $selectionClassName extends Selection\n";
            $selection .= "    {\n";
            $selection .= "        /** @return $className */\n";
            $selection .= "        public function current()\n";
            $selection .= "        {\n";
            $selection .= "            return parent::current();\n";
            $selection .= "        }\n\n";
            $selection .= "        /** @return $className */\n";
            $selection .= "        public function fetch()\n";
            $selection .= "        {\n";
            $selection .= "            return parent::fetch();\n";
            $selection .= "        }\n";
            $selection .= "    }";
            $classes[$selectionClassName] = $selection;

            $overrides[$table->getName()] = $selectionClassName;
        }

        $uses = [
            'Nette\Database\Context',
            'Nette\Database\Table\ActiveRow',
            'Nette\Database\Table\Selection',
            'Nette\Utils\DateTime',
        ];

        sort($uses);

        $output = "<?php\n\nnamespace " . self::PHPSTORM_META_NAMESPACE . "\n{\n";
        $output .= implode("\n", array_map(function ($use) {
            return '    use ' . $use . ';';
        }, $uses));
        $output .= "\n\n";

        ksort($classes);
        $output .= implode("\n\n", $classes);
        $output .= "\n\n";

        $output .= "    override(Context::table(),\n";
        $output .= "        map([\n";
        ksort($overrides);
        foreach ($overrides as $table => $selectionClassName) {
            $output .= "            '$table' => '\\" . self::PHPSTORM_META_NAMESPACE . "\\$selectionClassName',\n";
        }
        $output .= "        ])\n";
        $output .= "    );\n";
        $output .= "}\n";

        return $output;
    }

    private function createClassName(string $tableName, string $type = 'ActiveRow'): string
    {
        $singular = $tableName;
        $last3Letters = substr($tableName, -3);
        $last1Letter = substr($tableName, -1);
        if ($last3Letters === 'ies') {
            $singular = substr($tableName, 0, -3) . 'y';
        } elseif ($last3Letters === 'ses') {
            $singular = substr($tableName, 0, -2);
        } elseif ($last1Letter === 's') {
            $singular = substr($tableName, 0, -1);
        }
        return $this->snakeCaseToCamelCase($singular) . $type;
    }

    private function createForeignKeyFieldName(string $foreignKeyField): string
    {
        $foreignKeyFieldParts = explode('_', $foreignKeyField);
        array_pop($foreignKeyFieldParts);
        return implode('_', $foreignKeyFieldParts);
    }

    private function snakeCaseToCamelCase(string $fieldName): string
    {
        $methodFieldName = '';
        for ($i = 0; $i < strlen($fieldName); $i++) {
            if ($fieldName[$i] === '_') {
                continue;
            }
            $methodFieldName .= isset($fieldName[$i-1]) && $fieldName[$i-1] === '_' ? strtoupper($fieldName[$i]) : $fieldName[$i];
        }
        return ucfirst($methodFieldName);
    }
}
