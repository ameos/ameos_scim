<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:scim/Resources/Private/Language/locallang_db.xlf:scim_access',
        'label' => 'name',
        'crdate' => 'createdon',
        'tstamp' => 'updatedon',
        'adminOnly' => true,
        'hideTable' => false,
        'rootLevel' => 1,
        'default_sortby' => 'name',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disabled',
        ],
        'searchFields' => 'name',
        'versioningWS_alwaysAllowLiveEdit' => true,
        'iconfile' => 'EXT:scim/Resources/Public/Icons/Extension.svg',
    ],
    'types' => [
        '1' => [
            'showitem' => 'disabled, name, secret',
        ],
    ],
    'palettes' => [],
    'columns' => [
        'name' => [
            'label' => 'LLL:EXT:scim/Resources/Private/Language/locallang_db.xlf:scim_access.name',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'secret' => [
            'label' => 'LLL:EXT:scim/Resources/Private/Language/locallang_db.xlf:scim_access.secret',
            'config' => [
                'type' => 'password',
                'required' => true,
                'fieldControl' => [
                    'passwordGenerator' => [
                        'renderType' => 'passwordGenerator',
                        'options' => [
                            'allowEdit' => false,
                            'passwordRules' => [
                                'length' => 40,
                                'random' => 'hex',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'disabled' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
    ],
];
