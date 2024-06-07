<?php

declare(strict_types=1);

namespace Ameos\Scim\Service\Frontend;

use Ameos\Scim\Domain\Repository\FrontendGroupRepository;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Enum\PostPersistMode;
use Ameos\Scim\Enum\ResourceType;
use Ameos\Scim\Event\PostDeleteGroupEvent;
use Ameos\Scim\Event\PostPersistGroupEvent;
use Ameos\Scim\Service\ResourceService;
use Psr\EventDispatcher\EventDispatcherInterface;

class GroupService
{
    /**
     * @param ResourceService $resourceService
     * @param FrontendGroupRepository $frontendGroupRepository
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private readonly ResourceService $resourceService,
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
        return $this->resourceService->search(
            $this->frontendGroupRepository,
            ResourceType::Group,
            Context::Frontend,
            $queryParams,
            $configuration
        );
    }

    /**
     * detail an group
     *
     * @param string $resourceId
     * @param array $queryParams
     * @param array $configuration
     * @return array
     */
    public function read(string $resourceId, array $queryParams, array $configuration): array
    {
        return $this->resourceService->read(
            $this->frontendGroupRepository,
            ResourceType::Group,
            Context::Frontend,
            $resourceId,
            $queryParams,
            $configuration
        );
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
        $resource = $this->resourceService->create(
            $this->frontendGroupRepository,
            $payload,
            $configuration
        );

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Create,
            Context::Frontend
        ));

        return $this->read($resource['scim_id'], [], $configuration);
    }

    /**
     * update an group
     *
     * @param string $resourceId
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function update(string $resourceId, array $payload, array $configuration): array
    {
        $resource = $this->resourceService->update(
            $this->frontendGroupRepository,
            $resourceId,
            $payload,
            $configuration
        );

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Update,
            Context::Frontend
        ));

        return $this->read($resourceId, [], $configuration);
    }

    /**
     * patch  an group
     *
     * @param string $resourceId
     * @param array $payload
     * @param array $configuration
     * @return array
     */
    public function patch(string $resourceId, array $payload, array $configuration): array
    {
        $resource = $this->resourceService->patch(
            $this->frontendGroupRepository,
            $resourceId,
            $payload,
            $configuration
        );

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Patch,
            Context::Frontend
        ));

        return $this->read($resourceId, [], $configuration);
    }

    /**
     * delete  an group
     *
     * @param string $resourceId
     * @param array $configuration
     * @return array
     */
    public function delete(string $resourceId, array $configuration): void
    {
        $this->resourceService->delete($this->frontendGroupRepository, $resourceId);
        $this->eventDispatcher->dispatch(new PostDeleteGroupEvent(
            $resourceId,
            $configuration['mapping'],
            Context::Frontend
        ));
    }
}
