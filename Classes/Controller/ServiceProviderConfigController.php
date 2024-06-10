<?php

declare(strict_types=1);

namespace Ameos\Scim\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;

class ServiceProviderConfigController
{
    /**
     * index action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        /** @var NormalizedParams */
        $normalizedParams = $request->getAttribute('normalizedParams');

        return new JsonResponse(
            [
                'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig'],
                'patch' => [
                    'supported' => true
                ],
                'bulk' => [
                    'supported' => true,
                ],
                'filter' => [
                    'supported' => true,
                    'maxResults' => 200
                ],
                'changePassword' => [
                    'supported' => false
                ],
                'sort' => [
                    'supported' => true
                ],
                'etag' => [
                    'supported' => true
                ],
                'authenticationSchemes' => [],
                'meta' => [
                    'location' => $normalizedParams->getSiteUrl() . 'scim/v2/ServiceProviderConfig',
                    'resourceType' => 'ServiceProviderConfig',
                    'created' => (new \DateTime())->format('c'),
                    'lastModified' => (new \DateTime())->format('c'),
                    'version' => 'W/"' . md5((string)time()) . '"',
                ]
            ]
        );
    }
}
