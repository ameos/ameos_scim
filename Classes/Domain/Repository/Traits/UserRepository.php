<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository\Traits;

use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Database\Connection;

trait UserRepository
{
    /**
     * return user by group
     *
     * @param int $groupId
     * @param string $userTable
     * @return Result
     */
    public function findByUserGroupWithTables(int $groupId, string $userTable): Result
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($userTable);
        return $qb
            ->select('*')
            ->from($userTable)
            ->where(
                $qb->expr()->inSet(
                    'usergroup',
                    $qb->createNamedParameter($groupId, Connection::PARAM_INT)
                )
            )
            ->executeQuery();
    }
}
