<?php

declare(strict_types=1);

use HM\Container;
use HM\Migrator;

require_once 'vendor/autoload.php';

$container = new Container();
$migrator = $container->get(Migrator::class);
$migrator->migrate();
