<?php
namespace Jibriss\Dbvc;

require_once 'vendor/autoload.php';
$config = require_once 'config.php';

$dbal = \Doctrine\DBAL\DriverManager::getConnection($config['db']);

$db = new Db($dbal);
$db->createMigrationsTableIfNotExists();

$dbvc = new Dbvc(new File($config['patches_directory'], $config['tags_directory']), $db);

$application = new \Symfony\Component\Console\Application();
$application->add(new Command\StatusCommand($dbvc));
$application->add(new Command\PatchMigrateCommand($dbvc));
$application->add(new Command\PatchRollbackCommand($dbvc));
$application->add(new Command\TagMigrateCommand($dbvc));
$application->add(new Command\TagRollbackCommand($dbvc));
$application->add(new Command\TagCreateCommand($dbvc));
$application->add(new Command\UpdateCommand($dbvc));
$application->run();
