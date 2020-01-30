<?php

namespace Lulco\PhpStormMetaGenerator\Database;

use Lulco\PhpStormMetaGenerator\Generator\NetteDatabaseMetaGenerator;
use Nette\Database\Context;
use Nette\Database\IStructure;
use Nette\DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NetteDatabaseCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('nette:database')
            ->addArgument('app-root', InputArgument::REQUIRED, 'Application root path')
            ->addOption('bootstrap', null, InputOption::VALUE_REQUIRED, 'Path to bootstrap', 'app/bootstrap.php')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var string $appRootValue */
        $appRootValue = $input->getArgument('app-root');
        $appRoot = rtrim($appRootValue, '/') . '/';
        /** @var string $bootstrapPath */
        $bootstrapPath = $input->getOption('bootstrap');

        /** @var Container $container */
        $container = require $appRoot . $bootstrapPath;

        /** @var Context $dbContext */
        $dbContext = $container->getService('database.default.context');

        /** @var IStructure $dbStructure */
        $dbStructure = $dbContext->getStructure();

        $tables = [];
        foreach ($dbStructure->getTables() as $table) {
            $fields = [];
            foreach ($dbStructure->getColumns($table['name']) as $field) {
                $type = 'string';   // vacsina typov je string
                if (strpos($field['nativetype'], 'INT') !== false) {
                    $type = 'int';
                } elseif ($field['nativetype'] === 'DATETIME' || $field['nativetype'] === 'TIMESTAMP') {
                    $type = 'DateTime';
                } elseif ($field['nativetype'] === 'BOOL' || ($field['nativetype'] === 'TINYINT' && $field['size'] === 1)) {
                    $type = 'bool';
                }
                // TODO remap all native types to php types
                $nullable = (isset($field['vendor']['null']) && $field['vendor']['null'] === 'YES') || (isset($field['vendor']['nullable']) && $field['vendor']['nullable'] === true);
                $fields[] = new Field($field['name'], $type, $nullable);
            }

            $foreignKeys = [];
            $belongsToReference = $dbStructure->getBelongsToReference($table['name']) ?: [];
            foreach ($belongsToReference as $fieldName => $tableName) {
                $tableName = str_replace('public.', '', $tableName);    // fix nazvu tabulky pre pgsql
                $foreignKeys[] = new ForeignKey($fieldName, $tableName);
            }

            $tables[] = new Table($table['name'], $fields, $foreignKeys);
        }

        $structure = new Structure($tables);
        $metaGenerator = new NetteDatabaseMetaGenerator();
        $phpStormMetaOutput = $metaGenerator->generate($structure);

        mkdir($appRoot . '/.phpstorm.meta.php');
        $result = file_put_contents($appRoot . '/.phpstorm.meta.php/nette-database.meta.php', $phpStormMetaOutput);

        $output->writeln('');
        if ($result === false) {
            $lastError = error_get_last();
            $output->writeln('<error>' . ($lastError['message'] ?? 'Unknown error') . '</error>');
        } else {
            $output->write('<info>ALL DONE</info>');
        }
        $output->writeln("\n");

        return $result === false ? 1 : 0;
    }
}
