<?php
namespace Mdoq\Connector\MagentoFramework\App\Action;

use Magento\Framework\App\Action\Forward as MagentoForward;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Forward extends MagentoForward implements CsrfAwareActionInterface
{
    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        if($request->getAlias(UrlInterface::REWRITE_REQUEST_PATH_ALIAS) === null){
            return null;
        }

        if($request->getPathInfo() != '/mdoq-connector/index/index'){
            return null;
        }

        return true;
    }
}