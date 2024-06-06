<?php

declare(strict_types=1);

namespace Ameos\Scim\Test;

use Ameos\Scim\CustomObject\MultiValuedObject;
use Ameos\Scim\Service\MappingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MappingTest extends UnitTestCase
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
                    'externalId' => [
                        'mapOn' => 'scim_external_id'
                    ],
                ],
                'externalId',
                ['scim_external_id']
            ],
            'two level property' => [
                [
                    'addresses' => [
                        'locality' => [
                            'mapOn' => 'city'
                        ],
                    ],
                ],
                'addresses.locality',
                ['city']
            ],
            'filter by type on complex objects' => [
                [
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
                'phoneNumbers[type eq "fax"]',
                ['fax']
            ],
            'filter by type on complex objects with mutli result' => [
                [
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
                'phoneNumbers[type sw "mobile"]',
                ['mobile_work', 'mobile_home']
            ],
            'filter by primary on complex objects' => [
                [
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
                'emails[primary eq true]',
                ['email']
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

        self::assertEquals($expectedResult, $mappingService->findFieldsCorrespondingProperty($property, $mapping));
    }
}
