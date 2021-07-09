<?php

namespace SlmQueueDoctrine\Command;

use SlmQueue\Controller\Exception\WorkerProcessException;
use SlmQueue\Exception\ExceptionInterface;
use SlmQueue\Queue\QueuePluginManager;
use SlmQueue\Worker\WorkerInterface;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Worker controller
 */
class StartWorkerCommand extends Command
{
    protected static $defaultName = 'slm-queue-doctrine:start';

    /**
     * @var WorkerInterface
     */
    protected $worker;

    /**
     * @var QueuePluginManager
     */
    protected $queuePluginManager;

    public function __construct(WorkerInterface $worker, QueuePluginManager $queuePluginManager)
    {
        parent::__construct();

        $this->worker = $worker;
        $this->queuePluginManager = $queuePluginManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('queue', InputArgument::REQUIRED)
            ->addOption('start', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('queue');
        $queue = $this->queuePluginManager->get($name);

        try {
            $messages = $this->worker->processQueue($queue, $input->getArguments());
        } catch (ExceptionInterface $e) {
            throw new WorkerProcessException(
                'Caught exception while processing queue',
                $e->getCode(),
                $e
            );
        }

        $messages = implode("\n", array_map(function (string $message): string {
            return sprintf(' - %s', $message);
        }, $messages));

        $output->writeln(sprintf(
            "Finished worker for queue '%s':\n%s\n",
            $name,
            $messages
        ));

        return 0;
    }
}
