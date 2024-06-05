<?php

declare(strict_types=1);

namespace Ameos\Scim\Evaluator;

use Ameos\Scim\Enum\Context;
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
     * @param Context $context
     */
    public function retrieveResourceData(array $data, array $configuration, Context $context)
    {
        // TODO add $ref
        $table = $context === Context::Frontend ? 'fe_users' : 'be_users';
        $qb = $this->connectionPool->getQueryBuilderForTable($table);
        $users = $qb
            ->select('*')
            ->from($table)
            ->where(
                $qb->expr()->inSet(
                    $configuration['field'],
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
        // need group id, logic in post persist event with attach member listener
        return $data;
    }
}
