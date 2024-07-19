<?php

declare(strict_types=1);

namespace Ameos\Scim\Middleware;

use Ameos\Scim\Enum\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class ScimRequirementMiddleware implements MiddlewareInterface
{
    /**
     * @param ExtensionConfiguration $extensionConfiguration
     */
    public function __construct(private readonly ExtensionConfiguration $extensionConfiguration)
    {
    }

    /**
     * process middle ware
     * if uri start with /v2/Users or /v2/Groups : call Scim controller
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = $this->extensionConfiguration->get('scim');

        if (
            (bool)$config['be_activation'] === true
            && preg_match('/^' . str_replace('/', '\/', $config['be_path']) . '.*/', $request->getUri()->getPath())
        ) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] = false;
            $request = $this->enrichingRequest($request, Context::Backend);
        }
        if (
            (bool)$config['fe_activation'] === true
            && preg_match('/^' . str_replace('/', '\/', $config['fe_path']) . '.*/', $request->getUri()->getPath())
        ) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError'] = false;
            $request = $this->enrichingRequest($request, Context::Frontend);
        }

        return $handler->handle($request);
    }

    /**
     * enriching scim request
     *
     * @param ServerRequestInterface $request
     * @param Context $context
     * @return ServerRequestInterface $request
     */
    private function enrichingRequest(ServerRequestInterface $request, Context $context): ServerRequestInterface
    {
        $queryParams = $request->getQueryParams();
        $queryParams['id'] = $request->getAttribute('site')->getRootPageId();
        $request = $request->withQueryParams($queryParams);

        $request = $request->withAttribute('is_scim_request', true);
        $request = $request->withAttribute('scim_context', $context);

        return $request;
    }
}
