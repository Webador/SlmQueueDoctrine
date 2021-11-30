<?php

require 'vendor/autoload.php';

@unlink('temp/database.sqlite');
@unlink('temp/succesfull');

// Bootstrap application, and initialize an empty database.
$application = Laminas\Mvc\Application::init(include 'config/application.config.php');
$serviceManager = $application->getServiceManager();
$entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');
$connection = $entityManager->getConnection();
$connection->executeQuery(file_get_contents(__DIR__ . '/../queue_default.sqlite'));

// Populate with a job
$application->getServiceManager()->get(\SlmQueue\Queue\QueuePluginManager::class)->get('default')->push(new \TestModule\TestJob());

// Run the queue for a single job
exec('vendor/bin/laminas slm-queue:start default', $output, $result);

// Assert that file was generated?
if (@file_get_contents('temp/succesful') !== 'YES') {
    echo 'Test was NOT successful.';
    exit(1);
}

echo 'Test was successful.';
exit(0);
