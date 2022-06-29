<?php

namespace Jakhotiya\Bundling\Package\Bundle;

use Magento\Deploy\Config\BundleConfig;
use Magento\Deploy\Package\BundleInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\WriteInterface;
use Magento\Framework\View\Asset\Minification;

class RequireJs implements BundleInterface
{
    /**
     * Static files Bundling configuration class
     *
     * @var BundleConfig
     */
    private $bundleConfig;

    /**
     * Helper class for static files minification related processes
     *
     * @var Minification
     */
    private $minification;

    /**
     * Static content directory writable interface
     *
     * @var WriteInterface
     */
    private $staticDir;

    /**
     * Package area
     *
     * @var string
     */
    private $area;

    /**
     * Package theme
     *
     * @var string
     */
    private $theme;

    /**
     * Package locale
     *
     * @var string
     */
    private $locale;

    /**
     * Bundle content pools
     *
     * @var string[]
     */
    private $contentPools = [
       'common'=>'common',
        'cms'=>'cms',
        'category'=>'category',
        'product'=>'product',
        'checkout'=>'checkout'
    ];


    /**
     * Files content cache
     *
     * @var string[]
     */
    private $fileContent = [];



    /**
     * Relative path to directory where bundle files should be created
     *
     * @var string
     */
    private $pathToBundleDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Bundle constructor
     *
     * @param Filesystem $filesystem
     * @param BundleConfig $bundleConfig
     * @param Minification $minification
     * @param string $area
     * @param string $theme
     * @param string $locale
     * @param array $contentPools
     */
    public function __construct(
        Filesystem $filesystem,
        BundleConfig $bundleConfig,
        Minification $minification,
        $area,
        $theme,
        $locale,
        array $contentPools = []
    ) {
        $this->filesystem = $filesystem;
        $this->bundleConfig = $bundleConfig;
        $this->minification = $minification;
        $this->staticDir = $filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);
        $this->area = $area;
        $this->theme = $theme;
        $this->locale = $locale;
        $this->contentPools = array_merge($this->contentPools, $contentPools);
        $this->pathToBundleDir = $this->area . '/' . $this->theme . '/' . $this->locale . '/' . self::BUNDLE_JS_DIR;
    }

    /**
     * @inheritdoc
     */
    public function addFile($fileId, $sourcePath, $contentType)
    {

        return true;
    }

    public function flush(){
        $area = $this->area;$theme = $this->theme;$locale = $this->locale;
        $mapFilePath = $area . '/' . $theme . '/' . $locale . '/bundle.config.json';
        $resultMap = $this->staticDir->readFile($mapFilePath);
        $bundleConfig = json_decode($resultMap, true);
        foreach($bundleConfig as $bndl){
            $fileIds = array_values($bndl['modules']);
            $bundleFile = $this->startNewBundleFile($bndl['name']);
            $content = [];
            foreach($fileIds as $fileId){

                $contentType  = strpos($fileId,'.html')!==false ? 'html' : 'js';
                $sourcePath = $area.'/'.$theme.'/'.$locale.'/';
                if($contentType==='js'){
                    $fileName = $fileId.'.js';
                }else{
                    $fileName = $fileId;
                }
                $sourcePath .= $fileName;
                $sourcePath = $this->minification->addMinifiedSign($sourcePath);
                 if(!$this->staticDir->isExist($sourcePath)){
                     continue;
                 }
                $fileContent = $this->getFileContent($sourcePath);
                $content[$this->minification->addMinifiedSign($fileName)] = $fileContent;
            }
            $this->endBundleFile($bundleFile, $content);
            $bundleFile->write($this->getInitJs());
        }
        return true;
    }


    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->staticDir->delete($this->pathToBundleDir);

        return true;
    }

    /**
     * Create new bundle file and write beginning content to it
     *
     * @param string $bundleName
     * @return WriteInterface
     */
    private function startNewBundleFile($bundleName)
    {
        $bundleFile = $this->staticDir->openFile(
            $this->minification->addMinifiedSign($this->pathToBundleDir . '/bundle-' . $bundleName . '.js')
        );
        $bundleFile->write("require.config({\"config\": {\n");
        $bundleFile->write("        \"jsbuild\":");

        return $bundleFile;
    }

    /**
     * Write ending content to bundle file
     *
     * @param WriteInterface $bundleFile
     * @param array $contents
     * @return bool true on success
     */
    private function endBundleFile(WriteInterface $bundleFile, array $contents)
    {
        if ($contents) {
            $content = json_encode($contents, JSON_UNESCAPED_SLASHES);
            $bundleFile->write("{$content}\n");
        } else {
            $bundleFile->write("{}\n");
        }
        $bundleFile->write("}});\n");
        return true;
    }

    /**
     * Get content of static file
     *
     * @param string $sourcePath
     * @return string
     */
    private function getFileContent($sourcePath)
    {
        if (!isset($this->fileContent[$sourcePath])) {
            $content = $this->staticDir->readFile($this->minification->addMinifiedSign($sourcePath));
            if (mb_detect_encoding($content) !== "UTF-8") {
                $content = mb_convert_encoding($content, "UTF-8");
            }

            $this->fileContent[$sourcePath] = $content;
        }
        return $this->fileContent[$sourcePath];
    }



    /**
     * Bundle initialization script content (this must be added to the latest bundle file at the very end)
     *
     * @return string
     */
    private function getInitJs()
    {
        return "require.config({\n" .
            "    bundles: {\n" .
            "        'mage/requirejs/static': [\n" .
            "            'jsbuild',\n" .
            "            'buildTools',\n" .
            "            'text',\n" .
            "            'statistician'\n" .
            "        ]\n" .
            "    },\n" .
            "    deps: [\n" .
            "        'jsbuild'\n" .
            "    ]\n" .
            "});\n";
    }
}
