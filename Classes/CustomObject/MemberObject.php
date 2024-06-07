<?php

declare(strict_types=1);

namespace Ameos\Scim\CustomObject;

use Ameos\Scim\Enum\Context;
use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $apiPath = $this->extensionConfiguration->get('scim', $confPath);

        $members = [];

        $table = $context === Context::Frontend ? 'fe_users' : 'be_users';
        $qb = $this->connectionPool->getQueryBuilderForTable($table);
        $results = $qb
            ->select('*')
            ->from($table)
            ->where(
                $qb->expr()->inSet(
                    $configuration['field_user'],
                    $qb->createNamedParameter((int)$data['uid'], Connection::PARAM_INT)
                )
            )
            ->executeQuery();
        while ($user = $results->fetchAssociative()) {
            $members[] = [
                'value' => $user['scim_id'],
                'display' => $user['username'],
                '$ref' => trim($normalizedParams->getSiteUrl(), '/') . $apiPath . 'Users/' . $user['scim_id'],
                'type' => 'User'
            ];
        }

        $table = $context === Context::Frontend ? 'fe_groups' : 'be_groups';
        $qb = $this->connectionPool->getQueryBuilderForTable($table);
        $results = $qb
            ->select('*')
            ->from($table)
            ->where(
                $qb->expr()->in(
                    'uid',
                    $qb->createNamedParameter(
                        array_map('intval', GeneralUtility::trimExplode(',', $data[$configuration['field_group']])),
                        ArrayParameterType::INTEGER
                    )
                )
            )
            ->executeQuery();
        while ($group = $results->fetchAssociative()) {
            $members[] = [
                'value' => $group['scim_id'],
                'display' => $group['title'],
                '$ref' => trim($normalizedParams->getSiteUrl(), '/') . $apiPath . 'Groups/' . $group['scim_id'],
                'type' => 'Group'
            ];
        }

        return $members;
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
