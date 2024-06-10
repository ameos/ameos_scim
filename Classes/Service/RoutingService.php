<?php

declare(strict_types=1);

namespace Ameos\Scim\Service;

use Ameos\Scim\Controller\AbstractResourceController;
use Ameos\Scim\Controller\BulkController;
use Ameos\Scim\Controller\GroupController;
use Ameos\Scim\Controller\UserController;
use Ameos\Scim\Controller\ResourceTypeController;
use Ameos\Scim\Controller\ServiceProviderConfigController;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Exception\RoutingFailedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RoutingService
{
    public const HTTP_GET = 'GET';
    public const HTTP_PUT = 'PUT';
    public const HTTP_POST = 'POST';
    public const HTTP_PATCH = 'PATCH';
    public const HTTP_DELETE = 'DELETE';

    /**
     * @param ExtensionConfiguration $extensionConfiguration
     */
    public function __construct(private readonly ExtensionConfiguration $extensionConfiguration)
    {
    }

    /**
     * route a request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function route(ServerRequestInterface $request): ResponseInterface
    {
        $config = $this->extensionConfiguration->get('scim');
        $context = $request->getAttribute('scim_context');

        $path = str_replace(
            '/',
            '\/',
            $context === Context::Frontend ? $config['fe_path'] : $config['be_path']
        );

        $regexRoot = '/^' . $path . '(Users|Groups)\/?$/i';
        $regexUuid = '/^' . $path . '(Users|Groups)\/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/i';

        $response = null;
        if (
            preg_match($regexRoot, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_GET
        ) {
            $response = $this->getController($matches[1], $context)->searchAction($request, $context);
        }

        if (
            preg_match($regexRoot, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_POST
        ) {
            $response = $this->getController($matches[1], $context)->createAction($request, $context);
        }

        if (
            preg_match($regexUuid, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_GET
        ) {
            $response = $this->getController($matches[1], $context)->readAction($matches[2], $request, $context);
        }

        if (
            preg_match($regexUuid, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_PATCH
        ) {
            $response = $this->getController($matches[1], $context)->patchAction($matches[2], $request, $context);
        }

        if (
            preg_match($regexUuid, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_PUT
        ) {
            $response = $this->getController($matches[1], $context)->updateAction($matches[2], $request, $context);
        }

        if (
            preg_match($regexUuid, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_DELETE
        ) {
            $response = $this->getController($matches[1], $context)->deleteAction($matches[2], $request, $context);
        }

        if (
            preg_match('/^' . $path . 'ResourceTypes\/?$/i', $request->getUri()->getPath())
            && $request->getMethod() === self::HTTP_GET
        ) {
            $response = GeneralUtility::makeInstance(ResourceTypeController::class)->indexAction($request);
        }

        if (
            preg_match('/^' . $path . 'Bulk\/?$/i', $request->getUri()->getPath())
            && $request->getMethod() === self::HTTP_POST
        ) {
            $response = GeneralUtility::makeInstance(BulkController::class)->bulkAction($request, $context);
        }

        if (
            (
                preg_match('/^' . $path . 'ServiceProviderConfig\/?$/i', $request->getUri()->getPath())
                || preg_match('/^' . $path . 'ServiceConfiguration\/?$/i', $request->getUri()->getPath())
            ) && $request->getMethod() === self::HTTP_GET
        ) {
            $response = GeneralUtility::makeInstance(ServiceProviderConfigController::class)->indexAction($request);
        }

        if ($response === null) {
            throw new RoutingFailedException('Scim routing failed. Http request not valid');
        }

        return $response;
    }

    /**
     * return controller fqcn for corresponding to a URL segment
     *
     * @param string $segment
     * @param Context $context
     * @return AbstractResourceController
     */
    private function getController(string $segment, Context $context): ?AbstractResourceController
    {
        $controller = null;
        if (mb_strtolower($segment) === 'users') {
            $controller = GeneralUtility::makeInstance(UserController::class);
        } elseif (mb_strtolower($segment) === 'groups') {
            $controller = GeneralUtility::makeInstance(GroupController::class);
        }

        return $controller;
    }
}
