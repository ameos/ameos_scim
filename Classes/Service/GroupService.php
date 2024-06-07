<?php

declare(strict_types=1);

namespace Ameos\Scim\Service;

use Ameos\Scim\Domain\Repository\AbstractResourceRepository;
use Ameos\Scim\Domain\Repository\BackendGroupRepository;
use Ameos\Scim\Domain\Repository\FrontendGroupRepository;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Enum\PostPersistMode;
use Ameos\Scim\Enum\ResourceType;
use Ameos\Scim\Event\PostDeleteGroupEvent;
use Ameos\Scim\Event\PostPersistGroupEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class GroupService
{
    /**
     * @param ResourceService $resourceService
     * @param BackendGroupRepository $backendGroupRepository
     * @param FrontendGroupRepository $frontendGroupRepository
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private readonly ResourceService $resourceService,
        private readonly FrontendGroupRepository $frontendGroupRepository,
        private readonly BackendGroupRepository $backendGroupRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * list groups
     *
     * @param array $queryParams
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    public function search(array $queryParams, array $configuration, Context $context): array
    {
        return $this->resourceService->search(
            $this->getRepository($context),
            ResourceType::Group,
            $context,
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
     * @param Context $context
     * @return array
     */
    public function read(string $resourceId, array $queryParams, array $configuration, Context $context): array
    {
        return $this->resourceService->read(
            $this->getRepository($context),
            ResourceType::Group,
            $context,
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
     * @param Context $context
     * @return array
     */
    public function create(array $payload, array $configuration, Context $context): array
    {
        $resource = $this->resourceService->create(
            $this->getRepository($context),
            $payload,
            $configuration
        );

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Create,
            $context
        ));

        return $this->read($resource['scim_id'], [], $configuration, $context);
    }

    /**
     * update an group
     *
     * @param string $resourceId
     * @param array $payload
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    public function update(string $resourceId, array $payload, array $configuration, Context $context): array
    {
        $resource = $this->resourceService->update(
            $this->getRepository($context),
            $resourceId,
            $payload,
            $configuration
        );

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Update,
            $context
        ));

        return $this->read($resourceId, [], $configuration, $context);
    }

    /**
     * patch  an group
     *
     * @param string $resourceId
     * @param array $payload
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    public function patch(string $resourceId, array $payload, array $configuration, Context $context): array
    {
        $resource = $this->resourceService->patch(
            $this->getRepository($context),
            $resourceId,
            $payload,
            $configuration
        );

        $this->eventDispatcher->dispatch(new PostPersistGroupEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Patch,
            $context
        ));

        return $this->read($resourceId, [], $configuration, $context);
    }

    /**
     * delete  an group
     *
     * @param string $resourceId
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    public function delete(string $resourceId, array $configuration, Context $context): void
    {
        $this->resourceService->delete($this->getRepository($context), $resourceId);
        $this->eventDispatcher->dispatch(new PostDeleteGroupEvent(
            $resourceId,
            $configuration['mapping'],
            $context
        ));
    }

    /**
     * return repository
     *
     * @param Context $context
     * @return AbstractResourceRepository
     */
    private function getRepository(Context $context): AbstractResourceRepository
    {
        return $context === Context::Frontend ? $this->frontendGroupRepository : $this->backendGroupRepository;
    }
}
