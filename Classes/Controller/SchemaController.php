<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Controller;

use Ameos\AmeosScim\Enum\Context;
use Ameos\AmeosScim\Enum\ResourceType;
use Ameos\AmeosScim\Service\SchemaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;

class SchemaController
{
    use Traits\ConfigurationAccess;

    public function __construct(
        private readonly SchemaService $schemaService,
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    /**
     * schema action
     *
     * @param ServerRequestInterface $request
     * @param Context $context
     * @return ResponseInterface
     */
    public function schemaAction(ServerRequestInterface $request, Context $context): ResponseInterface
    {
        /** @var NormalizedParams */
        $normalizedParams = $request->getAttribute('normalizedParams');
        $pathConf = $context === Context::Frontend ? 'fe_path' : 'be_path';
        $url = trim($normalizedParams->getSiteUrl(), '/') .
            $this->extensionConfiguration->get('ameos_scim', $pathConf)
            . 'Schemas/';

        $configuration = $this->getConfiguration($request);

        $schema = [];
        $schema[] = [
            'id' => 'urn:ietf:params:scim:schemas:core:2.0:User',
            'name' => 'User',
            'description' => 'User Account',
            'attributes' => $this->schemaService->convertMapping(
                $configuration['user']['mapping'],
                $context,
                ResourceType::User
            ),
            'meta' => [
                'resourceType' => 'Schema',
                'location' => $url . 'urn:ietf:params:scim:schemas:core:2.0:User'
            ]
        ];
        $schema[] = [
            'id' => 'urn:ietf:params:scim:schemas:core:2.0:Group',
            'name' => 'Group',
            'description' => 'Group',
            'attributes' => $this->schemaService->convertMapping(
                $configuration['group']['mapping'],
                $context,
                ResourceType::Group
            ),
            'meta' => [
                'resourceType' => 'Schema',
                'location' => $url . 'urn:ietf:params:scim:schemas:core:2.0:Group'
            ]
        ];

        return new JsonResponse($schema);
    }
}
