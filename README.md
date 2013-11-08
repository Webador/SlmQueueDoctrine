SlmQueueDoctrine
================

[![Latest Stable Version](https://poser.pugx.org/slm/queue-doctrine/v/stable.png)](https://packagist.org/packages/slm/queue-doctrine)
[![Latest Unstable Version](https://poser.pugx.org/slm/queue-doctrine/v/unstable.png)](https://packagist.org/packages/slm/queue-doctrine)

Created by Stefan Kleff

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
	"slm/queue-doctrine": "dev-master"
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
    'queue_manager' => array(
      'factories' => array(
        'foo' => 'SlmQueueDoctrine\Factory\DoctrineQueueFactory'
      )
    )
  )
);
```
### Adding jobs

```php
return array(
  'slm_queue' => array(
    'job_manager' => array(
      'factories' => array(
        'My\Job' => 'My\JobFactory'
      )
    )
  )
);

``` 
### Configuring queues

These options are set occross all queues

- delete_lifetime (defaults to 0) : How long to keep deleted (successful) jobs (in minutes)
- buried_lifetime (defaults to 0) : How long to keep buried (failed) jobs (in minutes)

The following options can be set per queue ;
	
- sleep_when_idle (defaults to 1) : How long show we sleep when no jobs available for processing (in seconds)


```php
return array(
 'slm_queue' => array(
     'queues' => array(
         'foo' => array(
             'sleep_when_idle' => 1
         )
     )
 )
);
 ```


### Operations on queues

#### push

Valid options are:

* scheduled: the time when the job will be scheduled to run next
	* numeric string or integer - interpreted as a timestamp
	* string parserable by the DateTime object
	* DateTime instance
* delay: the delay before a job become available to be popped (defaults to 0 - no delay -)
	* numeric string or integer - interpreted as seconds
	* string parserable (ISO 8601 duration) by DateTimeInterval::__construct
	* string parserable (relative parts) by DateTimeInterval::createFromDateString
	* DateTimeInterval instance

Examples:

	// scheduled for execution asap
    $queue->push($job);

	// scheduled for execution 2015-01-01 00:00:00 (system timezone applies)
    $queue->push($job, array(
        'scheduled' => 1420070400
    ));

    // scheduled for execution 2015-01-01 00:00:00 (system timezone applies)
    $queue->push($job, array(
        'scheduled' => '2015-01-01 00:00:00'
    ));

    // scheduled for execution at 2015-01-01 01:00:00
    $queue->push($job, array(
        'scheduled' => '2015-01-01 00:00:00',
        'delay' => 3600
    ));  

    // scheduled for execution at now + 300 seconds
    $queue->push($job, array(
        'delay' => 'PT300S'
    ));

    // scheduled for execution at now + 2 weeks (1209600 seconds)
    $queue->push($job, array(
        'delay' => '2 weeks'
    ));

    // scheduled for execution at now + 300 seconds
    $queue->push($job, array(
        'delay' => new DateInterval("PT200S"))
    ));


### Worker actions

Interact with workers from the command line from within the public folder of your Zend Framework 2 application

#### Starting a worker
Start a worker that will keep monitoring a specific queue for jobs scheduled to be processed. This worker will continue until it has reached certain criteria (exceeds a memory limit or has processed a specified number of jobs).

`php index.php queue doctrine <queueName> --start`

A worker will exit when you press cntr-C *after* it has finished the current job it is working on. (PHP doesn't support signal handling on Windows)

*Warning : In previous versions of SlmQueueDoctrine the worker would quit if there where no jobs available for 
processing. That meant you could savely create a cronjob that would start a worker every minute. If you do that now
you will quickly run out of available resources.*

To work around this limitation a --max-workers switch has been added which will keep track of the running workers for a particular queue. If more workers are running then specified the process will exit immediately.

`php index.php queue doctrine <queueName> --max-workers=2 --start`

Now, you can let your script run indefinitely. While this was not possible in PHP versions previous to 5.3, it is now
not a big deal. This has the other benefit of not needing to bootstrap the application every time, which is good
for performance.


#### Recovering jobs

To recover jobs which are in the 'running' state for prolonged period of time (specified in minutes) use the following command.

`php index.php queue doctrine <queueName> --recover [--executionTime=]`

*Note : Workers that are processing a job that is being recovered are NOT stopped.*
