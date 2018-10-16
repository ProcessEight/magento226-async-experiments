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
     * @param \Magento\Framework\App\State                          $appState
     * @param \ProcessEight\PriceImportSync\Api\TimerInterface      $timer
     * @param \Magento\Catalog\Api\Data\BasePriceInterfaceFactory   $basePriceFactory
     * @param \Magento\Catalog\Api\BasePriceStorageInterfaceFactory $basePriceStorageFactory
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \ProcessEight\PriceImportSync\Api\TimerInterface $timer,
        \Magento\Catalog\Api\Data\BasePriceInterfaceFactory $basePriceFactory,
        \Magento\Catalog\Api\BasePriceStorageInterfaceFactory $basePriceStorageFactory
    ) {
        $this->timer                   = $timer;
        $this->appState                = $appState;
        $this->basePriceFactory        = $basePriceFactory;
        $this->basePriceStorageFactory = $basePriceStorageFactory;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('processeight:catalog:prices:import:sync')
             ->setDescription('Imports product prices')
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
            $count    = 0;
            $progress = new ProgressBar($output, $count);
            $progress->setFormat(
                "%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>"
            );

            if ($output->getVerbosity() !== OutputInterface::VERBOSITY_NORMAL) {
                $progress->setOverwrite(false);
            }

            /** @var \Magento\Catalog\Api\BasePriceStorageInterface $basePriceStorage */
            $basePriceStorage = $this->basePriceStorageFactory->create();
            $basePrices       = [];
            if (($handle = fopen('/var/www/vhosts/async-php/magento226-async-experiments/htdocs/app/code/ProcessEight/PriceImportSync/Console/Command/prices.csv',
                    "r")) !== false) {
                while (($price = fgetcsv($handle)) !== false) {
                    /** @var \Magento\Catalog\Api\Data\BasePriceInterface $basePrice */
                    $basePrice = $this->basePriceFactory->create();
                    $basePrice->setSku($price[0]);
                    $basePrice->setPrice($price[3]);
                    $basePrice->setStoreId($price[2]);
                    $basePrices[] = $basePrice;

                    $count++;
                    $progress->setMessage($price[0]);
                    $progress->advance();

                    if ($count % 100 == 0) {
                        $basePriceStorage->update($basePrices);
                        $basePrices = [];
                    }
                }
            }
            if (!empty($basePrices)) {
                $basePriceStorage->update($basePrices);
                $progress->advance();
            }
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }

        $this->timer->stopTimer();
        $output->writeln("");
        $output->writeln("<info>Stopped timer.</info>");
        $output->writeln("<info>{$count} prices imported successfully in {$this->timer->getExecutionTimeInSeconds()} seconds.</info>");

        return Cli::RETURN_SUCCESS;
    }
}
