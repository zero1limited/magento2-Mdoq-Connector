<?php
namespace Mdoq\Connector\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Mdoq\Connector\Model\Connector;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    const CONFIG_PATH_ENABLED = 'mdoq_connector/connector/enable';
    const CONFIG_PATH_ENDPOINT = 'mdoq_connector/connector/url_key';
    const CONFIG_PATH_AUTHKEY = 'mdoq_connector/connector/auth_key';
    const CONFIG_PATH_PHP_BIN = 'mdoq_connector/connector/php_bin';

    /** @var ScopeConfigInterface Magento\Framework\App\Config */
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
            if($this->getAuthKey() != null && $this->getAuthKey() != $this->getRequest()->getHeader('x-mdoq-auth')) {
                $output = $this->resultFactory->create(ResultFactory::TYPE_RAW);
                $output->setHeader('Content-Type','text/plain')->setContents('MDOQ Connector Error: authentication failed. Check the auth keys in your Magento Admin match the auth keys set against your live instance in MDOQ.');
                return $output;
            } else {
                $output = $this->resultFactory->create(ResultFactory::TYPE_RAW);
                $output->setHeader('Content-Type','text/plain')
                    ->setContents(
                        $this->connector->run($this->getPhpBin())
                    );
                return $output;
            }
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
            $output = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $output->setHeader('Content-Type','text/plain')->setContents('The MDOQ connector is disabled.');
            return $output;
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

    public function getAuthKey()
    {
        return $this->configInterface->getValue(self::CONFIG_PATH_AUTHKEY);
    }
    
    public function getPhpBin()
    {
    	return $this->configInterface->getValue(self::CONFIG_PATH_PHP_BIN);
    }
}
