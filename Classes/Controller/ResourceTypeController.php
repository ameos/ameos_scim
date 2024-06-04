<?php

declare(strict_types=1);

namespace Ameos\Scim\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;

class ResourceTypeController
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
                [
                    'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:ResourceType'],
                    'id' => 'User',
                    'name' => 'User',
                    'endpoint' => '/scim/v2/Users',
                    'description' => 'User Account',
                    'schema' => 'urn:ietf:params:scim:schemas:core:2.0:User',
                    'meta' => [
                        'location' => $normalizedParams->getSiteUrl() . 'scim/v2/ResourceTypes/User',
                        'resourceType' => 'ResourceType'
                    ]
                ],
                [
                    'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:ResourceType'],
                    'id' => 'Group',
                    'name' => 'Group',
                    'endpoint' => '/scim/v2/Groups',
                    'description' => 'Group',
                    'schema' => 'urn:ietf:params:scim:schemas:core:2.0:Group',
                    'meta' => [
                        'location' => $normalizedParams->getSiteUrl() . 'scim/v2/ResourceTypes/Group',
                        'resourceType' => 'ResourceType'
                    ]
                ]
            ]
        );
    }
}
