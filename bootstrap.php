<?php
namespace Jibriss\Dbvc;

require_once dirname(__FILE__) . '/vendor/autoload.php';

$application = new \Symfony\Component\Console\Application();
$application->setVersion('beta');
$application->setName('DBVC');
$application->add(new Command\InitCommand());
$application->add(new Command\StatusCommand());
$application->add(new Command\PatchCreateCommand());
$application->add(new Command\PatchMigrateCommand());
$application->add(new Command\PatchRollbackCommand());
$application->add(new Command\TagMigrateCommand());
$application->add(new Command\TagRollbackCommand());
$application->add(new Command\TagCreateCommand());
$application->add(new Command\UpdateCommand());
$application->run();
