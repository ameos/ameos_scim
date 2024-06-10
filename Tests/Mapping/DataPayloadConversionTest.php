<?php

declare(strict_types=1);

namespace Ameos\Scim\Test\Mapping;

use Ameos\Scim\CustomObject\MultiValuedObject;
use Ameos\Scim\Enum\Context;
use Ameos\Scim\Service\MappingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DataPayloadConversionTest extends UnitTestCase
{
    /**
     * Data and payload provider
     *
     * Every array splits into => [
     * - Mapping
     * - Payload
     * - Data
     * ]
     * @return array
     */
    public static function dataAndPayloadProvider(): array
    {
        return [
            'create user' => [
                [
                    'mapping' => [
                        'userName' => ['mapOn' => 'username'],
                        'name.formatted' => ['mapOn' => 'name'],
                        'name.familyName' => ['mapOn' => 'last_name'],
                        'name.givenName' => ['mapOn' => 'first_name'],
                        'name.middleName' => ['mapOn' => 'middle_name'],
                        'externalId' => ['mapOn' => 'scim_external_id'],
                        'title' => ['mapOn' => 'title'],
                        'userType' => ['value' => 'external'],
                        'active' => ['mapOn' => 'disable', 'cast' => 'bool', 'toggle' => true],
                        'emails' => [
                            'object' => MultiValuedObject::class,
                            'arguments' => [
                                [
                                    'primary' => ['value' => true],
                                    'value' => ['mapOn' => 'email']
                                ]
                            ]
                        ],
                        'phoneNumbers' => [
                            'object' => MultiValuedObject::class,
                            'arguments' => [
                                [
                                    'type' => ['value' => 'telephone'],
                                    'value' => ['mapOn' => 'telephone']
                                ],
                                [
                                    'type' => ['value' => 'fax'],
                                    'value' => ['mapOn' => 'fax'],
                                ]
                            ]
                        ]
                    ],
                    'meta' => []
                ],
                [
                    'UserName' => 'username-test',
                    'Active' => true,
                    'externalId' => '215487-54798',
                    'name' => [
                        'formatted' => 'Jean Dupont',
                        'familyName' => 'Dupont',
                        'givenName' => 'Jean'
                    ],
                    'emails' => [
                        [
                            'Primary' => true,
                            'value' => 'jean@dupont.com'
                        ]
                    ],
                    'PhoneNumbers' => [
                        [
                            'type' => 'fax',
                            'value' => '0329547896'
                        ]
                    ]
                ],
                [
                    'username' => 'username-test',
                    'disable' => false,
                    'name' => 'Jean Dupont',
                    'last_name' => 'Dupont',
                    'first_name' => 'Jean',
                    'email' => 'jean@dupont.com',
                    'fax' => '0329547896',
                    'scim_external_id' => '215487-54798'
                ]
            ]
        ];
    }

    /**
     * test mapping function
     *
     *  @param array $mapping
     * @param array $payload
     * @param array $data
     */
    #[DataProvider('dataAndPayloadProvider')]
    #[Test]
    public function payloadToData(array $mapping, array $payload, array $data): void
    {
        $mappingService = new MappingService();

        self::assertEquals(
            $data,
            $mappingService->payloadToData(
                $payload,
                $mapping['mapping']
            )
        );
    }

    /**
     * test mapping function
     *
     * @param array $mapping
     * @param array $payload
     * @param array $data
     */
    #[DataProvider('dataAndPayloadProvider')]
    #[Test]
    public function dataToPayload(array $mapping, array $payload, array $data): void
    {
        $mappingService = new MappingService();
        $payload = self::arrayChangeKeyRecursive($payload);

        self::assertEquals(
            $payload,
            $mappingService->dataToPayload(
                $data,
                $mapping['mapping'],
                [],
                [],
                Context::Frontend
            )
        );
    }

    private static function arrayChangeKeyRecursive($array, $case = CASE_LOWER)
    {
        return array_map(
            fn ($a) => is_array($a) ? self::arrayChangeKeyRecursive($a, $case) : $a,
            array_change_key_case($array, $case)
        );
    }
}
