<?php
namespace Mdoq\Connector\Model\Config\Backend;

class AuthKey extends \Magento\Framework\App\Config\Value implements \Magento\Framework\App\Config\ValueInterface
{
	/**
	 * @var \Magento\Framework\ObjectManagerInterface
	 */
	protected $_objectManager;

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

	public function beforeSave()
	{
        $value = $this->getValue();

        if(strlen($value) > 32) {
            throw new \Exception('Auth key must be 32 characters max');
        }

        $this->setValue($value);
        return parent::beforeSave();
	}
}
