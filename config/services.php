<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Http\Client\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpClient\HttplugClient;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services()->defaults()
                             ->autowire(true);

    $services->instanceof(Command::class)
             ->tag('app.command');

    $services->load('App\\', '../src/*');

    $services->set(ClientInterface::class, HttplugClient::class);

    $services->set(\App\App::class)
             ->public()
             ->args([tagged_iterator('app.command')]);
};
