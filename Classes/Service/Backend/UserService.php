<?php

declare(strict_types=1);

namespace Ameos\Scim\Service\Backend;

use Ameos\Scim\Domain\Repository\BackendUserRepository;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Enum\PostPersistMode;
use Ameos\Scim\Enum\ResourceType;
use Ameos\Scim\Event\PostDeleteUserEvent;
use Ameos\Scim\Event\PostPersistUserEvent;
use Ameos\Scim\Service\ResourceService;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserService
{
    /**
     * @param ResourceService $resourceService
     * @param BackendUserRepository $backendUserRepository
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private readonly ResourceService $resourceService,
        private readonly BackendUserRepository $backendUserRepository,
        private readonly EventDispatcherInterface $eventDispatcher
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
        return $this->resourceService->search(
            $this->backendUserRepository,
            ResourceType::User,
            Context::Backend,
            $queryParams,
            $configuration
        );
    }

    /**
     * detail an user
     *
     * @param string $resourceId
     * @param array $queryParams
     * @param array $configuration
     * @return array
     */
    public function read(string $resourceId, array $queryParams, array $configuration): array
    {
        return $this->resourceService->read(
            $this->backendUserRepository,
            ResourceType::User,
            Context::Backend,
            $resourceId,
            $queryParams,
            $configuration
        );
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
        $resource = $this->resourceService->create(
            $this->backendUserRepository,
            $payload,
            $configuration
        );

        $this->eventDispatcher->dispatch(new PostPersistUserEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Create,
            Context::Backend
        ));

        return $this->read($resource['scim_id'], [], $configuration);
    }

    /**
     * update an user
     *
     * @param string $resourceId
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function update(string $resourceId, array $payload, array $configuration): array
    {
        $resource = $this->resourceService->update(
            $this->backendUserRepository,
            $resourceId,
            $payload,
            $configuration
        );

        $this->eventDispatcher->dispatch(new PostPersistUserEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Update,
            Context::Backend
        ));

        return $this->read($resourceId, [], $configuration);
    }

    /**
     * patch  an user
     *
     * @param string $resourceId
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function patch(string $resourceId, array $payload, array $configuration): array
    {
        $resource = $this->resourceService->patch(
            $this->backendUserRepository,
            $resourceId,
            $payload,
            $configuration
        );

        $this->eventDispatcher->dispatch(new PostPersistUserEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Patch,
            Context::Backend
        ));

        return $this->read($resourceId, [], $configuration);
    }

    /**
     * delete  an user
     *
     * @param string $resourceId
     * @param array $configuration
     * @return array
     */
    public function delete(string $resourceId, array $configuration): void
    {
        $this->resourceService->delete($this->backendUserRepository, $resourceId);
        $this->eventDispatcher->dispatch(new PostDeleteUserEvent(
            $resourceId,
            $configuration['mapping'],
            Context::Backend
        ));
    }
}
