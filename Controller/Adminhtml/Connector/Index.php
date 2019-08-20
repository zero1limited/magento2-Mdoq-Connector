<?php
namespace Mdoq\Connector\Controller\Adminhtml\Connector;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Mdoq\Connector\Model\Connector;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Backend\App\Action
{
	const CONFIG_PATH_ENABLED = 'mdoq_connector/connector/enable';
	const CONFIG_PATH_ADMIN_ACCESS_ENABLED = 'mdoq_connector/connector/admin_access_enable';
    const CONFIG_PATH_PHP_BIN = 'mdoq_connector/connector/php_bin';

    protected  $configInterface;

    protected $connector;

    public function __construct(
        Context $context,
        ScopeConfigInterface $configInterface,
        Connector $connector
    ){
        $this->configInterface = $configInterface;
        $this->connector = $connector;
        $this->resultFactory = $context->getResultFactory();
        parent::__construct($context);
    }
    
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
    	if($this->isEnabled() && $this->isAdminAccessEnabled()) {
    		echo $this->connector->run($this->getPhpBin());
    		die();
    	} else {
        	return $this->_forward('noroute');
    	}
    }
    
    public function execute()
    {
        return $this->_forward('noroute');
    }
    
	public function isEnabled()
    {
        return (bool)$this->configInterface->getValue(self::CONFIG_PATH_ENABLED);
    }
    
    public function isAdminAccessEnabled()
    {
        return (bool)$this->configInterface->getValue(self::CONFIG_PATH_ADMIN_ACCESS_ENABLED);
    }
    
    public function getPhpBin()
    {
    	return $this->configInterface->getValue(self::CONFIG_PATH_PHP_BIN);
    }
}