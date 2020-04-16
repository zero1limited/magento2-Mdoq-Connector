<?php
namespace Mdoq\Connector\MagentoCatalog\Model\View\Asset;

use Magento\Catalog\Model\Product\Media\ConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\View\Asset\ContextInterface;
use Magento\Framework\View\Asset\LocalInterface;
use Magento\Catalog\Model\View\Asset\Image as MagentoImage;
use Mdoq\Connector\Helper\Mdoq as MdoqHelper;

/**
 * A locally available image file asset that can be referred with a file path
 *
 * This class is a value object with lazy loading of some of its data (content, physical file path)
 */
class Image extends MagentoImage
{
    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        if(MdoqHelper::isEnvironmentMdoq()){
            return $this->getContext()->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getImageInfo();
        }
        return parent::getUrl();
    }

    /**
     * Generate path from image info
     *
     * @return string
     */
    private function getImageInfo()
    {
        $path = ltrim($this->getFilePath(), '/');
        return preg_replace('|\Q'. DIRECTORY_SEPARATOR . '\E+|', DIRECTORY_SEPARATOR, $path);
    }
}
