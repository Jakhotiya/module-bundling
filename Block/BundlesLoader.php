<?php
declare(strict_types=1);

namespace Jakhotiya\Bundling\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\RequireJs\Config as RequireJsConfig;
use Magento\Framework\View\Asset\Minification;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Block needed to handle JS bundles in layout.
 */
class BundlesLoader extends Template
{

    /**
     * @var DirectoryList
     */
    private $dir;

    /**
     * @var PageConfig
     */
    protected $pageConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var RequireJsConfig
     */
    private $requireJsConfig;

    /**
     * @var string
     */
    protected $_template = 'Jakhotiya_Bundling::bundles-loader.phtml';

    /**
     * @param Context $context
     * @param DirectoryList $dir
     * @param PageConfig $pageConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param RequireJsConfig $requireJsConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        DirectoryList $dir,
        PageConfig $pageConfig,
        RequireJsConfig $requireJsConfig,
        ScopeConfigInterface $scopeConfig,
        Minification $minification,
        array $data = []
    ) {
        $this->dir = $dir;
        $this->pageConfig = $pageConfig;
        $this->scopeConfig = $scopeConfig;
        $this->requireJsConfig = $requireJsConfig;
        $this->minification = $minification;
        parent::__construct($context, $data);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            \Magento\Framework\View\Asset\Config::XML_PATH_JS_BUNDLING,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getCommonBundleUrl()
    {
        $commonBundle = $this->getData('common_bundle');
        if ($commonBundle && $this->isEnabled()) {
            return $this->getViewFileUrl($commonBundle['bundle_path']);
        }

        return '';
    }

    /**
     * @return string[] List of page bundles URLs.
     */
    public function getPageBundlesUrls()
    {
        $pageBundles = $this->getData('page_bundles');
        if (!empty($pageBundles) && $this->isEnabled()) {
            return array_map(function($pageBundle) {
                return $this->getViewFileUrl($pageBundle['bundle_path']);
            }, $pageBundles);
        }

        return [];
    }

    /**
     * @return string[] List of bundles URLs to prefetch when browser is idle.
     */
    public function getPrefetchBundlesUrls()
    {
        $prefetchBundles = $this->getData('prefetch_bundles');
        if (!empty($prefetchBundles) && $this->isEnabled()) {
            return array_values(
                array_map(function($prefetchBundle) {
                    return $this->getViewFileUrl($prefetchBundle);
                }, $prefetchBundles)
            );
        }

        return [];
    }

}
