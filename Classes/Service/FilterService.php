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
use TYPO3\CMS\Core\Error\Http\BadRequestException as HttpBadRequestException;

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
     * @return CompositeExpression
     */
    private function convertNode(Node $node, QueryBuilder $qb, array $mapping): CompositeExpression
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
     * @return CompositeExpression
     */
    private function comparaison(ComparisonExpression $node, QueryBuilder $qb, array $mapping): CompositeExpression
    {
        $fields = (string)$node->attributePath === 'id'
            ? ['scim_id'] : $this->mappingService->findField((string)$node->attributePath, $mapping);

        if (!$fields) {
            throw new HttpBadRequestException('Filter not valid');
        }

        $v = $node->compareValue;
        $comparisons = match ($node->operator) {
            'eq' => array_map(fn ($f) => $qb->expr()->eq($f, $qb->createNamedParameter($v)), $fields),
            'ne' => array_map(fn ($f) => $qb->expr()->neq($f, $qb->createNamedParameter($v)), $fields),
            'co' => array_map(fn ($f) => $qb->expr()->like($f, $qb->createNamedParameter('%' . $v . '%')), $fields),
            'sw' => array_map(fn ($f) => $qb->expr()->like($f, $qb->createNamedParameter($v . '%')), $fields),
            'ew' => array_map(fn ($f) => $qb->expr()->like($f, $qb->createNamedParameter('%' . $v)), $fields),
            'pr' => array_map(fn ($f) => $qb->expr()->notLike($f, $qb->createNamedParameter('')), $fields),
            'gt' => array_map(fn ($f) => $qb->expr()->gt($f, $qb->createNamedParameter($v)), $fields),
            'ge' => array_map(fn ($f) => $qb->expr()->gte($f, $qb->createNamedParameter($v)), $fields),
            'lt' => array_map(fn ($f) => $qb->expr()->lt($f, $qb->createNamedParameter($v)), $fields),
            'le' => array_map(fn ($f) => $qb->expr()->lte($f, $qb->createNamedParameter($v)), $fields),
        };
        return $qb->expr()->or(...$comparisons);
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
