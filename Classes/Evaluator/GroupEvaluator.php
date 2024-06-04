<?php

declare(strict_types=1);

namespace Ameos\Scim\Evaluator;

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GroupEvaluator implements EvaluatorInterface
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
        $usergroups = [];
        if (isset($data['usergroup'])) {
            // todo / be / fe
            $qb = $this->connectionPool->getQueryBuilderForTable('fe_groups');
            $usergroups = $qb
                ->select('*')
                ->from('fe_groups')
                ->where(
                    $qb->expr()->in(
                        'uid',
                        $qb->createNamedParameter(
                            GeneralUtility::trimExplode(',', $data['usergroup']),
                            ArrayParameterType::INTEGER
                        )
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();
        }

        return array_map(
            fn ($group) => ['value' => $group['scim_id'], 'display' => $group['title']],
            $usergroups
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
