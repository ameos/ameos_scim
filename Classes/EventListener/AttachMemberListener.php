<?php

declare(strict_types=1);

namespace Ameos\Scim\EventListener;

use Ameos\Scim\Domain\Repository\BackendUserRepository;
use Ameos\Scim\Domain\Repository\FrontendUserRepository;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Enum\PostPersistMode;
use Ameos\Scim\Evaluator\MemberEvaluator;
use Ameos\Scim\Event\PostPersistGroupEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class AttachMemberListener
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
                if (
                    $event->getMode() === PostPersistMode::Create || $event->getMode() === PostPersistMode::Update
                    && isset($event->getPayload()[$property])
                    && is_array($event->getPayload()[$property])
                ) {
                    foreach ($event->getPayload()[$property] as $userPayload) {
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

                // TODO : patch
            }
        }
    }
}
