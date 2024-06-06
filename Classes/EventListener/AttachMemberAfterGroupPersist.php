<?php

declare(strict_types=1);

namespace Ameos\Scim\EventListener;

use Ameos\Scim\Domain\Repository\BackendUserRepository;
use Ameos\Scim\Domain\Repository\FrontendUserRepository;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Enum\PostPersistMode;
use Ameos\Scim\Evaluator\MemberEvaluator;
use Ameos\Scim\Event\PostPersistGroupEvent;
use Ameos\Scim\Service\PatchService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class AttachMemberAfterGroupPersist
{
    /**
     * @param FrontendUserRepository $frontendUserRepository
     * @param BackendUserRepository $frontendUserRepository
     */
    public function __construct(
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly BackendUserRepository $backendUserRepository
    ) {
    }

    /**
     * attach member after persist
     *
     * @param PostPersistGroupEvent $event
     * @return void
     */
    public function __invoke(PostPersistGroupEvent $event): void
    {
        foreach ($event->getMapping() as $property => $configuration) {
            if (isset($configuration['callback']) && $configuration['callback'] === MemberEvaluator::class) {
                $payload = array_change_key_case($event->getPayload());
                $property = mb_strtolower($property);

                if (
                    $event->getMode() === PostPersistMode::Create || $event->getMode() === PostPersistMode::Update
                    && isset($payload[$property])
                    && is_array($payload[$property])
                ) {
                    $this->attachUsers($payload[$property], $configuration, $event);
                }

                if ($event->getMode() === PostPersistMode::Patch) {
                    foreach ($payload['operations'] as $operation) {
                        $operation = array_change_key_case($operation);
                        if ($property === $operation['path'] && $operation['op'] === PatchService::OP_ADD) {
                            $this->attachUsers($operation['value'], $configuration, $event);
                        }
                    }
                }
            }
        }
    }

    /**
     * attach users
     *
     * @param array $usersPayload
     * @param array $configuration
     * @param PostPersistGroupEvent $event
     * @return void
     */
    private function attachUsers(array $usersPayload, array $configuration, PostPersistGroupEvent $event): void
    {
        foreach ($usersPayload as $userPayload) {
            $repository = $event->getContext() === Context::Frontend
                ? $this->frontendUserRepository : $this->backendUserRepository;

            $user = $repository->read($userPayload['value']);
            if ($user) {
                $field = $configuration['arguments']['field'];
                $groups = array_filter(GeneralUtility::trimExplode(',', $user[$field]));
                if (!in_array((string)$event->getRecord()['uid'], $groups)) {
                    $groups[] = $event->getRecord()['uid'];
                    $repository->update($userPayload['value'], [$field => implode(',', $groups)]);
                }
            }
        }
    }
}
