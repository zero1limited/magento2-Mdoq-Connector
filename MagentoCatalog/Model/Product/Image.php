<?php
namespace Mdoq\Connector\MagentoCatalog\Model\Product;

use Mdoq\Connector\Helper\Mdoq as MdoqHelper;

class Image extends \Magento\Catalog\Model\Product\Image
{
    /**
     * First check this file on FS
     * If it doesn't exist - try to download it from DB
     *
     * @param string $filename
     * @return bool
     */
    protected function _fileExists($filename)
    {
        //TODO change this to check remotely, then fallback to local
        if(MdoqHelper::isEnvironmentMdoq()){
            return true;
        }
        return parent::_fileExists($filename);
    }

    /**
     * @return $this
     */
    public function saveFile()
    {
        if(MdoqHelper::isEnvironmentMdoq()){
            return $this;
        }
        return parent::saveFile();
    }

    /**
     * @see \Magento\Framework\Image\Adapter\AbstractAdapter
     * @return $this
     */
    public function resize()
    {
        if(MdoqHelper::isEnvironmentMdoq()){
            return $this;
        }
        return parent::saveFile();
    }

    /**
     * Return resized product image information
     *
     * @return array
     */
    public function getResizedImageInfo()
    {
        if(MdoqHelper::isEnvironmentMdoq()){
            return null;
        }
        return parent::getResizedImageInfo();
    }

    /**
     * Check is image cached
     *
     * @return bool
     */
    public function isCached()
    {
        if(MdoqHelper::isEnvironmentMdoq()) {
            return false;
        }
        return parent::isCached();
    }
}