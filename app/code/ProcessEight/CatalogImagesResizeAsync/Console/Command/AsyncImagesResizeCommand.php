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
 * @package     ProcessEight\CatalogImagesResizeAsync
 * @copyright   Copyright (c) 2018 ProcessEight
 * @author      ProcessEight
 *
 */

namespace ProcessEight\CatalogImagesResizeAsync\Console\Command;

use Magento\Catalog\Model\ResourceModel\Product\Image as ProductImage;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use ProcessEight\CatalogImagesResizeAsync\Exception\TimerException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AsyncImagesResizeCommand extends Command
{
    /**
     * Name of the argument which defines the number of child processes to spawn
     */
    const NUMBER_OF_CHILD_PROCESSES = 'processes';

    /**
     * @var State
     */
    private $appState;

    /**
     * @var ProductImage
     */
    private $productImage;

    /**
     * @var \ProcessEight\CatalogImagesResizeSync\Api\TimerInterface
     */
    private $timer;

    /**
     * @var \ProcessEight\CatalogImagesResizeAsync\Model\Adapter\ReactPHP\ProcessFactory
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
     * @param \ProcessEight\CatalogImagesResizeAsync\Api\TimerInterface                    $timer
     * @param \ProcessEight\CatalogImagesResizeAsync\Model\Adapter\ReactPHP\ProcessFactory $processFactory
     * @param \React\EventLoop\Factory                                                     $loopFactory
     * @param \Magento\Framework\Serialize\Serializer\Json                                 $jsonSerializer
     * @param State                                                                        $appState
     * @param ProductImage                                                                 $productImage
     */
    public function __construct(
        \ProcessEight\CatalogImagesResizeAsync\Api\TimerInterface $timer,
        \ProcessEight\CatalogImagesResizeAsync\Model\Adapter\ReactPHP\ProcessFactory $processFactory,
        \React\EventLoop\Factory $loopFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        State $appState,
        ProductImage $productImage = null
    ) {
        $this->timer          = $timer;
        $this->processFactory = $processFactory;
        $this->loop           = $loopFactory::create();
        $this->jsonSerializer = $jsonSerializer;
        $this->appState       = $appState;
        $this->productImage   = $productImage ?: ObjectManager::getInstance()->get(ProductImage::class);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('processeight:catalog:images:resize:async')
             ->setDescription('Creates resized product images asyncly')
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
            $count                  = $this->productImage->getCountAllProductImages();
            if (!$count) {
                $output->writeln("<info>No product images to resize</info>");
                $this->timer->stopTimer();
                $output->writeln("<info>Command took {$this->timer->getExecutionTimeInSeconds()} seconds.</info>");

                return Cli::RETURN_SUCCESS;
            }

            $productImages = $this->productImage->getAllProductImages();
            foreach ($productImages as $productImage) {
                $images[] = $productImage;
            }

            $this->processImagesUsingEventLoop($images ?? [], $numberOfChildProcesses);

        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }

        $this->timer->stopTimer();
        $output->write("\n");
        $output->writeln("<info>{$count} product images re-sized successfully in {$this->timer->getExecutionTimeInSeconds()} seconds.</info>");

        return 0;
    }

    /**
     * @param int[] $productImages
     * @param int   $numberOfChildProcesses
     */
    private function processImagesUsingEventLoop(array $productImages, int $numberOfChildProcesses) : void
    {
        $numberOfChunks     = $this->calculateNumberOfChunksForChildProcesses($productImages, $numberOfChildProcesses);
        $productImageChunks = array_chunk($productImages, $numberOfChunks);
        foreach ($productImageChunks as $chunk) {
            $this->createChildProcess($this->getChildProcessCommand($chunk));
        }
        $this->loop->run();
    }

    /**
     * @param int[] $productImages
     * @param int   $numberOfChildProcesses
     *
     * @return int
     */
    private function calculateNumberOfChunksForChildProcesses(array $productImages, int $numberOfChildProcesses) : int
    {
        $numberOfChunks = (int)(count($productImages) / $numberOfChildProcesses);

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
     * @param int[] $productImages
     *
     * @return string
     */
    private function getChildProcessCommand(array $productImages) : string
    {
        return PHP_BINARY
               . sprintf(
                   ' %s/bin/magento %s %s',
                   BP,
                   BatchImagesResizeCommand::NAME,
                   "'" . $this->jsonSerializer->serialize($productImages) . "'"
               );
    }
}
