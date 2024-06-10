<?php

declare(strict_types=1);

namespace Ameos\Scim\Test;

use Ameos\Scim\CustomObject\MultiValuedObject;
use Ameos\Scim\Service\MappingService;
use Ameos\Scim\Service\PatchService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PatchTest extends UnitTestCase
{
    /**
     * Data provider for test patch
     *
     * Every array splits into:
     * - Current record
     * - Payload
     * - Mapping
     * - Expected result
     * @return array
     */
    public static function dataProvider(): array
    {
        $mapping = [
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
        ];
        return [
            'replace last_name and username' => [
                [
                    'username' => 'jean-dupont',
                    'first_name' => 'Jean',
                    'last_name' => 'Dupont',
                    'tstamp' => time(),
                    'crdate' => time(),
                ],
                [
                    'schemas' => [
                        'urn:ietf:params:scim:api:messages:2.0:PatchOp'
                    ],
                    'Operations' => [
                        [
                            'op' => 'replace',
                            'path' => 'name.familyName',
                            'value' => 'Lamarche',
                        ],
                        [
                            'op' => 'replace',
                            'path' => 'userName',
                            'value' => 'jean-lamarche',
                        ]
                    ]
                ],
                $mapping,
                [
                    'last_name' => 'Lamarche',
                    'username' => 'jean-lamarche',
                ]
            ],
        ];
    }

    /**
     * test patch
     *
     * @param array $record
     * @param array $payload
     * @param array $mapping
     * @param array $expectedResult
     */
    #[DataProvider('dataProvider')]
    #[Test]
    public function applyPatch(array $record, array $payload, array $mapping, array $expectedResult): void
    {
        $mappingService = new MappingService();
        $patchService = new PatchService($mappingService);

        self::assertEquals(
            $expectedResult,
            $patchService->apply(
                $record,
                $payload,
                $mapping['mapping'],
                $mapping['meta']
            )
        );
    }
}
