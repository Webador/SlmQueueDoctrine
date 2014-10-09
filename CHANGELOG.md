# 0.4.0-beta3

* [BC] Due to changes in SlmQueue ([changelog](https://github.com/juriansluiman/SlmQueue/blob/master/CHANGELOG.md)) existing jobs won't be able to be executed correctly.

# 0.4.0-beta1

* [BC] SlmQueueDoctrine has been upgraded to SlmQueue 0.4. This feature includes a new, flexible and modular event system.

# 0.3.0

* Initial release for 0.3.* branch

# 0.3.0-beta1

First release of SlmQueueDoctrine which bring it on par with its parent SlmQueue 0.3.0-beta1

Comes with unittests, bug fixes and general happiness but please be aware of the following, if you are upgrading from an earlier version...

- Existing jobs are not compatible (Job content is now serialized). Make sure you empty the queue before upgrading.
- It is suggested that you recreate the table from data/queue_default.sql as the schema changed in minor ways.
- The ability to configure the queue defaults has been removed. Any queue specific configuration now goes into the queues configuration of slm_queue.local.php

	
	

