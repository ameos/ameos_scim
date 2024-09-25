<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Domain\Repository;

use Ameos\AmeosScim\Service\FilterService;
use Ameos\AmeosScim\Service\MappingService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Result;
use Symfony\Component\Uid\UuidV6;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;

abstract class AbstractResourceRepository
{
    /**
     * @param MappingService $mappingService
     * @param FilterService $filterService
     * @param ConnectionPool $connectionPool
     */
    public function __construct(
        private readonly MappingService $mappingService,
        private readonly FilterService $filterService,
        protected readonly ConnectionPool $connectionPool
    ) {
    }

    /**
     * return table name
     *
     * @return string
     */
    abstract protected function getTable(): string;

    /**
     * list resources
     *
     * @param array $queryParams
     * @param array $mapping
     * @param array $meta
     * @param int $pid
     * @return array
     */
    public function search(array $queryParams, array $mapping, array $meta, int $pid): array
    {
        $startIndex = isset($queryParams['startIndex']) ? (int)$queryParams['startIndex'] : 1;
        $itemsPerPage = isset($queryParams['itemsPerPage']) ? (int)$queryParams['itemsPerPage'] : 10;
        $filters = $queryParams['filter'] ?? null;
        $sortBy = null;
        $sortOrder = 'ASC';

        if (isset($queryParams['sortBy'])) {
            $sortBy = $this->mappingService->findFieldsCorrespondingProperty($queryParams['sortBy'], $mapping, $meta);
        }
        if (isset($queryParams['sortOrder'])) {
            $sortOrder = $queryParams['sortOrder'] === 'ascending' ? 'ASC' : 'DESC';
        }

        return $this->findByFilters(
            $filters,
            $mapping,
            $meta,
            $pid,
            $startIndex,
            $itemsPerPage,
            $sortBy,
            $sortOrder
        );
    }

    /**
     * find by filters
     *
     * @param string $filters
     * @param array $mapping
     * @param array $meta
     * @param int $pid
     * @param int $startIndex
     * @param int $itemsPerPage
     * @param array $sortBy
     * @param string $sortOrder
     * @return array
     */
    public function findByFilters(
        ?string $filters,
        array $mapping,
        array $meta,
        int $pid = 0,
        int $startIndex = 1,
        int $itemsPerPage = 10,
        ?array $sortBy = null,
        string $sortOrder = 'ASC'
    ): array {
        $qb = $this->connectionPool->getQueryBuilderForTable($this->getTable());
        $qb->getRestrictions()->removeByType(HiddenRestriction::class);

        $constraints = [];
        $constraints[] = $qb->expr()->eq('pid', $qb->createNamedParameter($pid, Connection::PARAM_INT));

        if ($filters) {
            $filtersContraints = $this->filterService->convertFilter($filters, $qb, $mapping, $meta);
            if ($filtersContraints) {
                $constraints[] = $qb->expr()->and(...$filtersContraints);
            }
        }

        $totalResults = $qb
            ->count('uid')
            ->from($this->getTable())
            ->where(...$constraints)
            ->executeQuery()
            ->fetchOne();

        $qb
            ->select('*')
            ->setMaxResults($itemsPerPage)
            ->setFirstResult($startIndex - 1);

        if ($sortBy) {
            foreach ($sortBy as $sortByItem) {
                $qb->addOrderBy($sortByItem, $sortOrder);
            }
        } else {
            $qb->orderBy('uid', $sortOrder);
        }

        $result = $qb->executeQuery();

        return [$totalResults, $result];
    }

    /**
     * find by scim ids
     *
     * @param array $resourceIds
     * @return Result
     */
    public function findById(array $resourceIds): Result
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($this->getTable());
        $qb->getRestrictions()->removeByType(HiddenRestriction::class);
        return $qb
            ->select('*')
            ->from($this->getTable())
            ->where(
                $qb->expr()->in(
                    'scim_id',
                    $qb->createNamedParameter($resourceIds, ArrayParameterType::STRING)
                )
            )
            ->executeQuery();
    }

    /**
     * find a resource
     *
     * @param string $resourceId
     * @param bool $withDeleted
     * @return array|false
     */
    public function find(string $resourceId, bool $withDeleted = false): array|false
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($this->getTable());
        $qb->getRestrictions()->removeByType(HiddenRestriction::class);
        if ($withDeleted) {
            $qb->getRestrictions()->removeByType(DeletedRestriction::class);
        }
        return $qb
            ->select('*')
            ->from($this->getTable())
            ->where($qb->expr()->eq('scim_id', $qb->createNamedParameter($resourceId)))
            ->executeQuery()
            ->fetchAssociative();
    }

    /**
     * create a resource
     *
     * @param array $data
     * @param int $pid
     * @return array
     */
    public function create(array $data, int $pid): array
    {
        $data['scim_id'] = UuidV6::generate();
        $data['crdate'] = time();
        $data['tstamp'] = time();
        $data['pid'] = $pid;
        $connection = $this->connectionPool->getConnectionForTable($this->getTable());
        $connection->insert($this->getTable(), $data);
        $data['uid'] = (int)$connection->lastInsertId($this->getTable());
        return $data;
    }

    /**
     * update a resource
     *
     * @param string $resourceId
     * @param array $data
     * @return array
     */
    public function update(string $resourceId, array $data): array
    {
        $data['tstamp'] = time();
        $connection = $this->connectionPool->getConnectionForTable($this->getTable());
        $connection->update($this->getTable(), $data, ['scim_id' => $resourceId]);
        return $this->find($resourceId);
    }

    /**
     * delete a resource
     *
     * @param string $resourceId
     * @return void
     */
    public function delete(string $resourceId): void
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($this->getTable());
        $qb->getRestrictions()->removeByType(HiddenRestriction::class);
        $qb
            ->update($this->getTable())
            ->set('deleted', 1, true, Connection::PARAM_INT)
            ->where($qb->expr()->eq('scim_id', $qb->createNamedParameter($resourceId)))
            ->executeStatement();
    }


    /**
     * return resource by group
     *
     * @param int $groupId
     * @param bool $withDeleted
     * @return Result
     */
    abstract public function findByGroup(int $groupId, bool $withDeleted = false): Result;
}
