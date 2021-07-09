<?php

require 'vendor/autoload.php';

@unlink('temp/database.sqlite');
@unlink('temp/succesfull');

// Bootstrap application, and initialize an empty database.
$serviceManager = require 'config/container.php';
$entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');
$connection = $entityManager->getConnection();
$connection->executeQuery(file_get_contents(__DIR__ . '/../lib/tests/Asset/queue_default.sqlite'));

// Populate with a job
$serviceManager->get(\SlmQueue\Queue\QueuePluginManager::class)->get('default')->push(new \App\TestJob());

// And now?
exec('vendor/bin/laminas slm-queue-doctrine:process default --start');

// Assert that file was generated?
if (@file_get_contents('temp/succesfull') !== 'YES') {
    echo 'Test was NOT successfull.';
    exit(1);
}

echo 'Test was successfull.';
exit(0);
