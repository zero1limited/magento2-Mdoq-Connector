<?php
namespace Zero1\Gateway\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Zero1\Gateway\Model\Gateway;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;

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
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        $this->_request = $request;

        if(($this->getEndpoint() == ltrim($this->getRequest()->getUri()->getPath(), '/')) && $this->isEnabled()){
            $this->gateway->run();
            die;
        }

        $profilerKey = 'CONTROLLER_ACTION:' . $request->getFullActionName();
        $eventParameters = ['controller_action' => $this, 'request' => $request];
        $this->_eventManager->dispatch('controller_action_predispatch', $eventParameters);
        $this->_eventManager->dispatch('controller_action_predispatch_' . $request->getRouteName(), $eventParameters);
        $this->_eventManager->dispatch(
            'controller_action_predispatch_' . $request->getFullActionName(),
            $eventParameters
        );
        \Magento\Framework\Profiler::start($profilerKey);

        $result = null;
        if ($request->isDispatched() && !$this->_actionFlag->get('', self::FLAG_NO_DISPATCH)) {
            \Magento\Framework\Profiler::start('action_body');
            $result = $this->execute();
            \Magento\Framework\Profiler::start('postdispatch');
            if (!$this->_actionFlag->get('', self::FLAG_NO_POST_DISPATCH)) {
                $this->_eventManager->dispatch(
                    'controller_action_postdispatch_' . $request->getFullActionName(),
                    $eventParameters
                );
                $this->_eventManager->dispatch(
                    'controller_action_postdispatch_' . $request->getRouteName(),
                    $eventParameters
                );
                $this->_eventManager->dispatch('controller_action_postdispatch', $eventParameters);
            }
            \Magento\Framework\Profiler::stop('postdispatch');
            \Magento\Framework\Profiler::stop('action_body');
        }
        \Magento\Framework\Profiler::stop($profilerKey);
        return $result ?: $this->_response;
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
