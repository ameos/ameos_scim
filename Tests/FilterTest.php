<?php

declare(strict_types=1);

namespace Ameos\Scim\Test;

use Ameos\Scim\Service\FilterService;
use Ameos\Scim\Service\MappingService;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FilterTest extends UnitTestCase
{
    private ?QueryBuilder $queryBuilder;

    /**
     * Create a new database connection mock object for every test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->createMock(Connection::class);
        $concreteQueryBuilder = new DoctrineQueryBuilder($connection);
        $expressionBuilder = new ExpressionBuilder($connection);

        $connection->method('getExpressionBuilder')->willReturn($expressionBuilder);
        $connection->method('quoteIdentifier')->willReturnCallback(fn ($v) => $v);
        $this->queryBuilder = new QueryBuilder($connection, null, $concreteQueryBuilder);
    }

    /**
     * Data provider for filters
     *
     * Every array splits into:
     * - query builder
     * - filter
     * - Expected result
     * @return array
     */
    public static function filtersProvider(): array
    {
        return [
            'username eq "username-test"' => [
                'username eq "username-test"',
                [
                    'mapping' => [
                        'username' => [
                            'mapOn' => 'username'
                        ]
                    ],
                    'meta' => [],
                ],
                'username = :dcValue1'
            ],
            'userName ew "@ameos.com" AND externalId eq "123"' => [
                'userName ew "@ameos.com" AND externalId eq "123"',
                [
                    'mapping' => [
                        'username' => [
                            'mapOn' => 'username'
                        ],
                        'externalId' => [
                            'mapOn' => 'scim_external_id'
                        ]
                    ],
                    'meta' => [],
                ],
                '((username LIKE :dcValue1 ESCAPE ) AND (scim_external_id = :dcValue2))'
            ],
            'userName ew "@ameos.com" AND name.givenName sw "Jean"' => [
                'userName ew "@ameos.com" AND name.givenName sw "Jean"',
                [
                    'mapping' => [
                        'username' => [
                            'mapOn' => 'username'
                        ],
                        'name.givenName' => [
                            'mapOn' => 'first_name'
                        ],
                        'externalId' => [
                            'mapOn' => 'scim_external_id'
                        ]
                    ],
                    'meta' => [],
                ],
                '((username LIKE :dcValue1 ESCAPE ) AND (first_name LIKE :dcValue2 ESCAPE ))'
            ],
        ];
    }

    /**
     * test convert filter function
     *
     * @param string $filter
     * @param array $mapping
     * @param string $expectedResult
     */
    #[DataProvider('filtersProvider')]
    #[Test]
    public function convertFilter(string $filter, array $mapping, string $expectedResult): void
    {
        $mappingService = new MappingService();
        $filterService = new FilterService($mappingService);

        self::assertEquals(
            $expectedResult,
            (string)array_pop(
                $filterService->convertFilter(
                    $filter,
                    $this->queryBuilder,
                    $mapping['mapping'],
                    $mapping['meta']
                )
            )
        );
    }
}
