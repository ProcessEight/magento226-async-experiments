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

use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Catalog\Model\Product\Image;

class BatchImagesResizeCommand extends Command
{
    /**
     * Name of this command
     */
    const NAME = 'processeight:catalog:image:resize:batch';

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Magento\Framework\View\ConfigInterface
     */
    private $viewConfig;

    /**
     * @var \Magento\Theme\Model\ResourceModel\Theme\Collection
     */
    private $themeCollection;

    /**
     * @var \Magento\Catalog\Model\Product\ImageFactory
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
     * @param \Magento\Framework\App\State                              $appState
     * @param \Magento\Framework\View\ConfigInterface                   $viewConfig
     * @param \Magento\Theme\Model\ResourceModel\Theme\Collection       $themeCollection
     * @param \Magento\Catalog\Model\Product\ImageFactory               $productImageFactory
     */
    public function __construct(
        \ProcessEight\CatalogImagesResizeAsync\Api\TimerInterface $timer,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\View\ConfigInterface $viewConfig = null,
        \Magento\Theme\Model\ResourceModel\Theme\Collection $themeCollection = null,
        \Magento\Catalog\Model\Product\ImageFactory $productImageFactory = null
    ) {
        $this->timer               = $timer;
        $this->jsonSerializer      = $jsonSerializer;
        $this->appState            = $appState;
        $this->viewConfig          = $viewConfig
            ?: ObjectManager::getInstance()->get(\Magento\Framework\View\ConfigInterface::class);
        $this->themeCollection     = $themeCollection
            ?: ObjectManager::getInstance()->get(\Magento\Theme\Model\ResourceModel\Theme\Collection::class);
        $this->productImageFactory = $productImageFactory
            ?: ObjectManager::getInstance()->get(\Magento\Catalog\Model\Product\ImageFactory::class);
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
        $status = Cli::RETURN_SUCCESS;

        try {
            $productImages = $this->jsonSerializer->unserialize(
                $input->getArgument('product-images')
            );
            $themes        = $this->themeCollection->loadRegisteredThemes();
            $viewImages    = $this->getViewImages($themes->getItems());

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
            $status = Cli::RETURN_FAILURE;
        }

        return $status;
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
