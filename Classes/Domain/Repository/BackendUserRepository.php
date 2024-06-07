<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository;

use Doctrine\DBAL\Result;

class BackendUserRepository extends AbstractResourceRepository
{
    use Traits\HasGroupRepository;

    /**
     * return table name
     *
     * @return string
     */
    protected function getTable(): string
    {
        return 'be_users';
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
        return $this->findByGroupWithFieldAndTable($groupId, 'usergroup', 'be_users', $withDeleted);
    }
}
