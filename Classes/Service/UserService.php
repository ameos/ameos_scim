<?php

declare(strict_types=1);

namespace Ameos\Scim\Service;

use Ameos\Scim\Domain\Repository\AbstractResourceRepository;
use Ameos\Scim\Domain\Repository\BackendUserRepository;
use Ameos\Scim\Domain\Repository\FrontendUserRepository;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Enum\PostPersistMode;
use Ameos\Scim\Enum\ResourceType;
use Ameos\Scim\Event\PostDeleteUserEvent;
use Ameos\Scim\Event\PostPersistUserEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserService
{
    /**
     * @param ResourceService $resourceService
     * @param FrontendUserRepository $frontendUserRepository
     * @param BackendUserRepository $backendUserRepository
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private readonly ResourceService $resourceService,
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly BackendUserRepository $backendUserRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * list users
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
            ResourceType::User,
            $context,
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
     * @param Context $context
     * @return array
     */
    public function read(string $resourceId, array $queryParams, array $configuration, Context $context): array
    {
        return $this->resourceService->read(
            $this->getRepository($context),
            ResourceType::User,
            $context,
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

        $this->eventDispatcher->dispatch(new PostPersistUserEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Create,
            $context
        ));

        return $this->read($resource['scim_id'], [], $configuration, $context);
    }

    /**
     * update an user
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

        $this->eventDispatcher->dispatch(new PostPersistUserEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Update,
            $context
        ));

        return $this->read($resourceId, [], $configuration, $context);
    }

    /**
     * patch  an user
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

        $this->eventDispatcher->dispatch(new PostPersistUserEvent(
            $configuration,
            $payload,
            $resource,
            PostPersistMode::Patch,
            $context
        ));

        return $this->read($resourceId, [], $configuration, $context);
    }

    /**
     * delete  an user
     *
     * @param string $resourceId
     * @param array $configuration
     * @param Context $context
     * @return array
     */
    public function delete(string $resourceId, array $configuration, Context $context): void
    {
        $this->resourceService->delete($this->getRepository($context), $resourceId);
        $this->eventDispatcher->dispatch(new PostDeleteUserEvent(
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
        return $context === Context::Frontend ? $this->frontendUserRepository : $this->backendUserRepository;
    }
}
