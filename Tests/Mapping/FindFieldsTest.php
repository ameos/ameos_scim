<?php

declare(strict_types=1);

namespace Ameos\Scim\Test\Mapping;

use Ameos\Scim\CustomObject\MultiValuedObject;
use Ameos\Scim\Service\MappingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FindFieldsTest extends UnitTestCase
{
    /**
     * Data provider for findField
     *
     * Every array splits into:
     * - Mapping
     * - Property for which we want the field
     * - Expected result array
     * @return array
     */
    public static function findFieldsCorrespondingPropertyProvider(): array
    {
        return [
            'one level property' => [
                [
                    'mapping' => [
                        'externalId' => [
                            'mapOn' => 'scim_external_id'
                        ],
                    ],
                    'meta' => [],
                ],
                'externalId',
                ['scim_external_id']
            ],
            'two level property' => [
                [
                    'mapping' => [
                        'addresses.locality' => [
                            'mapOn' => 'city'
                        ],
                    ],
                    'meta' => [],
                ],
                'addresses.locality',
                ['city']
            ],
            'filter by type on complex objects' => [
                [
                    'mapping' => [
                        'phoneNumbers' => [
                            'object' => MultiValuedObject::class,
                            'arguments' => [
                                [
                                    'type' => [
                                        'value' => 'telephone'
                                    ],
                                    'value' => [
                                        'mapOn' => 'telephone'
                                    ]
                                ],
                                [
                                    'type' => [
                                        'value' => 'fax'
                                    ],
                                    'value' => [
                                        'mapOn' => 'fax'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'meta' => [],
                ],
                'phoneNumbers[type eq "fax"]',
                ['fax']
            ],
            'filter by type on complex objects with mutli result' => [
                [
                    'mapping' => [
                        'phoneNumbers' => [
                            'object' => MultiValuedObject::class,
                            'arguments' => [
                                [
                                    'type' => [
                                        'value' => 'mobile-work'
                                    ],
                                    'value' => [
                                        'mapOn' => 'mobile_work'
                                    ]
                                ],
                                [
                                    'type' => [
                                        'value' => 'phone-work'
                                    ],
                                    'value' => [
                                        'mapOn' => 'phone_work'
                                    ]
                                ],
                                [
                                    'type' => [
                                        'value' => 'mobile-home'
                                    ],
                                    'value' => [
                                        'mapOn' => 'mobile_home'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'meta' => [],
                ],
                'phoneNumbers[type sw "mobile"]',
                ['mobile_work', 'mobile_home']
            ],
            'filter by primary on complex objects' => [
                [
                    'mapping' => [
                        'emails' => [
                            'object' => MultiValuedObject::class,
                            'arguments' => [
                                [
                                    'primary' => [
                                        'value' => true,
                                    ],
                                    'value' => [
                                        'mapOn' => 'email'
                                    ]
                                ],
                                [
                                    'type' => [
                                        'value' => 'other'
                                    ],
                                    'value' => [
                                        'mapOn' => 'other-mail'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'meta' => [],
                ],
                'emails[primary eq true]',
                ['email']
            ],
            'meta property' => [
                [
                    'mapping' => [],
                    'meta' => [
                        'lastModified' => [
                            'mapOn' => 'tstamp'
                        ]
                    ],
                ],
                'lastModified',
                ['tstamp']
            ],
        ];
    }

    /**
     * test findFields function
     *
     * @param array $mapping
     * @param string $property
     * @param array $expectedResult
     */
    #[DataProvider('findFieldsCorrespondingPropertyProvider')]
    #[Test]
    public function findFieldsCorrespondingProperty(array $mapping, string $property, array $expectedResult): void
    {
        $mappingService = new MappingService();

        self::assertEquals(
            $expectedResult,
            $mappingService->findFieldsCorrespondingProperty(
                $property,
                $mapping['mapping'],
                $mapping['meta']
            )
        );
    }
}
