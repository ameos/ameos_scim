<?php

declare(strict_types=1);

namespace Ameos\Scim\Evaluator;

use Ameos\Scim\Enum\Context;

class MultiValuedEvaluator implements EvaluatorInterface
{
    /**
     * retrieve resource data
     *
     * @param array $data
     * @param array $configuration
     * @param Context $context
     */
    public function retrieveResourceData(array $data, array $configuration, Context $context)
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
     * set resource data
     *
     * @param array $payload
     * @param array $data
     * @param array $configuration
     */
    public function setResourceData(array $payload, array $data, array $configuration)
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
}
