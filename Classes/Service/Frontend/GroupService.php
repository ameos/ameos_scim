<?php

declare(strict_types=1);

namespace Ameos\Scim\Service\Frontend;

use Ameos\Scim\Domain\Repository\FrontendGroupRepository;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Enum\PostPersistMode;
use Ameos\Scim\Event\PostPersistGroupEvent;
use Ameos\Scim\Exception\NoResourceFoundException;
use Ameos\Scim\Service\MappingService;
use Ameos\Scim\Service\PatchService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;

class GroupService
{
    /**
     * @param MappingService $mappingService
     * @param PatchService $patchService
     * @param ExtensionConfiguration $extensionConfiguration
     * @param FrontendGroupRepository $frontendGroupRepository
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private readonly MappingService $mappingService,
        private readonly PatchService $patchService,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly FrontendGroupRepository $frontendGroupRepository,
        private readonly EventDispatcherInterface $eventDispatcher
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
        $excludedAttributes = isset($queryParams['excludedAttributes']) 
            ? explode(',', $queryParams['excludedAttributes']) : [];
        $startIndex = isset($queryParams['startIndex']) ? (int)$queryParams['startIndex'] : 1;
        $itemsPerPage = isset($queryParams['itemsPerPage']) ? (int)$queryParams['itemsPerPage'] : 10;

        [$totalResults, $result] = $this->frontendGroupRepository->search(
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
            $payload['Resources'][] = $this->datatopayload(
                $group,
                $configuration['mapping'],
                $attributes,
                $excludedAttributes
            );
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
        $excludedAttributes = isset($queryParams['excludedAttributes']) 
            ? explode(',', $queryParams['excludedAttributes']) : [];
        $group = $this->frontendGroupRepository->read($groupId);

        if (!$group) {
            throw new NoResourceFoundException('Group not found');
        }

        return $this->dataToPayload($group, $configuration['mapping'], $attributes, $excludedAttributes);
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
        $group = $this->frontendGroupRepository->create($data, (int)$configuration['pid']);

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $configuration['mapping'],
            $payload,
            $group,
            PostPersistMode::Create,
            Context::Frontend
        ));

        return $this->read($group['scim_id'], [], $configuration);
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
        $data = $this->frontendGroupRepository->update($groupId, $data);

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $configuration['mapping'],
            $payload,
            $data,
            PostPersistMode::Update,
            Context::Frontend
        ));

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
        $record = $this->frontendGroupRepository->read($groupId);
        $data = $this->patchService->apply($record, $payload, $configuration['mapping']);
        if (!empty($data)) {
            $data = $this->frontendGroupRepository->update($groupId, $data);

            $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
                $configuration['mapping'],
                $payload,
                $data,
                PostPersistMode::Patch,
                Context::Frontend
            ));
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
        $this->frontendGroupRepository->delete($groupId);
    }

    /**
     * map an group
     *
     * @param array $group
     * @param array $mapping
     * @param array $attributes
     * @param array $excludedAttributes
     * @return array
     */
    public function dataToPayload(
        array $group,
        array $mapping,
        array $attributes = [],
        array $excludedAttributes = []
    ): array {
        /** @var NormalizedParams */
        $normalizedParams = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams');

        $data = $this->mappingService->dataToPayload(
            $group,
            $mapping,
            $attributes,
            $excludedAttributes,
            Context::Frontend
        );

        $apiPath = $this->extensionConfiguration->get('scim', 'fe_path') . '/Groups/';

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
