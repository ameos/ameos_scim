<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\EventListener;

use Ameos\AmeosScim\CustomObject\MemberObject;
use Ameos\AmeosScim\Domain\Repository\BackendGroupRepository;
use Ameos\AmeosScim\Domain\Repository\FrontendGroupRepository;
use Ameos\AmeosScim\Enum\Context;
use Ameos\AmeosScim\Event\PostDeleteGroupEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CleanGroupsAfterGroupRemoved
{
    /**
     * @param FrontendGroupRepository $frontendGroupRepository
     * @param BackendGroupRepository $backendGroupRepository
     */
    public function __construct(
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
        $groupRepository = $event->getContext() === Context::Frontend
            ? $this->frontendGroupRepository : $this->backendGroupRepository;

        $removedGroup = $groupRepository->find($event->getRecordId(), true);
        $results = $groupRepository->findByGroup((int)$removedGroup['uid']);
        while ($group = $results->fetchAssociative()) {
            $data = [];
            foreach ($event->getMapping() as $configuration) {
                if (isset($configuration['object']) && $configuration['object'] === MemberObject::class) {
                    $field = $configuration['arguments']['field_group'];
                    $currentgroup = array_filter(GeneralUtility::trimExplode(',', $group[$field]));
                    $data[$field] = implode(
                        ',',
                        array_filter(
                            $currentgroup,
                            fn($g) => (int)$g !== (int)$removedGroup['uid']
                        )
                    );
                }
            }

            if (!empty($data)) {
                $groupRepository->update($group['scim_id'], $data);
            }
        }
    }
}
