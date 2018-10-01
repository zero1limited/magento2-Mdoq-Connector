<?php
namespace Mdoq\Connector\Model\Config\Backend;

use Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite;

class UrlKey extends \Magento\Framework\App\Config\Value implements \Magento\Framework\App\Config\ValueInterface
{
	const TARGET_PATH = 'mdoq-connector/index/index';

	/**
	 * @var \Magento\Framework\ObjectManagerInterface
	 */
	protected $_objectManager;

	/**
	 * @var \Magento\UrlRewrite\Model\UrlRewrite
	 */
	protected $_urlRewrite;

    protected $storeManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
	 * @param ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param array $data
     */
	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\App\Config\ScopeConfigInterface $config,
		\Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		array $data = []
	) {
		parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);

		$this->_objectManager = $objectManagerInterface;
        $this->storeManager = $storeManagerInterface;
	}

	public function beforeSave()
    {
        $value = $this->getValue();

        preg_match('#[^a-zA-Z0-9]#', $value, $matches);

        if(count($matches) > 0){
            throw new \Exception('API endpoint may only contain [a-z0-9A-Z]');
        }

        if(strlen($value) != 255) {
            throw new \Exception('API endpoint must be exactly 255 characters');
        }

        $this->setValue($value);
        return parent::beforeSave();
    }

	public function afterSave()
	{
		if($this->isValueChanged()){
            $this->updateUrlRewrites();
		}
		return parent::afterSave();
	}

	protected function updateUrlRewrites()
	{
        // delete the current ones
        /** @var \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite */
        $urlRewrite = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite');
        /** @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection $collection */
        $collection = $urlRewrite->getCollection();
        $collection->addFieldToFilter('target_path', self::TARGET_PATH);
        foreach($collection as $urlRewrite){
            $urlRewrite->delete();
        }

        // if no value don't want any
        if(!$this->getValue()){
            return $this;
        }

        // add the new ones
        foreach($this->storeManager->getStores() as $store){
            $urlRewrite = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite');
            $urlRewrite->setEntityType(Rewrite::ENTITY_TYPE_CUSTOM)
                ->setRequestPath($this->getValue())
                ->setTargetPath(self::TARGET_PATH)
                ->setRedirectType(0)
                ->setStoreId($store->getId())
                ->setDescription('Url rewrite to allow access to the MDOQ Connector module');
            $urlRewrite->save();
        }

        return $this;
	}
}
