<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository\Traits;

use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;

trait HasGroupRepository
{
    /**
     * return user by group
     *
     * @param int $groupId
     * @param string $field
     * @param string $table
     * @param bool $withDeleted
     * @return Result
     */
    public function findByGroupWithFieldAndTable(
        int $groupId,
        string $field,
        string $table,
        bool $withDeleted = false
    ): Result {
        $qb = $this->connectionPool->getQueryBuilderForTable($table);
        $qb->getRestrictions()->removeByType(HiddenRestriction::class);
        if ($withDeleted) {
            $qb->getRestrictions()->removeByType(DeletedRestriction::class);
        }

        return $qb
            ->select('*')
            ->from($table)
            ->where(
                $qb->expr()->inSet(
                    $field,
                    $qb->createNamedParameter($groupId, Connection::PARAM_INT)
                )
            )
            ->executeQuery();
    }
}
