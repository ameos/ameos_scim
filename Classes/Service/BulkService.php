<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Service;

use Ameos\AmeosScim\Domain\Repository\AbstractResourceRepository;
use Ameos\AmeosScim\Domain\Repository\BackendGroupRepository;
use Ameos\AmeosScim\Domain\Repository\BackendUserRepository;
use Ameos\AmeosScim\Domain\Repository\FrontendGroupRepository;
use Ameos\AmeosScim\Domain\Repository\FrontendUserRepository;
use Ameos\AmeosScim\Enum\Context;
use Ameos\AmeosScim\Enum\PostPersistMode;
use Ameos\AmeosScim\Enum\ResourceType;
use Ameos\AmeosScim\Event\PostDeleteGroupEvent;
use Ameos\AmeosScim\Event\PostDeleteUserEvent;
use Ameos\AmeosScim\Event\PostPersistGroupEvent;
use Ameos\AmeosScim\Event\PostPersistUserEvent;
use Ameos\AmeosScim\Exception\BadRequestException;
use Ameos\AmeosScim\Exception\NoResourceFoundException;
use Ameos\AmeosScim\Registry\BulkIdRegistry;
use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;

class BulkService
{
    /**
     * @var array
     */
    private array $events = [];

    /**
     * @param ResourceService $resourceService
     * @param BackendGroupRepository $backendGroupRepository
     * @param BackendUserRepository $backendUserRepository
     * @param FrontendGroupRepository $frontendGroupRepository
     * @param FrontendUserRepository $frontendUserRepository
     * @param ExtensionConfiguration $extensionConfiguration
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private readonly ResourceService $resourceService,
        private readonly BackendGroupRepository $backendGroupRepository,
        private readonly BackendUserRepository $backendUserRepository,
        private readonly FrontendGroupRepository $frontendGroupRepository,
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly BulkIdRegistry $bulkIdRegistry,
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * execute bulk action
     *
     * @param array $payload
     * @param array $configuration
     * @param Context $context
     */
    public function execute(array $payload, array $configuration, Context $context): array
    {
        $this->events = [];

        $payload = array_change_key_case($payload);
        $resources = [];
        foreach ($payload['operations'] as $operation) {
            $operation = array_change_key_case($operation);
            $resources[] = match ($operation['method']) {
                RoutingService::HTTP_POST => $this->post($operation, $configuration, $context),
                RoutingService::HTTP_PUT => $this->put($operation, $configuration, $context),
                RoutingService::HTTP_PATCH => $this->patch($operation, $configuration, $context),
                RoutingService::HTTP_DELETE => $this->delete($operation, $configuration, $context),
            };
        }

        foreach ($this->events as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:BulkResponse'],
            'Operations' => $resources
        ];
    }

