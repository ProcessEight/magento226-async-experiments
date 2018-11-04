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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AsyncPriceImportCommand extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \ProcessEight\CatalogImagesResizeSync\Api\TimerInterface
     */
    private $timer;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * Magento Catalog Api BasePriceStorageInterfaceFactory
     *
     * @var \Magento\Catalog\Api\BasePriceStorageInterfaceFactory $basePriceStorageFactory
     */
    private $basePriceStorageFactory;

    /**
     * Magento Catalog Api CostStorageInterfaceFactory
     *
     * @var \Magento\Catalog\Api\CostStorageInterfaceFactory $costPriceStorageFactory
     */
    private $costPriceStorageFactory;

    /**
     * Magento Catalog Api Data BasePriceInterfaceFactory
     *
     * @var \Magento\Catalog\Api\Data\BasePriceInterfaceFactory $basePriceFactory
     */
    private $basePriceFactory;

    /**
     * Magento Catalog Api Data CostInterfaceFactory
     *
     * @var \Magento\Catalog\Api\Data\CostInterfaceFactory $costPriceFactory
     */
    private $costPriceFactory;


    /**
     * @param \Magento\Framework\App\State                          $appState
     * @param \ProcessEight\PriceImportAsync\Api\TimerInterface     $timer
     * @param \React\EventLoop\Factory                              $loopFactory
     * @param \Magento\Catalog\Api\BasePriceStorageInterfaceFactory $basePriceStorageFactory
     * @param \Magento\Catalog\Api\CostStorageInterfaceFactory      $costPriceStorageFactory
     * @param \Magento\Catalog\Api\Data\BasePriceInterfaceFactory   $basePriceFactory
     * @param \Magento\Catalog\Api\Data\CostInterfaceFactory        $costPriceFactory
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \ProcessEight\PriceImportAsync\Api\TimerInterface $timer,
        \React\EventLoop\Factory $loopFactory,
        \Magento\Catalog\Api\BasePriceStorageInterfaceFactory $basePriceStorageFactory,
        \Magento\Catalog\Api\CostStorageInterfaceFactory $costPriceStorageFactory,
        \Magento\Catalog\Api\Data\BasePriceInterfaceFactory $basePriceFactory,
        \Magento\Catalog\Api\Data\CostInterfaceFactory $costPriceFactory
    ) {
        $this->appState                = $appState;
        $this->timer                   = $timer;
        $this->loop                    = $loopFactory::create();
        $this->basePriceStorageFactory = $basePriceStorageFactory;
        $this->costPriceStorageFactory = $costPriceStorageFactory;
        $this->basePriceFactory        = $basePriceFactory;
        $this->costPriceFactory        = $costPriceFactory;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('processeight:catalog:prices:import:async')
             ->setDescription('Imports product prices asyncly');
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
        $this->appState->setAreaCode(Area::AREA_GLOBAL);
        $pricesFilename = '2044-prices.csv';

        $filesystem = \React\Filesystem\Filesystem::create($this->loop);
        /** @var \React\Filesystem\Node\File $file */
        $file = $filesystem->file("/var/www/vhosts/async-php/magento226-async-experiments/htdocs/app/code/ProcessEight/PriceImportAsync/Data/{$pricesFilename}");

        $output->writeln("<info>Importing {$pricesFilename}...</info>");
        $output->writeln("<warn>I'm not entirely convinced the timer is working properly when called twice from the same class. Perhaps split this into two commands?</warn>");
        $output->writeln("<info>Method One: For processing small files which can fit into memory</info>");
        $output->writeln("<info>Starting timer...</info>");
        $this->timer->startTimer();

        try {
            // Load the entire file into memory, then return it
            // The main differences between this and file_get_contents()
            // is that this is faster and non-blocking
            $promise = $file->getContents()
                /**
                 * then() transforms a promise's value by applying a function
                 * to the promise's fulfillment or rejection value.
                 * It then returns a new promise for the transformed result.
                 *
                 * @see https://reactphp.org/promise/#promiseinterfacethen
                 */
                ->then(
                    /**
                     * Invoked once the promise is fulfilled and passed the result as the first argument.
                     * This callback method transforms the result value in some way and then returns it as a new promise
                     *
                     * @see https://reactphp.org/promise/#how-promise-forwarding-works
                     */
                    function ($result) {
                        // File contents are returned as a string, just like file_get_contents()
                        // Now convert the string into an array
                        $allPrices = array_map(
                            'str_getcsv',
                            str_getcsv($result, "\n")
                        );

                        // Remove header row
                        unset($allPrices[0]);

                        return $allPrices;
                    },
                    /**
                     * Invoked once the promise is rejected and passed the reason as the first argument.
                     */
                    function ($reason) {
                        echo "Promise rejected with error {$reason} on line " . __LINE__ . PHP_EOL;
                    }
                );

            /**
             * The intent of done() is to consume a promise's value,
             * transferring responsibility for the value to your code.
             *
             * Consumes the promise's ultimate value if the promise fulfills,
             * or handles the ultimate error.
             *
             * It will cause a fatal error if either the fulfilled or rejected
             * callbacks throw or return a rejected promise.
             *
             * Since the purpose of done() is consumption rather than transformation,
             * done() always returns null.
             *
             * @see https://reactphp.org/promise/#extendedpromiseinterfacedone
             */
            $promise->done(
                // Invoked once the promise is fulfilled and passed the result as the first argument.
                function ($result) {
//                    echo "Promise resolved with result on line " . __LINE__ . PHP_EOL;
                    $this->processPrices($result);
                },
                // Invoked once the promise is rejected and passed the reason as the first argument.
                function ($error) {
                    echo "Promise rejected with error {$error} on line " . __LINE__ . PHP_EOL;
                }
            );

            $file->close();

            $this->loop->run();

        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }

        $this->timer->stopTimer();
        $output->writeln("<info>Stopped timer.</info>");
        $output->writeln("<info>All prices imported successfully in {$this->timer->getExecutionTimeInSeconds()} seconds.</info>");
        $output->writeln("<info>Peak memory usage: {$this->timer->getMemoryPeakUsage()}");

        // Method 2

        $output->writeln("<info>(@TODO) Method Two: For processing very large files which cannot fit into memory</info>");
        $output->writeln("<info>Starting timer...</info>");

        $this->timer->startTimer();

        try {
            $file2 = $filesystem->file("/var/www/vhosts/async-php/magento226-async-experiments/htdocs/app/code/ProcessEight/PriceImportAsync/Data/{$pricesFilename}");
            $file2->open('r')->then(function ($stream) {
                $stream->on('data', function ($chunk) {
                    echo 'Chunk read: ' . PHP_EOL;
                });
            });
            $this->loop->run();

        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }

        $this->timer->stopTimer();
        $output->writeln("Stopped timer.");
        $output->writeln("<info>All product prices imported successfully in {$this->timer->getExecutionTimeInSeconds()} seconds.</info>");
        $output->writeln("<info>Peak memory usage: {$this->timer->getMemoryPeakUsage()}");

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Process the prices using the event loop
     *
     * @param int[] $productPrices
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function processPrices(array $productPrices) : bool
    {
        $processed = 0;

        /** @var \Magento\Catalog\Api\BasePriceStorageInterface $basePriceStorage */
        $basePriceStorage = $this->basePriceStorageFactory->create();
        /** @var \Magento\Catalog\Api\CostStorageInterface $costPriceStorage */
        $costPriceStorage = $this->costPriceStorageFactory->create();

        /** @var \Magento\Catalog\Api\Data\BasePriceInterface $basePrice */
        $emptyBasePrice = $this->basePriceFactory->create();
        /** @var \Magento\Catalog\Api\Data\CostInterface $costPrice */
        $emptyCostPrice = $this->costPriceFactory->create();

        foreach ($productPrices as $price) {
            $basePrice = $emptyBasePrice;
            $basePrice->setSku($price[0]);
            $basePrice->setStoreId($price[1]);
            $basePrice->setPrice($price[2]);
            $basePrices[] = $basePrice;

            $costPrice = $emptyCostPrice;
            $costPrice->setSku($price[0]);
            $costPrice->setStoreId($price[1]);
            $costPrice->setCost($price[4]);
            $costPrices[] = $costPrice;

            $processed++;

            if ($processed % 1000 == 0) {
                $basePriceStorage->update($basePrices);
                $basePrices = [];
                $costPriceStorage->update($costPrices);
                $costPrices = [];
            }
        }
        if (!empty($basePrices)) {
            $basePriceStorage->update($basePrices);
        }
        if (!empty($costPrices)) {
            $costPriceStorage->update($costPrices);
        }

        return $processed;
    }
}
