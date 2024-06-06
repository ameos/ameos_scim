<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository;

use Doctrine\DBAL\Result;

class BackendUserRepository extends AbstractResourceRepository
{
    use Traits\UserRepository;

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
     * return user by group
     *
     * @param int $groupId
     * @param bool $withDeleted
     * @return Result
     */
    public function findByUserGroup(int $groupId, bool $withDeleted = false): Result
    {
        return $this->findByUserGroupWithTables($groupId, 'be_users', $withDeleted);
    }
}
