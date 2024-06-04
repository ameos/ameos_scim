<?php

declare(strict_types=1);

use Ameos\Scim\Middleware\ScimRequirementMiddleware;
use Ameos\Scim\Middleware\ScimRoutingMiddleware;

return [
    'frontend' => [
        'ameos/scim/requirement' => [
            'target' => ScimRequirementMiddleware::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'typo3/cms-frontend/site',
            ]
        ],
        'ameos/scim/routing' => [
            'target' => ScimRoutingMiddleware::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
            ]
        ],
    ]
];
