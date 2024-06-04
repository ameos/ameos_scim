<?php

declare(strict_types=1);

namespace Ameos\Scim\Service\Backend;

use Ameos\Scim\Domain\Repository\BackendGroupRepository;
use Ameos\Scim\Exception\NoResourceFoundException;
use Ameos\Scim\Service\MappingService;
use Ameos\Scim\Service\PatchService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;

class GroupService
{
    /**
     * @param MappingService $mappingService
     * @param PatchService $patchService
     * @param ExtensionConfiguration $extensionConfiguration
     * @param BackendGroupRepository $backendGroupRepository
     */
    public function __construct(
        private readonly MappingService $mappingService,
        private readonly PatchService $patchService,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly BackendGroupRepository $backendGroupRepository
    ) {
    }

    /**
     * list groups
     *
     * @param array $queryParams
     * @param array $configuration
     * @return array
     */
    public function search(array $queryParams, array $configuration): array
    {
        $attributes = isset($queryParams['attributes']) ? explode(',', $queryParams['attributes']) : [];
        $startIndex = isset($queryParams['startIndex']) ? (int)$queryParams['startIndex'] : 1;
        $itemsPerPage = isset($queryParams['itemsPerPage']) ? (int)$queryParams['itemsPerPage'] : 10;

        [$totalResults, $result] = $this->backendGroupRepository->search(
            $queryParams,
            $configuration['mapping'],
            (int)$configuration['pid']
        );

        if ($totalResults === 0) {
            throw new NoResourceFoundException('Group not found');
        }

        $payload = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => $totalResults,
            'startIndex' => $startIndex,
            'itemsPerPage' => $itemsPerPage,
            'Resources' => []
        ];

        while ($group = $result->fetchAssociative()) {
            $payload['Resources'][] = $this->datatopayload($group, $configuration['mapping'], $attributes);
        }

        return $payload;
    }

    /**
     * detail an group
     *
     * @param string $groupId
     * @param array $queryParams
     * @param array $configuration
     * @return array
     */
    public function read(string $groupId, array $queryParams, array $configuration): array
    {
        $attributes = isset($queryParams['attributes']) ? explode(',', $queryParams['attributes']) : [];
        $group = $this->backendGroupRepository->read($groupId);

        if (!$group) {
            throw new NoResourceFoundException('Group not found');
        }

        return $this->dataToPayload($group, $configuration['mapping'], $attributes);
    }

    /**
     * create an group
     *
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function create(array $payload, array $configuration): array
    {
        $data = $this->mappingService->payloadToData($payload, $configuration['mapping']);
        $groupId = $this->backendGroupRepository->create($data, (int)$configuration['pid']);
        return $this->read($groupId, [], $configuration);
    }

    /**
     * update an group
     *
     * @param string $groupId
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function update(string $groupId, array $payload, array $configuration): array
    {
        $data = $this->mappingService->payloadToData($payload, $configuration['mapping']);
        $this->backendGroupRepository->update($groupId, $data);
        return $this->read($groupId, [], $configuration);
    }

    /**
     * patch  an group
     *
     * @param string $groupId
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function patch(string $groupId, array $payload, array $configuration): array
    {
        $record = $this->backendGroupRepository->read($groupId);
        $data = $this->patchService->apply($record, $payload, $configuration['mapping']);
        if (!empty($data)) {
            $this->backendGroupRepository->update($groupId, $data);
        }
        return $this->read($groupId, [], $configuration);
    }

    /**
     * delete  an group
     *
     * @param string $groupId
     * @return array
     */
    public function delete(string $groupId): void
    {
        $this->backendGroupRepository->delete($groupId);
    }

    /**
     * map an group
     *
     * @param array $group
     * @param array $mapping
     * @param array $attributes
     * @return array
     */
    public function dataToPayload(array $group, array $mapping, array $attributes): array
    {
        /** @var NormalizedParams */
        $normalizedParams = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams');

        $data = $this->mappingService->dataToPayload($group, $mapping, $attributes);

        $apiPath = $this->extensionConfiguration->get('scim', 'be_path') . '/Groups/';

        $data['schemas'] = ['urn:ietf:params:scim:schemas:core:2.0:Group'];
        $data['id'] = $group['scim_id'];
        $data['meta'] = [
            'resourceType' => 'group',
            'created' => \DateTime::createFromFormat('U', (string)$group['crdate'])->format('c'),
            'lastModified' => \DateTime::createFromFormat('U', (string)$group['tstamp'])->format('c'),
            'location' => $normalizedParams->getSiteUrl() . $apiPath . $group['scim_id'],
            'version' => 'W/"' . md5((string)$group['tstamp']) . '"',
        ];

        return $data;
    }
}
