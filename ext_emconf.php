<?php

$EM_CONF['scim'] = [
    'title' => 'SCIM Extension for TYPO3',
    'description' => 'SCIM Extension for TYPO3',
    'author' => 'AMEOS Team',
    'author_email' => 'typo3dev@ameos.com',
    'author_company' => 'AMEOS',
    'state' => 'alpha',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0-alpha',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
