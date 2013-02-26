<?php
namespace GoalioQueueDoctrine\Controller;
use Goalio\Mvc\Controller\AbstractActionController;
use GoalioQueueDoctrine\Queue\Table;


class WorkerController extends AbstractActionController {

    public function processAction() {
        /** @var $worker \GoalioQueueDoctrine\Worker\Worker */
        $worker = $this->serviceLocator->get('GoalioQueueDoctrine\Worker\Worker');
        $queueName = $this->params('queueName');
        $options = array();

        try {
            $count = $worker->processQueue($queueName, array_filter($options));
        } catch(Exception $exception) {
            return "\nAn error occurred " . $exception->getMessage() . "\n\n";
        }

        return sprintf(
            "\nWork for queue %s is done, %s jobs were processed\n\n",
            $queueName,
            $count
        );
    }


    public function recoverAction() {
        /** @var $worker \GoalioQueueDoctrine\Worker\Worker */
        $worker = $this->serviceLocator->get('GoalioQueueDoctrine\Worker\Worker');
        $queueName = $this->params('queueName');
        $executionTime = $this->params('executiontime', 0);

        /** @var $queueManager \SlmQueue\Queue\QueuepluginManager */
        $queueManager = $this->getServiceLocator()->get('SlmQueue\Queue\QueuePluginManager');
        $queue = $queueManager->get('main');

        if(!$queue instanceof Table) {
            return sprintf("\nQueue % does not support the recovering of job\n\n", $queueName);
        }

        try {
            $count = $queue->recover($executionTime);
        } catch(Exception $exception) {
            return "\nAn error occurred " . $exception->getMessage() . "\n\n";
        }

        return sprintf(
            "\nWork for queue %s is done, %s jobs were recovered\n\n",
            $queueName,
            $count
        );

    }

}