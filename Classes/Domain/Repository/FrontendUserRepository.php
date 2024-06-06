<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository;

use Doctrine\DBAL\Result;

class FrontendUserRepository extends AbstractResourceRepository
{
    use Traits\UserRepository;

    /**
     * return table name
     *
     * @return string
     */
    protected function getTable(): string
    {
        return 'fe_users';
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
        return $this->findByUserGroupWithTables($groupId, 'fe_users', $withDeleted);
    }
}
