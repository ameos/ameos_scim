<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Service;

use Ameos\AmeosScim\Exception\BadRequestException;
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
     * @param array $meta
     * @return array
     */
    public function convertFilter(string $filter, QueryBuilder $qb, array $mapping, array $meta): array
    {
        $constraints = [];

        try {
            $parser = new Parser(Mode::FILTER());
            $node = $parser->parse($filter);
        } catch (UnknownTokenException $e) {
            throw new BadRequestException('Bad request : ' . $e->getMessage());
        }

        $constraints[] = $this->convertNode($node, $qb, $mapping, $meta);

        return $constraints;
    }

    /**
     * convert node, return constraint
     *
     * @param Node $node
     * @param QueryBuilder $qb
     * @param array $mapping
     * @param array $meta
     * @return CompositeExpression
     */
    private function convertNode(Node $node, QueryBuilder $qb, array $mapping, array $meta): CompositeExpression
    {
        return match (get_class($node)) {
            ComparisonExpression::class => $this->comparaison($node, $qb, $mapping, $meta),
            Disjunction::class => $this->disjunction($node, $qb, $mapping, $meta),
            Conjunction::class => $this->conjunction($node, $qb, $mapping, $meta),
        };
    }

    /**
     * convert comparison, return constraint
     *
     * @param ComparisonExpression $node
     * @param QueryBuilder $qb
     * @param array $mapping
     * @param array $meta
     * @return CompositeExpression
     */
    private function comparaison(
        ComparisonExpression $node,
        QueryBuilder $qb,
        array $mapping,
        array $meta
    ): CompositeExpression {
        $fields = ['scim_id'];
        if ((string)$node->attributePath !== 'id') {
            $fields = $this->mappingService->findFieldsCorrespondingProperty(
                (string)$node->attributePath,
                $mapping,
                $meta
            );
        }

        if (!$fields) {
            throw new BadRequestException('Filter not valid');
        }

        $v = $node->compareValue;
        if (preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}.*/', $v)) {
            $v = (new \DateTime($v))->getTimestamp();
        }

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
     * @param array $meta
     * @return CompositeExpression
     */
    private function disjunction(Disjunction $node, QueryBuilder $qb, array $mapping, array $meta): CompositeExpression
    {
        return $qb->expr()->or(
            ...array_map(
                fn ($t) => $this->convertNode($t, $qb, $mapping, $meta),
                $node->getTerms()
            )
        );
    }

    /**
     * convert comparison, return constraint
     *
     * @param Conjunction $node
     * @param QueryBuilder $qb
     * @param array $mapping
     * @param array $meta
     * @return CompositeExpression
     */
    private function conjunction(Conjunction $node, QueryBuilder $qb, array $mapping, array $meta): CompositeExpression
    {
        return $qb->expr()->and(
            ...array_map(
                fn ($f) => $this->convertNode($f, $qb, $mapping, $meta),
                $node->getFactors()
            )
        );
    }
}
