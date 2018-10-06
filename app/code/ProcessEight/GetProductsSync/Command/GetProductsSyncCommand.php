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
 * @package     ProcessEight\GetProductsSync
 * @copyright   Copyright (c) 2018 ProcessEight
 * @author      ProcessEight
 *
 */

declare(strict_types=1);

namespace ProcessEight\GetProductsSync\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetProductsSyncCommand extends Command
{
    /**
     * @var \ProcessEight\GetProductsSync\Api\TimerInterface
     */
    private $timer;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Api\Search\SearchCriteriaInterface
     */
    private $searchCriteria;

    /**
     * GetProductsSyncCommand constructor.
     *
     * @param \ProcessEight\GetProductsSync\Api\TimerInterface      $timer
     * @param \Magento\Framework\App\State                          $appState
     * @param \Magento\Catalog\Api\ProductRepositoryInterface       $productRepository
     * @param \Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria
     */
    public function __construct(
        \ProcessEight\GetProductsSync\Api\TimerInterface $timer,
        \Magento\Framework\App\State $appState,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria
    ) {
        $this->timer             = $timer;
        $this->appState          = $appState;
        $this->productRepository = $productRepository;
        $this->searchCriteria    = $searchCriteria;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName("processeight:get-products:sync");
        $this->setDescription("Load all product data syncly");
        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \ProcessEight\GetProductsSync\Exception\TimerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Starting timer...</info>");
        $this->timer->startTimer();
        $status = Cli::RETURN_SUCCESS;

        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        $productRepo = $this->productRepository->getList(
            $this->searchCriteria
        );

        // Make sure we trigger any 'on load' logic
        foreach ($productRepo->getItems() as $key => $product) {
            $output->writeln($key);
            $output->writeln(var_export($product->getData(), true));
        }

        $this->timer->stopTimer();
        $output->writeln("<info>Stopped timer.</info>");
        $output->writeln("<info>Products loaded successfully in {$this->timer->getExecutionTimeInSeconds()} seconds.</info>");

        return $status;
    }
}
