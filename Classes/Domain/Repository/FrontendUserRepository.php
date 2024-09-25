<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Domain\Repository;

use Doctrine\DBAL\Result;

class FrontendUserRepository extends AbstractResourceRepository
{
    use Traits\HasGroupRepository;

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
     * return resource by group
     *
     * @param int $groupId
     * @param bool $withDeleted
     * @return Result
     */
    public function findByGroup(int $groupId, bool $withDeleted = false): Result
    {
        return $this->findByGroupWithFieldAndTable($groupId, 'usergroup', 'fe_users', $withDeleted);
    }
}
