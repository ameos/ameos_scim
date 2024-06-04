<?php

declare(strict_types=1);

namespace Ameos\Scim\Evaluator;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class MemberEvaluator implements EvaluatorInterface
{
    public function __construct(private readonly ConnectionPool $connectionPool)
    {
    }

    /**
     * retrieve resource data
     *
     * @param array $data
     * @param array $configuration
     */
    public function retrieveResourceData(array $data, array $configuration)
    {
        // add $ref
        $qb = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $users = $qb
            ->select('*')
            ->from('fe_users')
            ->where(
                $qb->expr()->inSet(
                    'usergroup',
                    $qb->createNamedParameter((int)$data['uid'], Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            fn ($user) => ['value' => $user['scim_id'], 'display' => $user['username'], 'type' => 'User'],
            $users
        );
    }

    /**
     * set resource data
     *
     * @param array $payload
     * @param array $data
     * @param array $configuration
     */
    public function setResourceData(array $payload, array $data, array $configuration)
    {
        return $data;
    }
}
