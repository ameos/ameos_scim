<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die('Access denied');

ExtensionManagementUtility::addTCAcolumns(
    'fe_groups',
    [
        'scim_id' => [
            'label'  => 'LLL:EXT:ameos_scim/Resources/Private/Language/locallang.xlf:scim_id',
            'config' => [
                'type' => 'uuid',
                'version' => 6,
            ],
        ],
        'scim_external_id' => [
            'label'  => 'LLL:EXT:ameos_scim/Resources/Private/Language/locallang.xlf:scim_external_id',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
    ]
);

ExtensionManagementUtility::addToAllTCAtypes(
    'fe_groups',
    '--div--;LLL:EXT:ameos_scim/Resources/Private/Language/locallang.xlf:scim,scim_id,scim_external_id'
);
