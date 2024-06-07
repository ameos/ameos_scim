<?php

declare(strict_types=1);

namespace Ameos\Scim\Service;

use Ameos\Scim\Domain\Repository\AbstractResourceRepository;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Enum\ResourceType;
use Ameos\Scim\Exception\NoResourceFoundException;
use Ameos\Scim\Service\MappingService;
use Ameos\Scim\Service\PatchService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;

class ResourceService
{
    /**
     * @param MappingService $mappingService
     * @param PatchService $patchService
     * @param ExtensionConfiguration $extensionConfiguration
     */
    public function __construct(
        private readonly MappingService $mappingService,
        private readonly PatchService $patchService,
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    /**
     * list resources
     *
     * @param AbstractResourceRepository $repository
     * @param ResourceType $resourceType
     * @param Context $context
     * @param array $queryParams
     * @param array $configuration
     * @return array
     */
    public function search(
        AbstractResourceRepository $repository,
        ResourceType $resourceType,
        Context $context,
        array $queryParams,
        array $configuration
    ): array {
        $attributes = isset($queryParams['attributes']) ? explode(',', $queryParams['attributes']) : [];
        $excludedAttributes = isset($queryParams['excludedAttributes'])
            ? explode(',', $queryParams['excludedAttributes']) : [];
        $startIndex = isset($queryParams['startIndex']) ? (int)$queryParams['startIndex'] : 1;
        $itemsPerPage = isset($queryParams['itemsPerPage']) ? (int)$queryParams['itemsPerPage'] : 10;

        [$totalResults, $result] = $repository->search(
            $queryParams,
            $configuration['mapping'],
            $configuration['meta'],
            (int)$configuration['pid']
        );

        if ($totalResults === 0) {
            throw new NoResourceFoundException($resourceType->value . ' not found');
        }

        $payload = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => $totalResults,
            'startIndex' => $startIndex,
            'itemsPerPage' => $itemsPerPage,
            'Resources' => []
        ];

        while ($resource = $result->fetchAssociative()) {
            $payload['Resources'][] = $this->dataToPayload(
                $resourceType,
                $context,
                $resource,
                $configuration,
                $attributes,
                $excludedAttributes
            );
        }

        return $payload;
    }

    /**
     * detail an resource
     *
     * @param AbstractResourceRepository $repository
     * @param ResourceType $resourceType
     * @param Context $context
     * @param string $resourceId
     * @param array $queryParams
     * @param array $configuration
     * @return array
     */
    public function read(
        AbstractResourceRepository $repository,
        ResourceType $resourceType,
        Context $context,
        string $resourceId,
        array $queryParams,
        array $configuration
    ): array {
        $attributes = isset($queryParams['attributes']) ? explode(',', $queryParams['attributes']) : [];
        $excludedAttributes = isset($queryParams['excludedAttributes'])
            ? explode(',', $queryParams['excludedAttributes']) : [];
        $resource = $repository->find($resourceId);

        if (!$resource) {
            throw new NoResourceFoundException($resourceType->value . ' not found');
        }

        return $this->dataToPayload(
            $resourceType,
            $context,
            $resource,
            $configuration,
            $attributes,
            $excludedAttributes
        );
    }

    /**
     * create an resource
     *
     * @param AbstractResourceRepository $repository
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function create(
        AbstractResourceRepository $repository,
        array $payload,
        array $configuration
    ): array {
        $data = $this->mappingService->payloadToData($payload, $configuration['mapping']);
        $resource = $repository->create($data, (int)$configuration['pid']);

        return $resource;
    }

    /**
     * update an resource
     *
     * @param AbstractResourceRepository $repository
     * @param string $resourceId
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function update(
        AbstractResourceRepository $repository,
        string $resourceId,
        array $payload,
        array $configuration
    ): array {
        $data = $this->mappingService->payloadToData($payload, $configuration['mapping']);
        $data = $repository->update($resourceId, $data);

        return $data;
    }

    /**
     * patch  an resource
     *
     * @param AbstractResourceRepository $repository
     * @param string $resourceId
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function patch(
        AbstractResourceRepository $repository,
        string $resourceId,
        array $payload,
        array $configuration
    ): array {
        $record = $repository->find($resourceId);
        $data = $this->patchService->apply($record, $payload, $configuration['mapping'], $configuration['meta']);
        if (!empty($data)) {
            $record = $repository->update($resourceId, $data);
        }

        return $record;
    }

    /**
     * delete  an resource
     *
     * @param AbstractResourceRepository $repository
     * @param string $resourceId
     * @return void
     */
    public function delete(AbstractResourceRepository $repository, string $resourceId): void
    {
        $repository->delete($resourceId);
    }

    /**
     * map an resource
     *
     * @param ResourceType $resourceType
     * @param array $resource
     * @param array $configuration
     * @param array $attributes
     * @param array $excludedAttributes
     * @return array
     */
    public function dataToPayload(
        ResourceType $resourceType,
        Context $context,
        array $resource,
        array $configuration,
        array $attributes = [],
        array $excludedAttributes = []
    ): array {
        /** @var NormalizedParams */
        $normalizedParams = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams');

        $data = $this->mappingService->dataToPayload(
            $resource,
            $configuration['mapping'],
            $attributes,
            $excludedAttributes,
            $context
        );

        $apiPath = $this->extensionConfiguration->get('scim', 'be_path') . $resourceType->value . '/';

        $createdField = $configuration['meta']['created']['mapOn'] ?? 'crdate';
        $lastModifiedField = $configuration['meta']['lastModified']['mapOn'] ?? 'tstamp';

        $data['schemas'] = ['urn:ietf:params:scim:schemas:core:2.0:' . $resourceType->value];
        $data['id'] = $resource['scim_id'];
        $data['meta'] = [
            'resourceType' => $resourceType->value,
            'created' => \DateTime::createFromFormat('U', (string)$resource[$createdField])->format('c'),
            'lastModified' => \DateTime::createFromFormat('U', (string)$resource[$lastModifiedField])->format('c'),
            'location' => trim($normalizedParams->getSiteUrl(), '/') . $apiPath . $resource['scim_id'],
            'version' => 'W/"' . md5((string)$resource['tstamp']) . '"',
        ];

        return $data;
    }
}
