<?php
/**
 * ProcessEight
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact ProcessEight for more information.
 *
 * @package     ProcessEight\PriceImportAsync
 * @copyright   Copyright (c) 2018 ProcessEight
 * @author      ProcessEight
 *
 */

namespace ProcessEight\PriceImportAsync\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\Console\Cli;
use ProcessEight\PriceImportAsync\Exception\TimerException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AsyncPriceImportCommand extends Command
{
    /**
     * Name of the argument which defines the number of child processes to spawn
     */
    const NUMBER_OF_CHILD_PROCESSES = 'processes';

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \ProcessEight\CatalogImagesResizeSync\Api\TimerInterface
     */
    private $timer;

    /**
     * @var \ProcessEight\PriceImportAsync\Model\Adapter\ReactPHP\ProcessFactory
     */
    private $processFactory;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @param \Magento\Framework\App\State                                         $appState
     * @param \ProcessEight\PriceImportAsync\Api\TimerInterface                    $timer
     * @param \ProcessEight\PriceImportAsync\Model\Adapter\ReactPHP\ProcessFactory $processFactory
     * @param \React\EventLoop\Factory                                             $loopFactory
     * @param \Magento\Framework\Serialize\Serializer\Json                         $jsonSerializer
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \ProcessEight\PriceImportAsync\Api\TimerInterface $timer,
        \ProcessEight\PriceImportAsync\Model\Adapter\ReactPHP\ProcessFactory $processFactory,
        \React\EventLoop\Factory $loopFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        $this->appState       = $appState;
        $this->timer          = $timer;
        $this->processFactory = $processFactory;
        $this->loop           = $loopFactory::create();
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('processeight:catalog:prices:import:async')
             ->setDescription('Imports product prices asyncly')
             ->addArgument(
                 self::NUMBER_OF_CHILD_PROCESSES,
                 InputArgument::OPTIONAL,
                 'Number of child processes to spawn',
                 3
             )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws TimerException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \ProcessEight\CatalogImagesResizeSync\Exception\TimerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Starting timer...</info>");
        $output->writeln("<info>Using {$input->getArgument(self::NUMBER_OF_CHILD_PROCESSES)} child processes</info>");
        $this->timer->startTimer();

        $this->appState->setAreaCode(Area::AREA_GLOBAL);

        try {
            $numberOfChildProcesses = (int)$input->getArgument(self::NUMBER_OF_CHILD_PROCESSES);

            $allPrices = array_map('str_getcsv', file('/var/www/vhosts/async-php/magento226-async-experiments/htdocs/app/code/ProcessEight/PriceImportAsync/Console/Command/async-prices.csv'));

            $this->processBasePricesUsingEventLoop($allPrices ?? [], $numberOfChildProcesses);

        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }

        $this->timer->stopTimer();
        $output->writeln("");
        $output->writeln("Stopped timer.");
        $output->writeln("<info>All product prices imported successfully in {$this->timer->getExecutionTimeInSeconds()} seconds.</info>");

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param int[] $productBasePrices
     * @param int   $numberOfChildProcesses
     */
    private function processBasePricesUsingEventLoop(array $productBasePrices, int $numberOfChildProcesses) : void
    {
        $numberOfChunks         = $this->calculateNumberOfChunksForChildProcesses(
            $productBasePrices,
            $numberOfChildProcesses
        );
        $productBasePriceChunks = array_chunk($productBasePrices, $numberOfChunks);
        foreach ($productBasePriceChunks as $chunk) {
            $this->createChildProcess($this->getChildProcessCommand($chunk));
        }
        $this->loop->run();
    }

    /**
     * @param int[] $productBasePrices
     * @param int   $numberOfChildProcesses
     *
     * @return int
     */
    private function calculateNumberOfChunksForChildProcesses(
        array $productBasePrices,
        int $numberOfChildProcesses
    ) : int {
        $numberOfChunks = (int)(count($productBasePrices) / $numberOfChildProcesses);

        return $numberOfChunks > 0 ? $numberOfChunks : 1;
    }

    /**
     * @param string $command
     */
    private function createChildProcess(string $command) : void
    {
        $reactProcess = $this->processFactory->create($command);
        $reactProcess->start($this->loop);

        $reactProcess->stdout->on('data', function ($chunk) {
            echo $chunk;
        });
    }

    /**
     * @param int[] $productPrices
     *
     * @return string
     */
    private function getChildProcessCommand(array $productPrices) : string
    {
        return PHP_BINARY
               . sprintf(
                   ' %s/bin/magento %s %s',
                   BP,
                   BatchPriceImportCommand::NAME,
                   "'" . $this->jsonSerializer->serialize($productPrices) . "'"
               );
    }
}
