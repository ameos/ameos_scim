<?php

declare(strict_types=1);

namespace Ameos\Scim\Test;

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
    public static function findFieldProvider(): array
    {
        return [
            'one level property' => [
                [
                    'property1' => [
                        'mapOn' => 'field1'
                    ],
                ],
                'property1',
                [
                    'field1'
                ]
            ],
            'two level property' => [
                [
                    'property2' => [
                        'subproperty' => [
                            'mapOn' => 'field2'
                        ],
                    ],
                ],
                'property2.subproperty',
                [
                    'field2'
                ]
            ],
        ];
    }

    /**
     * test findField function
     *
     * @param array $mapping
     * @param string $property
     * @param array $expectedResult
     */
    #[DataProvider('findFieldProvider')]
    #[Test]
    public function findField(array $mapping, string $property, array $expectedResult): void
    {
        $mappingService = new MappingService();
        
        self::assertEquals($expectedResult, $mappingService->findField($property, $mapping));
    }
}
