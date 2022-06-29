<?php

namespace Jakhotiya\Bundling\Service;

use Magento\Deploy\Config\BundleConfig;
use Magento\Deploy\Package\BundleInterfaceFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Utility\Files;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Asset\RepositoryMap;

class Bundle
{
    /**
     * Path to package subdirectory where bundle files are located
     */
    const BUNDLE_JS_DIR = 'js/bundle';

    /**
     * Matched file extension name for JavaScript files
     */
    const ASSET_TYPE_JS = 'js';

    /**
     * Matched file extension name for template files
     */
    const ASSET_TYPE_HTML = 'html';

    /**
     * Public static directory writable interface
     *
     * @var Filesystem\Directory\WriteInterface
     */
    private $pubStaticDir;

    /**
     * Factory for Bundle object
     *
     * @see BundleInterface
     * @var BundleInterfaceFactory
     */
    private $bundleFactory;

    /**
     * Utility class for collecting files by specific pattern and location
     *
     * @var Files
     */
    private $utilityFiles;

    /**
     * Cached data about files which must be excluded from bundling
     *
     * @var array
     */
    private $excludedCache = [];

    /**
     * List of supported types of static files
     *
     * @var array
     * */
    public static $availableTypes = [
        self::ASSET_TYPE_JS,
        self::ASSET_TYPE_HTML
    ];

    /**
     * @var File|null
     */
    private $file;

    /**
     * @var BundleConfig
     */
    private $bundleConfig;

    /**
     * @param Filesystem $filesystem
     * @param BundleInterfaceFactory $bundleFactory
     * @param BundleConfig $bundleConfig
     * @param Files $files
     * @param File|null $file
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        BundleInterfaceFactory $bundleFactory,
        BundleConfig $bundleConfig,
        Files $files,
        File $file = null
    ) {
        $this->pubStaticDir = $filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);
        $this->bundleFactory = $bundleFactory;
        $this->bundleConfig = $bundleConfig;
        $this->utilityFiles = $files;
        $this->file = $file ?: ObjectManager::getInstance()->get(
            \Magento\Framework\Filesystem\Io\File::class
        );
    }

    /**
     * Deploy bundles for the given area, theme and locale
     *
     * @param string $area
     * @param string $theme
     * @param string $locale
     * @return void
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deploy($area, $theme, $locale)
    {
        $bundle = $this->bundleFactory->create(
            [
                'area' => $area,
                'theme' => $theme,
                'locale' => $locale
            ]
        );

        // delete all previously created bundle files
        $bundle->clear();

        $mapFilePath = $area . '/' . $theme . '/' . $locale . '/bundle.config.json';
        if ($this->pubStaticDir->isFile($mapFilePath)) {
            $bundle->flush();
        } else {
            //throw new \Exception('bundle.config.json does not exist in your theme hence your theme does not support bundling');
        }

    }

}
