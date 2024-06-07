<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository;

use Doctrine\DBAL\Result;

class FrontendGroupRepository extends AbstractResourceRepository
{
    use Traits\HasGroupRepository;

    /**
     * return table name
     *
     * @return string
     */
    protected function getTable(): string
    {
        return 'fe_groups';
    }

    /**
     * return resource by group
     *
     * @param int $groupId
     * @param bool $withDeleted
     * @return Result
     */
    public function findByGroup(int $groupId, bool $withDeleted = false): Result
    {
        return $this->findByGroupWithFieldAndTable($groupId, 'subgroup', 'fe_groups', $withDeleted);
    }
}
