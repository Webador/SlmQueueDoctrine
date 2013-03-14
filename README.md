SlmQueueDoctrine
================

Version 0.2.0 Created by Stefan Kleff

> SlmQueueDoctrine is currently untested, it may not work as expected.

Requirements
------------
* [Zend Framework 2](https://github.com/zendframework/zf2)
* [SlmQueue](https://github.com/juriansluiman/SlmQueue)
* [Doctrine 2 ORM Module](https://github.com/doctrine/DoctrineORMModule)


Installation
------------

First, install SlmQueue ([instructions here](https://github.com/juriansluiman/SlmQueue/blob/master/README.md)). Then,
add the following line into your `composer.json` file:

```json
"require": {
	"juriansluiman/slm-queue-doctrine": ">=0.2"
}
```

Then, enable the module by adding `SlmQueueDoctrine` in your application.config.php file. You may also want to
configure the module: just copy the `slm_queue_doctrine.local.php.dist` (you can find this file in the config
folder of SlmQueueDoctrine) into your config/autoload folder, and override what you want.

To be written:
* Configure Doctrine connection
* Import SQL from data/queue_default.sql


Documentation
-------------

Before reading SlmQueueDoctrine documentation, please read [SlmQueue documentation](https://github.com/juriansluiman/SlmQueue).

### Setting the connection parameters

Copy the `slm_queue_doctrine.local.php.dist` file to your `config/autoload` folder, and follow the instructions.

### Adding queues

```php
return array(
 'slm_queue' => array(
     'queues' => array(
         'factories' => array(
             'foo' => 'SlmQueueDoctrine\Factory\TableFactory'
         )
     )
 )
);
 ```

### Operations on queues

#### push

Valid options are:

* scheduled: the time when the job should run the next time OR
* delay: the delay in seconds before a job become available to be popped (default to 0 - no delay -)

Example:

```php
$queue->push($job, array(
    'delay' => 20
));
```

#### bury

Valid options are:

* message: Message why this has happened
* trace: Stack trace for further investigation

#### release

Valid options are:

* scheduled: the time when the job should run the next time OR
* delay: the delay in seconds before a job become available to be popped (default to 0 - no delay -)

#### purge

Valid options are:

* buried_lifetime
* deleted_lifetime

### Executing jobs

SlmQueueDoctrine provides a command-line tool that can be used to pop and execute jobs. You can type the following
command within the public folder of your Zend Framework 2 application:

`php index.php queue doctrine <queueName>  --start`
