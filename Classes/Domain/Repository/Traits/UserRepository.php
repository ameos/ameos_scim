<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository\Traits;

use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;

trait UserRepository
{
    /**
     * return user by group
     *
     * @param int $groupId
     * @param string $userTable
     * @param bool $withDeleted
     * @return Result
     */
    public function findByUserGroupWithTables(int $groupId, string $userTable, bool $withDeleted = false): Result
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($userTable);
        if ($withDeleted) {
            $qb->getRestrictions()
                ->removeByType(HiddenRestriction::class)
                ->removeByType(DeletedRestriction::class);
        }

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
