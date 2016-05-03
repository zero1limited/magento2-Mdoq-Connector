<?php
namespace Zero1\Gateway\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Zero1\Gateway\Model\Gateway;

class Index extends \Magento\Framework\App\Action\Action
{
    const CONFIG_PATH_ENABLED = 'zero1_gateway/gateway/enable';
    const CONFIG_PATH_ENDPOINT = 'zero1_gateway/gateway/url_key';

    /** @var ScopeConfigInterface Magento\Framework\App\Config */
    protected  $configInterface;

    protected $gateway;

    public function __construct(
        Context $context,
        ScopeConfigInterface $configInterface,
        Gateway $gateway
    ){
        $this->configInterface = $configInterface;
        $this->gateway = $gateway;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return $this
     */
    public function execute()
    {
        if($this->getEndpoint() != ltrim($this->getRequest()->getUri()->getPath(), '/')){
            return $this->_forward('noroute');
        }

        if(!$this->isEnabled()){
            echo 'Module Disabled';
            return;
        }

        $this->gateway->run();
        return;
    }

    public function isEnabled()
    {
        return (bool)$this->configInterface->getValue(self::CONFIG_PATH_ENABLED);
    }

    public function getEndpoint()
    {
        return $this->configInterface->getValue(self::CONFIG_PATH_ENDPOINT);
    }
}
