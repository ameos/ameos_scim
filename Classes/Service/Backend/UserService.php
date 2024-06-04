<?php

declare(strict_types=1);

namespace Ameos\Scim\Service\Backend;

use Ameos\Scim\Domain\Repository\BackendUserRepository;
use Ameos\Scim\Exception\NoResourceFoundException;
use Ameos\Scim\Service\MappingService;
use Ameos\Scim\Service\PatchService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;

class UserService
{
    /**
     * @param MappingService $mappingService
     * @param PatchService $patchService
     * @param ExtensionConfiguration $extensionConfiguration
     * @param BackendUserRepository $backendUserRepository
     */
    public function __construct(
        private readonly MappingService $mappingService,
        private readonly PatchService $patchService,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly BackendUserRepository $backendUserRepository
    ) {
    }

    /**
     * list users
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

        [$totalResults, $result] = $this->backendUserRepository->search(
            $queryParams,
            $configuration['mapping'],
            (int)$configuration['pid']
        );

        if ($totalResults === 0) {
            throw new NoResourceFoundException('User not found');
        }

        $payload = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => $totalResults,
            'startIndex' => $startIndex,
            'itemsPerPage' => $itemsPerPage,
            'Resources' => []
        ];

        while ($user = $result->fetchAssociative()) {
            $payload['Resources'][] = $this->dataToPayload($user, $configuration['mapping'], $attributes);
        }

        return $payload;
    }

    /**
     * detail an user
     *
     * @param string $userId
     * @param array $queryParams
     * @param array $configuration
     * @return array
     */
    public function read(string $userId, array $queryParams, array $configuration): array
    {
        $attributes = isset($queryParams['attributes']) ? explode(',', $queryParams['attributes']) : [];
        $user = $this->backendUserRepository->read($userId);

        if (!$user) {
            throw new NoResourceFoundException('User not found');
        }

        return $this->dataToPayload($user, $configuration['mapping'], $attributes);
    }

    /**
     * create an user
     *
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function create(array $payload, array $configuration): array
    {
        $data = $this->mappingService->payloadToData($payload, $configuration['mapping']);
        $userId = $this->backendUserRepository->create($data, (int)$configuration['pid']);
        return $this->read($userId, [], $configuration);
    }

    /**
     * update an user
     *
     * @param string $userId
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function update(string $userId, array $payload, array $configuration): array
    {
        $data = $this->mappingService->payloadToData($payload, $configuration['mapping']);
        $this->backendUserRepository->update($userId, $data);
        return $this->read($userId, [], $configuration);
    }

    /**
     * patch  an user
     *
     * @param string $userId
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function patch(string $userId, array $payload, array $configuration): array
    {
        $record = $this->backendUserRepository->read($userId);
        $data = $this->patchService->apply($record, $payload, $configuration['mapping']);
        if (!empty($data)) {
            $this->backendUserRepository->update($userId, $data);
        }
        return $this->read($userId, [], $configuration);
    }

    /**
     * delete  an user
     *
     * @param string $userId
     * @return array
     */
    public function delete(string $userId): void
    {
        $this->backendUserRepository->delete($userId);
    }

    /**
     * map an user
     *
     * @param array $user
     * @param array $mapping
     * @param array $attributes
     * @return array
     */
    public function dataToPayload(array $user, array $mapping, array $attributes): array
    {
        /** @var NormalizedParams */
        $normalizedParams = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams');

        $data = $this->mappingService->dataToPayload($user, $mapping, $attributes);

        $apiPath = $this->extensionConfiguration->get('scim', 'be_path') . '/Users/';

        $data['schemas'] = ['urn:ietf:params:scim:schemas:core:2.0:User'];
        $data['id'] = $user['scim_id'];
        $data['meta'] = [
            'resourceType' => 'User',
            'created' => \DateTime::createFromFormat('U', (string)$user['crdate'])->format('c'),
            'lastModified' => \DateTime::createFromFormat('U', (string)$user['tstamp'])->format('c'),
            'location' => $normalizedParams->getSiteUrl() . $apiPath . $user['scim_id'],
            'version' => 'W/"' . md5((string)$user['tstamp']) . '"',
        ];

        return $data;
    }
}
