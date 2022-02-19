<?php
namespace Mdoq\Connector\MagentoCatalog\Model\Product;

class Image extends \Magento\Catalog\Model\Product\Image
{
	protected function isEnvironmentMdoq()
	{
		return (isset($_SERVER['SERVER_NAME']) && preg_match('/\.mdoq\.io$/', $_SERVER['SERVER_NAME']) === 1);
	}
	
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
    	if($this->isEnvironmentMdoq()){
    		return true;
    	}
    	return parent::_fileExists($filename);
    }
    
     /**
     * @return $this
     */
    public function saveFile()
    {
    	if($this->isEnvironmentMdoq()){
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
    	if($this->isEnvironmentMdoq()){
    		return $this;
    	}
	return parent::resize();
    }
}
