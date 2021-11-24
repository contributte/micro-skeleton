<?php declare(strict_types = 1);

// Load Nette Framework
if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install skeleton using `composer install`');
}

// Configure application
$configurator = new Nette\Configurator;

// Enable Tracy for error visualisation & logging
$configurator->enableTracy(__DIR__ . '/../var/log');

// Create Dependency Injection container
$configurator->setTempDirectory(__DIR__ . '/../var/tmp');
$configurator->addConfig(__DIR__ . '/../config/config.neon');
$container = $configurator->createContainer();

// Run the application!
$container->getByType(Nette\Application\Application::class)->run();
