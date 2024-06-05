<?php

declare(strict_types=1);

namespace Ameos\Scim\Service;

use Ameos\Scim\Controller\AbstractResourceController;
use Ameos\Scim\Controller\Backend\GroupController as BackendGroupController;
use Ameos\Scim\Controller\Backend\UserController as BackendUserController;
use Ameos\Scim\Controller\Frontend\GroupController as FrontendGroupController;
use Ameos\Scim\Controller\Frontend\UserController as FrontendUserController;
use Ameos\Scim\Controller\ResourceTypeController;
use Ameos\Scim\Controller\ServiceProviderConfigController;
use Ameos\Scim\Exception\RoutingFailedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RoutingService
{
    protected const HTTP_GET = 'GET';
    protected const HTTP_PUT = 'PUT';
    protected const HTTP_POST = 'POST';
    protected const HTTP_PATCH = 'PATCH';
    protected const HTTP_DELETE = 'DELETE';

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

        $path = str_replace(
            '/',
            '\/',
            $request->getAttribute('scim_context') === 'frontend' ? $config['fe_path'] : $config['be_path']
        );

        $regexRoot = '/^' . $path . '(Users|Groups)$/';
        $regexUuid = '/^' . $path . '(Users|Groups)\/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/';

        $response = null;
        if (
            preg_match($regexRoot, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_GET
        ) {
            $response = $this->getControllerForSegment($matches[1], $request->getAttribute('scim_context'))
                ->searchAction($request);
        }

        if (
            preg_match($regexRoot, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_POST
        ) {
            $response = $this->getControllerForSegment($matches[1], $request->getAttribute('scim_context'))
                ->createAction($request);
        }

        if (
            preg_match($regexUuid, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_GET
        ) {
            $response = $this->getControllerForSegment($matches[1], $request->getAttribute('scim_context'))
                ->readAction($matches[2], $request);
        }

        if (
            preg_match($regexUuid, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_PATCH
        ) {
            $response = $this->getControllerForSegment($matches[1], $request->getAttribute('scim_context'))
                ->patchAction($matches[2], $request);
        }

        if (
            preg_match($regexUuid, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_PUT
        ) {
            $response = $this->getControllerForSegment($matches[1], $request->getAttribute('scim_context'))
                ->updateAction($matches[2], $request);
        }

        if (
            preg_match($regexUuid, $request->getUri()->getPath(), $matches)
            && $request->getMethod() === self::HTTP_DELETE
        ) {
            $response = $this->getControllerForSegment($matches[1], $request->getAttribute('scim_context'))
                ->deleteAction($matches[2], $request);
        }

        if (
            preg_match('/^' . $path . 'ResourceTypes$/', $request->getUri()->getPath())
            && $request->getMethod() === self::HTTP_GET
        ) {
            $response = GeneralUtility::makeInstance(ResourceTypeController::class)->indexAction($request);
        }

        if (
            (
                preg_match('/^' . $path . 'ServiceProviderConfig$/', $request->getUri()->getPath())
                || preg_match('/^' . $path . 'ServiceConfiguration$/', $request->getUri()->getPath())
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
     * @param string $context
     * @return AbstractResourceController
     */
    private function getControllerForSegment(string $segment, string $context): ?AbstractResourceController
    {
        $controller = null;
        if ($segment === 'Users') {
            $fqcn = $context === 'frontend' ? FrontendUserController::class : BackendUserController::class;
            $controller = GeneralUtility::makeInstance($fqcn);
        } elseif ($segment === 'Groups') {
            $fqcn = $context === 'frontend' ? FrontendGroupController::class : BackendGroupController::class;
            $controller = GeneralUtility::makeInstance($fqcn);
        }

        return $controller;
    }
}
