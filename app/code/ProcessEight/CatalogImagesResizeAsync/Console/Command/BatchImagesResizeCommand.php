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
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\View\ConfigInterface as ViewConfig;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Catalog\Model\Product\Image;
use Magento\Catalog\Model\Product\ImageFactory as ProductImageFactory;

class BatchImagesResizeCommand extends Command
{
    const NAME = 'processeight:catalog:image:resize:batch';

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
     * @var \ProcessEight\CatalogImagesResizeAsync\Api\TimerInterface
     */
    private $timer;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @param \ProcessEight\CatalogImagesResizeAsync\Api\TimerInterface $timer
     * @param \Magento\Framework\Serialize\Serializer\Json              $jsonSerializer
     * @param State                                                     $appState
     * @param CollectionFactory                                         $productCollectionFactory
     * @param ProductRepositoryInterface                                $productRepository
     * @param CacheFactory                                              $imageCacheFactory
     * @param ProductImage                                              $productImage
     * @param ViewConfig                                                $viewConfig
     * @param ThemeCollection                                           $themeCollection
     * @param ProductImageFactory                                       $productImageFactory
     */
    public function __construct(
        \ProcessEight\CatalogImagesResizeAsync\Api\TimerInterface $timer,
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
        $this->jsonSerializer           = $jsonSerializer;
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
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
             ->setDescription('Re-sizes catalog images a batch at a time')
             ->addArgument(
                 'product-images',
                 InputArgument::REQUIRED
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

        try {
            $productImages = $this->jsonSerializer->unserialize(
                $input->getArgument('product-images')
            );

            $themes     = $this->themeCollection->loadRegisteredThemes();
            $viewImages = $this->getViewImages($themes->getItems());

            foreach ($productImages as $image) {
                $originalImageName = $image['filepath'];

                foreach ($viewImages as $viewImage) {
                    $image = $this->makeImage($originalImageName, $viewImage);
                    $image->resize();
                    $image->saveFile();
                }
            }
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            // we must have an exit code higher than zero to indicate something was wrong
            return Cli::RETURN_FAILURE;
        }

//        $output->write("\n");
//        $output->writeln("<info>Product images resized successfully.</info>");

        return 0;
    }

    /**
     * Make image
     *
     * @param string $originalImagePath
     * @param array  $imageParams
     *
     * @return Image
     * @throws \Exception
     */
    private function makeImage(string $originalImagePath, array $imageParams) : Image
    {
        $image = $this->productImageFactory->create();

        if (isset($imageParams['height'])) {
            $image->setHeight($imageParams['height']);
        }
        if (isset($imageParams['width'])) {
            $image->setWidth($imageParams['width']);
        }
        if (isset($imageParams['aspect_ratio'])) {
            $image->setKeepAspectRatio($imageParams['aspect_ratio']);
        }
        if (isset($imageParams['frame'])) {
            $image->setKeepFrame($imageParams['frame']);
        }
        if (isset($imageParams['transparency'])) {
            $image->setKeepTransparency($imageParams['transparency']);
        }
        if (isset($imageParams['constrain'])) {
            $image->setConstrainOnly($imageParams['constrain']);
        }
        if (isset($imageParams['background'])) {
            $image->setBackgroundColor($imageParams['background']);
        }

        $image->setDestinationSubdir($imageParams['type']);
        $image->setBaseFile($originalImagePath);

        return $image;
    }

    /**
     * Get view images data from themes
     *
     * @param array $themes
     *
     * @return array
     */
    private function getViewImages(array $themes) : array
    {
        $viewImages = [];
        foreach ($themes as $theme) {
            $config = $this->viewConfig->getViewConfig([
                'area'       => Area::AREA_FRONTEND,
                'themeModel' => $theme,
            ]);
            $images = $config->getMediaEntities('Magento_Catalog', ImageHelper::MEDIA_TYPE_CONFIG_NODE);
            foreach ($images as $imageId => $imageData) {
                $uniqIndex              = $this->getUniqueImageIndex($imageData);
                $imageData['id']        = $imageId;
                $viewImages[$uniqIndex] = $imageData;
            }
        }

        return $viewImages;
    }

    /**
     * Get unique image index
     *
     * @param array $imageData
     *
     * @return string
     */
    private function getUniqueImageIndex(array $imageData) : string
    {
        ksort($imageData);
        unset($imageData['type']);

        return md5(json_encode($imageData));
    }
}
