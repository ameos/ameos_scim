<?php

declare(strict_types=1);

namespace Ameos\Scim\Service;

use Ameos\Scim\Exception\BadRequestException;
use Tmilos\Lexer\Error\UnknownTokenException;
use Tmilos\ScimFilterParser\Ast\ComparisonExpression;
use Tmilos\ScimFilterParser\Ast\Conjunction;
use Tmilos\ScimFilterParser\Ast\Disjunction;
use Tmilos\ScimFilterParser\Ast\Node;
use Tmilos\ScimFilterParser\Mode;
use Tmilos\ScimFilterParser\Parser;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class FilterService
{
    /**
     * @param MappingService $mappingService
     */
    public function __construct(private readonly MappingService $mappingService)
    {
    }

    /**
     * convert filter, return constraints array
     *
     * @param string $filter
     * @param QueryBuilder $qb
     * @param array $mapping
     * @return array|false
     */
    public function convertFilter(string $filter, QueryBuilder $qb, array $mapping): array|false
    {
        $constraints = [];

        try {
            $parser = new Parser(Mode::FILTER());
            $node = $parser->parse($filter);
        } catch (UnknownTokenException $e) {
            throw new BadRequestException('Bad request : ' . $e->getMessage());
        }

        $constraints[] = $this->convertNode($node, $qb, $mapping);

        return empty($constraints) ? false : $constraints;
    }

    /**
     * convert node, return constraint
     *
     * @param Node $node
     * @param QueryBuilder $qb
     * @param array $mapping
     * @return CompositeExpression|string
     */
    private function convertNode(Node $node, QueryBuilder $qb, array $mapping): CompositeExpression|string
    {
        return match (get_class($node)) {
            ComparisonExpression::class => $this->comparaison($node, $qb, $mapping),
            Disjunction::class => $this->disjunction($node, $qb, $mapping),
            Conjunction::class => $this->conjunction($node, $qb, $mapping)
        };
    }

    /**
     * convert comparison, return constraint
     *
     * @param ComparisonExpression $node
     * @param QueryBuilder $qb
     * @param array $mapping
     * @return string
     */
    private function comparaison(ComparisonExpression $node, QueryBuilder $qb, array $mapping): string
    {
        $field = $this->mappingService->findField((string)$node->attributePath, $mapping);
        return match ($node->operator) {
            'eq' => $qb->expr()->eq($field, $qb->createNamedParameter($node->compareValue)),
            'ne' => $qb->expr()->neq($field, $qb->createNamedParameter($node->compareValue)),
            'co' => $qb->expr()->like($field, $qb->createNamedParameter('%' . $node->compareValue . '%')),
            'sw' => $qb->expr()->like($field, $qb->createNamedParameter($node->compareValue . '%')),
            'ew' => $qb->expr()->like($field, $qb->createNamedParameter('%' . $node->compareValue)),
            'pr' => $qb->expr()->notLike($field, $qb->createNamedParameter('')),
            'gt' => $qb->expr()->gt($field, $qb->createNamedParameter($node->compareValue)),
            'ge' => $qb->expr()->gte($field, $qb->createNamedParameter($node->compareValue)),
            'lt' => $qb->expr()->lt($field, $qb->createNamedParameter($node->compareValue)),
            'le' => $qb->expr()->lte($field, $qb->createNamedParameter($node->compareValue))
        };
    }

    /**
     * convert comparison, return constraint
     *
     * @param Disjunction $node
     * @param QueryBuilder $qb
     * @param array $mapping
     * @return CompositeExpression
     */
    private function disjunction(Disjunction $node, QueryBuilder $qb, array $mapping): CompositeExpression
    {
        return $qb->expr()->or(...array_map(fn ($t) => $this->convertNode($t, $qb, $mapping), $node->getTerms()));
    }

    /**
     * convert comparison, return constraint
     *
     * @param Conjunction $node
     * @param QueryBuilder $qb
     * @param array $mapping
     * @return CompositeExpression
     */
    private function conjunction(Conjunction $node, QueryBuilder $qb, array $mapping): CompositeExpression
    {
        return $qb->expr()->and(...array_map(fn ($f) => $this->convertNode($f, $qb, $mapping), $node->getFactors()));
    }
}
