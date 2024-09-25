<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Service;

use Ameos\AmeosScim\Controller\AbstractResourceController;
use Ameos\AmeosScim\Controller\BulkController;
use Ameos\AmeosScim\Controller\GroupController;
use Ameos\AmeosScim\Controller\UserController;
use Ameos\AmeosScim\Controller\ResourceTypeController;
use Ameos\AmeosScim\Controller\SchemaController;
use Ameos\AmeosScim\Controller\ServiceProviderConfigController;
use Ameos\AmeosScim\Enum\Context;
use Ameos\AmeosScim\Exception\RoutingFailedException;
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
        $config = $this->extensionConfiguration->get('ameos_scim');
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
            preg_match('/^' . $path . 'Bulk\/?$/i', $request->getUri()->getPath())
            && $request->getMethod() === self::HTTP_POST
        ) {
            $response = GeneralUtility::makeInstance(BulkController::class)->bulkAction($request, $context);
        }

        if (
            preg_match('/^' . $path . 'ResourceTypes\/?$/i', $request->getUri()->getPath())
            && $request->getMethod() === self::HTTP_GET
        ) {
            $response = GeneralUtility::makeInstance(ResourceTypeController::class)->indexAction($request);
        }

        if (
            preg_match('/^' . $path . 'Schemas\/?$/i', $request->getUri()->getPath())
            && $request->getMethod() === self::HTTP_GET
        ) {
            $response = GeneralUtility::makeInstance(SchemaController::class)->schemaAction($request, $context);
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

        $response = $response
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate');

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
