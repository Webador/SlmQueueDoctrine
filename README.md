GoalioQueueDoctrine
===================

Version 0.2.0 Created by Stefan Kleff

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
	"goalio/goalio-queue-doctrine": ">=0.2"
}
```

Then, enable the module by adding `GoalioQueueDoctrine` in your application.config.php file. You may also want to
configure the module: just copy the `goalio_queue_doctrine.local.php.dist` (you can find this file in the config
folder of GoalioQueueDoctrine) into your config/autoload folder, and override what you want.

To be written:
* Configure Doctrine connection
* Import SQL from data/queue_jobs.sql (rename table?)


Documentation
-------------
Before reading GoalioQueue documentation, please read [SlmQueue documentation](https://github.com/juriansluiman/SlmQueue).


### Setting the connection parameters

Copy the `goalio_queue_doctrine.local.php.dist` file to your `config/autoload` folder, and follow the instructions.


### Adding queues
```php
return array(
 'slm_queue' => array(
     'queues' => array(
         'factories' => array(
             'foo' => 'GoalioQueueDoctrine\Factory\TableFactory'
         )
     )
 )
);
 ```

### Executing jobs

GoalioQueueDoctrine provides a command-line tool that can be used to pop and execute jobs. You can type the following
command within the public folder of your Zend Framework 2 application:

`php index.php queue doctrine <queueName>  --start`