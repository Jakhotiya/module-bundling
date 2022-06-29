<?php

namespace Jakhotiya\Bundling\Plugin;

use Magento\Framework\App\State as AppState;
use Magento\Framework\RequireJs\Config;

class Bundling
{
    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepo;

    /**
     * @param AppState $appState
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     */
    public function __construct(
        AppState $appState,
        \Magento\Framework\View\Asset\Repository $assetRepo
    ) {
        $this->appState = $appState;
        $this->assetRepo = $assetRepo;
    }

    public function aroundCreateBundleJsPool(){
        if ($this->appState->getMode() != AppState::MODE_PRODUCTION) {
            return [];
        }
        $bundleCommon = $this->assetRepo->createAsset('js/bundle/bundle-common.js');
        return [$bundleCommon];
    }
}
