<?php

declare(strict_types=1);

namespace Ameos\Scim\CustomObject;

use Ameos\Scim\Enum\Context;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class MemberObject implements CustomObjectInterface
{
    /**
     * @param ConnectionPool $connectionPool
     * @param ExtensionConfiguration $extensionConfiguration
     */
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    /**
     * return payload for $data
     *
     * @param array $data
     * @param array $configuration
     * @param Context $context
     */
    public function read(array $data, array $configuration, Context $context)
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
     * return update $data array
     *
     * @param array $payload
     * @param array $data
     * @param array $configuration
     * @return array
     */
    public function write(array $payload, array $data, array $configuration): array
    {
        return $data;
    }

    /**
     * return fields associate to properties
     *
     * @param array $configuration
     * @param string $filter
     * @return array|false
     */
    public function getAssociateFields(array $configuration, ?string $filter): array|false
    {
        return false;
    }
}
