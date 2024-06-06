<?php

declare(strict_types=1);

namespace Ameos\Scim\CustomObject;

use Ameos\Scim\Enum\Context;
use Ameos\Scim\Exception\BadRequestException;
use Ameos\Scim\Exception\NotImplementedException;
use Tmilos\Lexer\Error\UnknownTokenException;
use Tmilos\ScimFilterParser\Ast\Conjunction;
use Tmilos\ScimFilterParser\Ast\Disjunction;
use Tmilos\ScimFilterParser\Mode;
use Tmilos\ScimFilterParser\Parser;

class MultiValuedObject implements CustomObjectInterface
{
    /**
     * return payload for $data
     *
     * @param array $data
     * @param array $configuration
     * @param Context $context
     */
    public function read(array $data, array $configuration, Context $context)
    {
        $evaluation = [];
        foreach ($configuration as $configurationItem) {
            $item = [];
            foreach ($configurationItem as $property => $propertyConfiguration) {
                $value = null;
                if (isset($propertyConfiguration['mapOn'])) {
                    $value = $data[$propertyConfiguration['mapOn']];
                } elseif (isset($propertyConfiguration['value'])) {
                    $value = $propertyConfiguration['value'];
                }
                if ($value) {
                    $item[$property] = $value;
                }
            }

            if (isset($item['value'])) {
                $evaluation[] = $item;
            }
        }

        return $evaluation;
    }

    /**
     * return update $data array
     *
     * @param array $payload
     * @param array $data
     * @param array $configuration
     * @return array
     */
    public function write(array $payload, array $data, array $configuration): array
    {
        foreach ($configuration as $item) {
            $matchingProperty = [];
            $mapOn = null;
            foreach ($item as $property => $propertyConfiguration) {
                if (isset($propertyConfiguration['value'])) {
                    $matchingProperty[$property] = $propertyConfiguration['value'];
                }
                if (isset($propertyConfiguration['mapOn'])) {
                    $mapOn = $propertyConfiguration['mapOn'];
                }
            }

            if ($mapOn) {
                foreach ($payload as $row) {
                    $value = $row['value'];
                    unset($row['value']);
                    if (empty(array_diff($matchingProperty, $row)) && empty(array_diff($row, $matchingProperty))) {
                        $data[$mapOn] = $value;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * return fields associate to properties
     *
     * @param array $configuration
     * @param string $filter
     * @return array|false
     */
    public function getAssociateFields(array $configuration, ?string $filter): array|false
    {
        $fields = [];

        foreach ($configuration['arguments'] as $argument) {
            if (isset($argument['value']['mapOn'])) {
                if ($filter === null || $this->filterIsValid($filter, $argument)) {
                    $fields[] = $argument['value']['mapOn'];
                }
            }
        }

        return empty($fields) ? false : $fields;
    }

    /**
     * check if filter is valid
     *
     * @param string $filter
     * @param array $argument
     * @return bool
     */
    private function filterIsValid(string $filter, array $argument): bool
    {
        $isValid = false;

        try {
            $parser = new Parser(Mode::FILTER());
            $node = $parser->parse($filter);
        } catch (UnknownTokenException $e) {
            throw new BadRequestException('Bad request : ' . $e->getMessage());
        }

        if (get_class($node) === Disjunction::class || get_class($node) === Conjunction::class) {
            throw new NotImplementedException('Filter not implemented');
        }

        /** @var ComparisonExpression $node */
        if (isset($argument[(string)$node->attributePath])) {
            $value = $argument[(string)$node->attributePath]['value'];
            $isValid = match ($node->operator) {
                'eq' => $node->compareValue === $value,
                'ne' => $node->compareValue !== $value,
                'co' => strpos($value, $node->compareValue) !== false,
                'sw' => str_starts_with($value, $node->compareValue),
                'ew' => str_ends_with($value, $node->compareValue),
                'pr' => true,
                'gt' => $node->compareValue < $value,
                'ge' => $node->compareValue <= $value,
                'lt' => $node->compareValue > $value,
                'le' => $node->compareValue >= $value,
            };
        }

        return $isValid;
    }
}
