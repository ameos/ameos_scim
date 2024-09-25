<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\EventListener;

use Ameos\AmeosScim\CustomObject\MemberObject;
use Ameos\AmeosScim\Domain\Repository\BackendGroupRepository;
use Ameos\AmeosScim\Domain\Repository\BackendUserRepository;
use Ameos\AmeosScim\Domain\Repository\FrontendGroupRepository;
use Ameos\AmeosScim\Domain\Repository\FrontendUserRepository;
use Ameos\AmeosScim\Enum\Context;
use Ameos\AmeosScim\Event\PostDeleteGroupEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CleanUsersAfterGroupRemoved
{
    /**
     * @param FrontendUserRepository $frontendUserRepository
     * @param BackendUserRepository $backendUserRepository
     * @param FrontendGroupRepository $frontendGroupRepository
     * @param BackendGroupRepository $backendGroupRepository
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
        $results = $userRepository->findByGroup((int)$group['uid']);
        while ($user = $results->fetchAssociative()) {
            $data = [];
            foreach ($event->getMapping() as $configuration) {
                if (isset($configuration['object']) && $configuration['object'] === MemberObject::class) {
                    $field = $configuration['arguments']['field_user'];
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
