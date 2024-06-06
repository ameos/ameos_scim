<?php

declare(strict_types=1);

namespace Ameos\Scim\EventListener;

use Ameos\Scim\Domain\Repository\BackendGroupRepository;
use Ameos\Scim\Domain\Repository\BackendUserRepository;
use Ameos\Scim\Domain\Repository\FrontendGroupRepository;
use Ameos\Scim\Domain\Repository\FrontendUserRepository;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Evaluator\MemberEvaluator;
use Ameos\Scim\Event\PostDeleteGroupEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CleanUsersAfterGroupRemoved
{
    /**
     * @param FrontendUserRepository $frontendUserRepository
     * @param BackendUserRepository $frontendUserRepository
     * @param FrontendGroupRepository $frontendGroupRepository
     * @param BackendGroupRepository $frontendGroupRepository
     */
    public function __construct(
        private readonly FrontendUserRepository $frontendUserRepository,
        private readonly BackendUserRepository $backendUserRepository,
        private readonly FrontendGroupRepository $frontendGroupRepository,
        private readonly BackendGroupRepository $backendGroupRepository
    ) {
    }

    /**
     * attach member after persist
     *
     * @param PostDeleteGroupEvent $event
     * @return void
     */
    public function __invoke(PostDeleteGroupEvent $event): void
    {
        $userRepository = $event->getContext() === Context::Frontend
            ? $this->frontendUserRepository : $this->backendUserRepository;

        $groupRepository = $event->getContext() === Context::Frontend
            ? $this->frontendGroupRepository : $this->backendGroupRepository;

        $group = $groupRepository->find($event->getRecordId(), true);
        $results = $userRepository->findByUserGroup((int)$group['uid']);
        while ($user = $results->fetchAssociative()) {
            $data = [];
            foreach ($event->getMapping() as $property => $configuration) {
                if (isset($configuration['callback']) && $configuration['callback'] === MemberEvaluator::class) {
                    $field = $configuration['arguments']['field'];
                    $usergroup = array_filter(GeneralUtility::trimExplode(',', $user[$field]));
                    $data[$field] = implode(',', array_filter($usergroup, fn($g) => (int)$g !== (int)$group['uid']));
                }
            }

            if (!empty($data)) {
                $userRepository->update($user['scim_id'], $data);
            }
        }
    }
}
