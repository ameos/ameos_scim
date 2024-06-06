<?php

declare(strict_types=1);

namespace Ameos\Scim\Evaluator;

use Ameos\Scim\Enum\Context;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;

class MemberEvaluator implements EvaluatorInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
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
        /** @var NormalizedParams */
        $normalizedParams = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams');
        $confPath = $context === Context::Frontend ? 'fe_path' : 'be_path';
        $apiPath = $this->extensionConfiguration->get('scim', $confPath) . 'Users/';

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
            fn ($user) => [
                'value' => $user['scim_id'],
                'display' => $user['username'],
                '$ref' => trim($normalizedParams->getSiteUrl(), '/') . $apiPath . $user['scim_id'],
                'type' => 'User'
            ],
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

    /**
     * return field
     *
     * @param array $configuration
     * @return string
     */
    public function getFields(array $configuration): ?array
    {
        return null;
    }
}
