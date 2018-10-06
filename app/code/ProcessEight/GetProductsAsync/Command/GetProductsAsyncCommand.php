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
 * @package     ProcessEight\GetProductsAsync
 * @copyright   Copyright (c) 2018 ProcessEight
 * @author      ProcessEight
 *
 */

namespace ProcessEight\GetProductsAsync\Command;

use Magento\Framework\Console\Cli;
use React\MySQL\ConnectionInterface;
use React\MySQL\Factory;
use React\MySQL\QueryResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetProductsAsyncCommand extends Command
{
    /**
     * @var \ProcessEight\GetProductsAsync\Api\TimerInterface
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
     * GetProductsAsyncCommand constructor.
     *
     * @param \ProcessEight\GetProductsAsync\Api\TimerInterface     $timer
     * @param \Magento\Framework\App\State                          $appState
     * @param \Magento\Catalog\Api\ProductRepositoryInterface       $productRepository
     * @param \Magento\Framework\Api\Search\SearchCriteriaInterface $searchCriteria
     */
    public function __construct(
        \ProcessEight\GetProductsAsync\Api\TimerInterface $timer,
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
        $this->setName("processeight:get-products:async");
        $this->setDescription("Load product data asyncly");
        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \ProcessEight\GetProductsAsync\Exception\TimerException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Starting timer...</info>");
        $this->timer->startTimer();
        $status = Cli::RETURN_SUCCESS;

        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        $this->loadAllProductsAsyncly($output);

        $this->timer->stopTimer();
        $output->writeln("<info>Stopped timer.</info>");
        $output->writeln("<info>All products loaded successfully in {$this->timer->getExecutionTimeInSeconds()} seconds.</info>");

        return $status;
    }

    /**
     * @param OutputInterface $output
     */
    private function loadAllProductsAsyncly(OutputInterface $output)
    {
        $loop    = \React\EventLoop\Factory::create();
        $factory = new Factory($loop);

        $uri   = 'm2_async_exp:m2_async_exp@localhost/m2_async_exp';
        $query = 'select * from catalog_product_entity';

        // Create a mysql connection for executing query
        $factory->createConnection($uri)->then(function (ConnectionInterface $connection) use ($query, $output) {
            $connection->query($query)->then(function (QueryResult $command) use ($output) {
                if (isset($command->resultRows)) {
                    // This is a response to a SELECT etc. with some rows (0+)
                    $output->writeln(print_r($command->resultFields, true));
                    $output->writeln(print_r($command->resultRows, true));
                    $output->writeln(count($command->resultRows) . ' row(s) in set' . PHP_EOL);
                } else {
                    // This is an OK message in response to an UPDATE etc.
                    if ($command->insertId !== 0) {
                        $output->writeln('last insert ID', $command->insertId);
                    }
                    $output->writeln('Query OK, ' . $command->affectedRows . ' row(s) affected' . PHP_EOL);
                }
            }, function (\Exception $error) use ($output) {
                // The query was not executed successfully
                $output->writeln('Error: ' . $error->getMessage() . PHP_EOL);
            })
            ;

            $connection->quit();
        }, 'printf')
        ;

        $loop->run();
    }
}
