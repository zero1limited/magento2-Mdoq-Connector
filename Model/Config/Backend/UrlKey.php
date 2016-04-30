<?php
namespace Zero1\Gateway\Model\Config\Backend;

use Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite;

class UrlKey extends \Magento\Framework\App\Config\Value implements \Magento\Framework\App\Config\ValueInterface
{
	const TARGET_PATH = 'zero1-gateway/index/index';
	/**
	 * @var \Magento\Framework\ObjectManagerInterface
	 */
	protected $_objectManager;

	/**
	 * @var \Magento\UrlRewrite\Model\UrlRewrite
	 */
	protected $_urlRewrite;

	/**
	 * @param \Magento\Framework\Model\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param ScopeConfigInterface $config
	 * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
	 * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
	 * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
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
		array $data = []
	) {
		parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);

		$this->_objectManager = $objectManagerInterface;
	}

	public function afterSave()
	{
		if($this->isValueChanged()){
			$model = $this->getUrlRewrite();
			$model->setRequestPath($this->getValue());
			$model->save();
		}
		return parent::afterSave();
	}
	/**
	 * Get URL rewrite from request
	 *
	 * @return \Magento\UrlRewrite\Model\UrlRewrite
	 */
	protected function getUrlRewrite()
	{
		if (!$this->_urlRewrite) {
			/** @var \Magento\UrlRewrite\Model\UrlRewrite $urlRewrite */
			$urlRewrite = $this->_objectManager->create('Magento\UrlRewrite\Model\UrlRewrite');
			/** @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection $collection */
			$collection = $urlRewrite->getCollection();
			$collection->addFieldToFilter('target_path' , self::TARGET_PATH);
			$collection->load();
			if($collection->count()){
				$this->_urlRewrite = $collection->getFirstItem();
			}else{
				$this->_urlRewrite = $urlRewrite;
				$this->_urlRewrite->setEntityType(Rewrite::ENTITY_TYPE_CUSTOM)
					->setTargetPath(self::TARGET_PATH)
					->setRedirectType(0)
					->setStoreId(1) //TODO revisit
					->setDescription('Url rewrite to allow access to the gateway module');
			}
		}
		return $this->_urlRewrite;
	}
}
