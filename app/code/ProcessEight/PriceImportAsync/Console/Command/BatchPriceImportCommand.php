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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BatchPriceImportCommand extends Command
{
    /**
     * Name of this command
     */
    const NAME = 'processeight:catalog:price:import:batch';

    /**
     * Name of argument which passes the JSON-encoded price data
     */
    const ARGUMENT = 'product-prices';

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \ProcessEight\PriceImportAsync\Api\TimerInterface
     */
    private $timer;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

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
     * @param \ProcessEight\PriceImportAsync\Api\TimerInterface     $timer
     * @param \Magento\Framework\Serialize\Serializer\Json          $jsonSerializer
     * @param \Magento\Catalog\Api\Data\BasePriceInterfaceFactory   $basePriceFactory
     * @param \Magento\Catalog\Api\BasePriceStorageInterfaceFactory $basePriceStorageFactory
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \ProcessEight\PriceImportAsync\Api\TimerInterface $timer,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Magento\Catalog\Api\Data\BasePriceInterfaceFactory $basePriceFactory,
        \Magento\Catalog\Api\BasePriceStorageInterfaceFactory $basePriceStorageFactory
    ) {
        $this->appState                = $appState;
        $this->timer                   = $timer;
        $this->jsonSerializer          = $jsonSerializer;
        $this->basePriceFactory        = $basePriceFactory;
        $this->basePriceStorageFactory = $basePriceStorageFactory;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
             ->setDescription('Imports catalog prices a batch at a time')
             ->addArgument(
                 self::ARGUMENT,
                 InputArgument::REQUIRED,
                 'JSON-encoded string of product price data.'
             )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(Area::AREA_GLOBAL);
        $status = Cli::RETURN_SUCCESS;

        try {
            $productPrices = $this->jsonSerializer->unserialize(
                $input->getArgument(self::ARGUMENT)
            );

            /** @var \Magento\Catalog\Api\BasePriceStorageInterface $basePriceStorage */
            $basePriceStorage = $this->basePriceStorageFactory->create();
            $processed        = 0;
            $basePrices       = [];
            foreach ($productPrices as $price) {
                /** @var \Magento\Catalog\Api\Data\BasePriceInterface $basePrice */
                $basePrice = $this->basePriceFactory->create();
                $basePrice->setSku($price[0]);
                $basePrice->setStoreId($price[1]);
                $basePrice->setPrice($price[2]);
                $basePrices[] = $basePrice;

                $processed++;
            }
            $basePriceStorage->update($basePrices);
        } catch (\Exception $e) {
            $messages[] = "<error>{$e->getMessage()}</error>";
            $status     = Cli::RETURN_FAILURE;
        }

        $messages[] = "<info>{$processed} prices imported successfully.</info>";

        $output->writeln(implode(PHP_EOL, $messages));

        return $status;
    }
}
