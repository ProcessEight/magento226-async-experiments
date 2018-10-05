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

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Image\CacheFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
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
use Magento\Framework\View\ConfigInterface as ViewConfig;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Catalog\Model\Product\ImageFactory as ProductImageFactory;

class AsyncImagesResizeCommand extends Command
{
    const ARGUMENT_NUMBER_OF_THREADS = 'threads';

    /**
     * @var State
     */
    protected $appState;

    /**
     * @deprecated
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @deprecated
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @deprecated
     * @var CacheFactory
     */
    protected $imageCacheFactory;

    /**
     * @var ProductImage
     */
    private $productImage;

    /**
     * @var ViewConfig
     */
    private $viewConfig;

    /**
     * @var ThemeCollection
     */
    private $themeCollection;

    /**
     * @var ProductImageFactory
     */
    private $productImageFactory;

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
     * @param CollectionFactory                                                            $productCollectionFactory
     * @param ProductRepositoryInterface                                                   $productRepository
     * @param CacheFactory                                                                 $imageCacheFactory
     * @param ProductImage                                                                 $productImage
     * @param ViewConfig                                                                   $viewConfig
     * @param ThemeCollection                                                              $themeCollection
     * @param ProductImageFactory                                                          $productImageFactory
     */
    public function __construct(
        \ProcessEight\CatalogImagesResizeAsync\Api\TimerInterface $timer,
        \ProcessEight\CatalogImagesResizeAsync\Model\Adapter\ReactPHP\ProcessFactory $processFactory,
        \React\EventLoop\Factory $loopFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        State $appState,
        CollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        CacheFactory $imageCacheFactory,
        ProductImage $productImage = null,
        ViewConfig $viewConfig = null,
        ThemeCollection $themeCollection = null,
        ProductImageFactory $productImageFactory = null
    ) {
        $this->timer                    = $timer;
        $this->processFactory           = $processFactory;
        $this->loop                     = $loopFactory::create();
        $this->appState                 = $appState;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository        = $productRepository;
        $this->imageCacheFactory        = $imageCacheFactory;
        $this->productImage             = $productImage ?: ObjectManager::getInstance()->get(ProductImage::class);
        $this->viewConfig               = $viewConfig ?: ObjectManager::getInstance()->get(ViewConfig::class);
        $this->themeCollection          = $themeCollection ?: ObjectManager::getInstance()->get(ThemeCollection::class);
        $this->productImageFactory      = $productImageFactory
            ?: ObjectManager::getInstance()->get(ProductImageFactory::class);
        parent::__construct();
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('processeight:catalog:images:resize:async')
             ->setDescription('Creates resized product images asyncly')
             ->addArgument(
                 self::ARGUMENT_NUMBER_OF_THREADS,
                 InputArgument::OPTIONAL,
                 'Number of threads for running the command',
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
        $this->timer->startTimer();

        $this->appState->setAreaCode(Area::AREA_GLOBAL);

        try {
            $numberOfThreads = (int)$input->getArgument(self::ARGUMENT_NUMBER_OF_THREADS);
            $count           = $this->productImage->getCountAllProductImages();
            if (!$count) {
                $output->writeln("<info>No product images to resize</info>");

                return Cli::RETURN_SUCCESS;
            }

            $productImages = $this->productImage->getAllProductImages();
            foreach ($productImages as $productImage) {
                $images[] = $productImage;
            }
            $this->startProcesses($images ?? [], $numberOfThreads);

            // Figure this out later
//            $progress = new ProgressBar($output, $count);
//            $progress->setFormat(
//                "%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>"
//            );

//            if ($output->getVerbosity() !== OutputInterface::VERBOSITY_NORMAL) {
//                $progress->setOverwrite(false);
//            }

//            foreach ($productImages as $image) {
//                $originalImageName = $image['filepath'];
//
//                foreach ($viewImages as $viewImage) {
//                    $image = $this->makeImage($originalImageName, $viewImage);
//                    $image->resize();
//                    $image->saveFile();
//                }
//                $progress->setMessage($originalImageName);
//                $progress->advance();
//            }
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            // we must have an exit code higher than zero to indicate something was wrong
            return Cli::RETURN_FAILURE;
        }

        $this->timer->stopTimer();
        $output->write("\n");
        $output->writeln("<info>{$count} product images resized successfully in {$this->timer->getExecutionTimeInSeconds()} seconds.</info>");

        return 0;
    }

    /**
     * @param int[] $productImages
     * @param int   $numberOfThreads
     */
    protected function startProcesses(array $productImages, int $numberOfThreads) : void
    {
        $numberOfChunks     = $this->calculateNumberOfChunksForThreads($productImages, $numberOfThreads);
        $productImageChunks = array_chunk($productImages, $numberOfChunks);
        foreach ($productImageChunks as $chunk) {
            $this->createProcessDefinition($this->getFullCommand($chunk));
        }
        $this->loop->run();
    }

    /**
     * @param int[] $productImages
     * @param int   $numberOfThreads
     *
     * @return int
     */
    protected function calculateNumberOfChunksForThreads(array $productImages, int $numberOfThreads) : int
    {
        $numberOfChunks = (int)(count($productImages) / $numberOfThreads);

        return $numberOfChunks > 0 ? $numberOfChunks : 1;
    }

    /**
     * @param string $command
     */
    protected function createProcessDefinition(string $command) : void
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
    protected function getFullCommand(array $productImages) : string
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