    /**
     * post action
     *
     * @param array $operation
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    private function post(array $operation, array $configuration, Context $context): array
    {
        $resourceType = $this->extractTypeFromPath($operation['path']);

        $resource = $this->resourceService->create(
            $this->getRepository($context, $resourceType),
            $operation['data'],
            $this->getConfiguration($configuration, $resourceType)
        );

        $this->bulkIdRegistry->addResource($operation['bulkid'], $resource['scim_id'], $resourceType);

        if ($resourceType === ResourceType::User) {
            $this->events[] = new PostPersistUserEvent(
                $this->getConfiguration($configuration, $resourceType),
                $operation['data'],
                $resource,
                PostPersistMode::Create,
                $context
            );
        }
        if ($resourceType === ResourceType::Group) {
            $this->events[] = new PostPersistGroupEvent(
                $this->getConfiguration($configuration, $resourceType),
                $operation['data'],
                $resource,
                PostPersistMode::Create,
                $context
            );
        }

        return [
            'location' => $this->getResourceEndpoint($resource['scim_id'], $context, $resourceType),
            'method' => 'POST',
            'bulkId' => $operation['bulkid'],
            'version' => 'W/"' . md5((string)$resource['tstamp']) . '"',
            'status' => '201',
        ];
    }

    /**
     * put action
     *
     * @param array $operation
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    private function put(array $operation, array $configuration, Context $context): array
    {
        $resourceType = $this->extractTypeFromPath($operation['path']);

        $resourceId = null;
        if (preg_match('/' . $resourceType->value . 's\/(.*)/', $operation['path'], $match)) {
            $resourceId = $match[1];
        }

        if ($resourceId === null) {
            throw new NoResourceFoundException($resourceType->value . ' not found');
        }

        $resource = $this->resourceService->update(
            $this->getRepository($context, $resourceType),
            $resourceId,
            $operation['data'],
            $this->getConfiguration($configuration, $resourceType)
        );


        if (isset($operation['bulkid'])) {
            $this->bulkIdRegistry->addResource($operation['bulkid'], $resource['scim_id'], $resourceType);
        }

        if ($resourceType === ResourceType::User) {
            $this->events[] = new PostPersistUserEvent(
                $this->getConfiguration($configuration, $resourceType),
                $operation['data'],
                $resource,
                PostPersistMode::Update,
                $context
            );
        }
        if ($resourceType === ResourceType::Group) {
            $this->events[] = new PostPersistGroupEvent(
                $this->getConfiguration($configuration, $resourceType),
                $operation['data'],
                $resource,
                PostPersistMode::Update,
                $context
            );
        }

        return [
            'location' => $this->getResourceEndpoint($resourceId, $context, $resourceType),
            'method' => 'PUT',
            'version' => 'W/"' . md5((string)$resource['tstamp']) . '"',
            'status' => '200',
        ];
    }

    /**
     * patch action
     *
     * @param array $operation
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    private function patch(array $operation, array $configuration, Context $context): array
    {
        $resourceType = $this->extractTypeFromPath($operation['path']);

        $resourceId = null;
        if (preg_match('/' . $resourceType->value . 's\/(.*)/', $operation['path'], $match)) {
            $resourceId = $match[1];
        }

        if ($resourceId === null) {
            throw new NoResourceFoundException($resourceType->value . ' not found');
        }

        $resource = $this->resourceService->patch(
            $this->getRepository($context, $resourceType),
            $resourceId,
            $operation['data'],
            $this->getConfiguration($configuration, $resourceType)
        );

        if (isset($operation['bulkid'])) {
            $this->bulkIdRegistry->addResource($operation['bulkid'], $resource['scim_id'], $resourceType);
        }

        if ($resourceType === ResourceType::User) {
            $this->events[] = new PostPersistUserEvent(
                $this->getConfiguration($configuration, $resourceType),
                $operation['data'],
                $resource,
                PostPersistMode::Patch,
                $context
            );
        }
        if ($resourceType === ResourceType::Group) {
            $this->events[] = new PostPersistGroupEvent(
                $this->getConfiguration($configuration, $resourceType),
                $operation['data'],
                $resource,
                PostPersistMode::Patch,
                $context
            );
        }

        return [
            'location' => $this->getResourceEndpoint($resourceId, $context, $resourceType),
            'method' => 'PATCH',
            'version' => 'W/"' . md5((string)$resource['tstamp']) . '"',
            'status' => '200',
        ];
    }

    /**
     * delete action
     *
     * @param array $operation
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    private function delete(array $operation, array $configuration, Context $context): array
    {
        $resourceType = $this->extractTypeFromPath($operation['path']);

        $resourceId = null;
        if (preg_match('/' . $resourceType->value . 's\/(.*)/', $operation['path'], $match)) {
            $resourceId = $match[1];
        }

        if ($resourceId === null) {
            throw new NoResourceFoundException($resourceType->value . ' not found');
        }

        $this->resourceService->delete($this->getRepository($context, $resourceType), $resourceId);
        if ($resourceType === ResourceType::User) {
            $this->events[] = new PostDeleteUserEvent(
                $resourceId,
                $this->getConfiguration($configuration, $resourceType)['mapping'],
                $context
            );
        }
        if ($resourceType === ResourceType::Group) {
            $this->events[] = new PostDeleteGroupEvent(
                $resourceId,
                $this->getConfiguration($configuration, $resourceType)['mapping'],
                $context
            );
        }

        return [
            'location' => $this->getResourceEndpoint($resourceId, $context, $resourceType),
            'method' => 'DELETE',
            'status' => '204',
        ];
    }

    /**
     * extract type from path
     *
     * @param string $path
     * @return ResourceType
     */
    private function extractTypeFromPath(string $path): ResourceType
    {
        if (stripos($path, 'Users') !== false) {
            return ResourceType::User;
        }
        if (stripos($path, 'Groups') !== false) {
            return ResourceType::Group;
        }
        throw new BadRequestException('Bulk request not valid');
    }

    /**
     * return repository
     *
     * @param Context $context
     * @param ResourceType $resourceType
     * @return AbstractResourceRepository
     */
    private function getRepository(Context $context, ResourceType $resourceType): AbstractResourceRepository
    {
        if ($resourceType === ResourceType::User) {
            return $context === Context::Frontend ? $this->frontendUserRepository : $this->backendUserRepository;
        }
        if ($resourceType === ResourceType::Group) {
            return $context === Context::Frontend ? $this->frontendGroupRepository : $this->backendGroupRepository;
        }
        throw new LogicException('Should not be reached');
    }

    /**
     * return resource endpoing
     *
     * @param string $resourceId
     * @param Context $context
     * @param ResourceType $resourceType
     * @return string
     */
    private function getResourceEndpoint(string $resourceId, Context $context, ResourceType $resourceType): string
    {
        /** @var NormalizedParams */
        $normalizedParams = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams');

        $pathConf = $context === Context::Frontend ? 'fe_path' : 'be_path';
        $apiPath = $this->extensionConfiguration->get('ameos_scim', $pathConf) . $resourceType->value . '/';

        return trim($normalizedParams->getSiteUrl(), '/') . $apiPath . $resourceId;
    }

    /**
     * return configuration for a resource type
     *
     * @param array $configuration
     * @param ResourceType $resourceType
     * @return array
     */
    private function getConfiguration(array $configuration, ResourceType $resourceType): array
    {
        return match ($resourceType) {
            ResourceType::Group => [
                'pid' => $configuration['pid'],
                'mapping' => $configuration['group']['mapping'],
                'meta' => $configuration['group']['meta'],
            ],
            ResourceType::User => [
                'pid' => $configuration['pid'],
                'mapping' => $configuration['user']['mapping'],
                'meta' => $configuration['user']['meta'],
            ],
            default => $configuration,
        };
    }
}
