<?php

namespace SlmQueueDoctrine\Command;

use SlmQueue\Controller\Exception\WorkerProcessException;
use SlmQueue\Exception\ExceptionInterface;
use SlmQueue\Queue\QueuePluginManager;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Worker controller
 */
class RecoverJobsCommand extends Command
{
    /**
     * @var QueuePluginManager
     */
    protected $queuePluginManager;

    public function __construct(QueuePluginManager $queuePluginManager)
    {
        parent::__construct();

        $this->queuePluginManager = $queuePluginManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('queue', InputArgument::REQUIRED)
            ->addOption('executionTime', null, InputOption::VALUE_REQUIRED, '', 0);
    }

    /**
     * Recover long running jobs
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName     = $input->getArgument('queue');
        $executionTime = $input->getOption('executionTime');
        $queue         = $this->queuePluginManager->get($queueName);

        if (! $queue instanceof DoctrineQueueInterface) {
            return sprintf("\nQueue % does not support the recovering of job\n\n", $queueName);
        }

        try {
            $count = $queue->recover($executionTime);
        } catch (ExceptionInterface $exception) {
            throw new WorkerProcessException("An error occurred", $exception->getCode(), $exception);
        }

        $output->writeln(sprintf(
            "\nWork for queue %s is done, %s jobs were recovered\n\n",
            $queueName,
            $count
        ));

        return 0;
    }
}
