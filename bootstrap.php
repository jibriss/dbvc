<?php
namespace Jibriss\Dbvc;

define('DBVC_VERSION', 'beta');

require_once dirname(__FILE__) . '/vendor/autoload.php';

$application = new \Symfony\Component\Console\Application();
$application->add(new Command\InitCommand());
$application->add(new Command\StatusCommand());
$application->add(new Command\PatchMigrateCommand());
$application->add(new Command\PatchRollbackCommand());
$application->add(new Command\TagMigrateCommand());
$application->add(new Command\TagRollbackCommand());
$application->add(new Command\TagCreateCommand());
$application->add(new Command\UpdateCommand());
$application->run();
