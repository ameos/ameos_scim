<?php

declare(strict_types=1);

namespace Ameos\Scim\Evaluator;

use Ameos\Scim\Enum\Context;
use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GroupEvaluator implements EvaluatorInterface
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
        $apiPath = $this->extensionConfiguration->get('scim', $confPath) . 'Groups/';

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
            fn ($group) => [
                'value' => $group['scim_id'],
                'display' => $group['title'],
                '$ref' => rtrim($normalizedParams->getSiteUrl(), '/') . $apiPath . $group['scim_id'],
            ],
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
