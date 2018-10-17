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
 * @package     ProcessEight\PriceImportSync
 * @copyright   Copyright (c) 2018 ProcessEight
 * @author      ProcessEight
 *
 */

namespace ProcessEight\PriceImportSync\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class PriceImportCommand extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \ProcessEight\PriceImportSync\Api\TimerInterface
     */
    private $timer;

    /**
     * @var \Magento\Catalog\Api\Data\BasePriceInterfaceFactory
     */
    private $basePriceFactory;

    /**
     * @var \Magento\Catalog\Api\BasePriceStorageInterfaceFactory
     */
    private $basePriceStorageFactory;

    /**
     * @var \Magento\Catalog\Api\Data\CostInterfaceFactory
     */
    private $costPriceFactory;

    /**
     * @var \Magento\Catalog\Api\CostStorageInterfaceFactory
     */
    private $costPriceStorageFactory;

    /**
     * @param \Magento\Framework\App\State                          $appState
     * @param \ProcessEight\PriceImportSync\Api\TimerInterface      $timer
     * @param \Magento\Catalog\Api\Data\BasePriceInterfaceFactory   $basePriceFactory
     * @param \Magento\Catalog\Api\BasePriceStorageInterfaceFactory $basePriceStorageFactory
     * @param \Magento\Catalog\Api\Data\CostInterfaceFactory        $costPriceFactory
     * @param \Magento\Catalog\Api\CostStorageInterfaceFactory      $costPriceStorageFactory
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \ProcessEight\PriceImportSync\Api\TimerInterface $timer,
        \Magento\Catalog\Api\Data\BasePriceInterfaceFactory $basePriceFactory,
        \Magento\Catalog\Api\BasePriceStorageInterfaceFactory $basePriceStorageFactory,
        \Magento\Catalog\Api\Data\CostInterfaceFactory $costPriceFactory,
        \Magento\Catalog\Api\CostStorageInterfaceFactory $costPriceStorageFactory
    ) {
        $this->timer                   = $timer;
        $this->appState                = $appState;
        $this->basePriceFactory        = $basePriceFactory;
        $this->basePriceStorageFactory = $basePriceStorageFactory;
        $this->costPriceFactory        = $costPriceFactory;
        $this->costPriceStorageFactory = $costPriceStorageFactory;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('processeight:catalog:prices:import:sync')
             ->setDescription('Imports product prices syncly')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \ProcessEight\PriceImportSync\Exception\TimerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Starting timer...</info>");
        $this->timer->startTimer();

        $this->appState->setAreaCode(Area::AREA_GLOBAL);

        try {
            $count          = 0;
            $basePriceCount = 0;
            $costPriceCount = 0;
            $progress       = new ProgressBar($output, $count);
            $progress->setFormat(
                "%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>"
            );

            if ($output->getVerbosity() !== OutputInterface::VERBOSITY_NORMAL) {
                $progress->setOverwrite(false);
            }

            /** @var \Magento\Catalog\Api\BasePriceStorageInterface $basePriceStorage */
            $basePriceStorage = $this->basePriceStorageFactory->create();
            $basePrices       = [];
            /** @var \Magento\Catalog\Api\CostStorageInterface $costPriceStorage */
            $costPriceStorage = $this->costPriceStorageFactory->create();
            $costPrices       = [];
            if (($syncPricesCsvHandle = fopen('/var/www/vhosts/async-php/magento226-async-experiments/htdocs/app/code/ProcessEight/PriceImportSync/Console/Command/sync-prices.csv',
                    "r")) !== false) {
                while (($price = fgetcsv($syncPricesCsvHandle)) !== false) {
                    /** @var \Magento\Catalog\Api\Data\BasePriceInterface $basePrice */
                    $basePrice = $this->basePriceFactory->create();
                    $basePrice->setSku($price[0]);
                    $basePrice->setStoreId($price[1]);
                    $basePrice->setPrice($price[2]);
                    $basePrices[] = $basePrice;

                    /** @var \Magento\Catalog\Api\Data\CostInterface $costPrice */
                    $costPrice = $this->costPriceFactory->create();
                    $costPrice->setSku($price[0]);
                    $costPrice->setStoreId($price[1]);
                    $costPrice->setCost($price[4]);
                    $costPrices[] = $costPrice;

                    $count++;
                    $basePriceCount++;
                    $costPriceCount++;
                    $progress->setMessage($price[0]);
                    $progress->advance();

                    if ($count % 100 == 0) {
                        $basePriceStorage->update($basePrices);
                        $basePrices = [];
                        $costPriceStorage->update($costPrices);
                        $costPrices = [];
                    }
                }
            }
            fclose($syncPricesCsvHandle);
            if (!empty($basePrices)) {
                $basePriceStorage->update($basePrices);
                $progress->advance();
            }
            if (!empty($costPrices)) {
                $costPriceStorage->update($costPrices);
                $progress->advance();
            }
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }

        $this->timer->stopTimer();
        $output->writeln("");
        $output->writeln("<info>Stopped timer.</info>");
        $output->writeln("<info>{$basePriceCount} base prices and {$costPriceCount} cost prices imported successfully in {$this->timer->getExecutionTimeInSeconds()} seconds.</info>");

        return Cli::RETURN_SUCCESS;
    }
}
