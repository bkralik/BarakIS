<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

//$configurator->setDebugMode('23.75.345.200'); // enable for your remote IP
$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

Instante\Bootstrap3Renderer\DI\RendererExtension::register($configurator);

// Source: http://www.radekdostal.cz/software/date-time-picker/
RadekDostal\NetteComponents\DateTimePicker\TbDatePicker::register();
RadekDostal\NetteComponents\DateTimePicker\TbDateTimePicker::register();

$container = $configurator->createContainer();

return $container;
