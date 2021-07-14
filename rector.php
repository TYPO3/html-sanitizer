<?php

declare(strict_types=1);

use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    // Define what rule sets will be applied
    $containerConfigurator->import(\Rector\Set\ValueObject\DowngradeSetList::PHP_71);
    $containerConfigurator->import(\Rector\Set\ValueObject\DowngradeSetList::PHP_70);

    $parameters->set(\Rector\Core\Configuration\Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ]);

    // get services (needed for register a single rule)
    // $services = $containerConfigurator->services();

    
    // register a single rule
    // $services->set(...);
};
