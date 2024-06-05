<?php

declare(strict_types=1);

namespace Ameos\Scim\Service;

use Ameos\Scim\Evaluator\EvaluatorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MappingService
{
    /**
     * map data to a payload with a mapping configuration array
     *
     * @param array $data
     * @param array $mapping
     * @param array $attributes
     * @return array
     */
    public function dataToPayload(array $data, array $mapping, array $attributes = []): array
    {
        $payload = [];
        $attributes = array_map('mb_strtolower', $attributes);
        foreach ($mapping as $key => $configuration) {
            $key = mb_strtolower($key);
            if (empty($attributes) || in_array($key, $attributes)) {
                $item = [];
                $root = &$item;
                $keySplit = explode('.', $key);
                foreach ($keySplit as $keyPart) {
                    $item[$keyPart] = [];
                    $item = &$item[$keyPart];
                }

                $value = null;
                if (isset($configuration['mapOn'])) {
                    $value = $data[$configuration['mapOn']];
                }

                if (isset($configuration['callback'])) {
                    /** @var EvaluatorInterface */
                    $evaluator = GeneralUtility::makeInstance($configuration['callback']);
                    $value = $evaluator->retrieveResourceData($data, $configuration['arguments'] ?? []);
                }

                if ($value !== null && isset($configuration['cast'])) {
                    $value = match ($configuration['cast']) {
                        'bool' => (bool)$value,
                        'int' => (int)$value,
                        'string' => (string)$value
                    };
                }

                if ($value !== null && isset($configuration['toggle']) && $configuration['toggle']) {
                    $value = !$value;
                }

                if ($value !== null) {
                    $item = $value;
                    $payload = array_merge_recursive($payload, $root);
                }

                unset($item);
                unset($root);
            }
        }

        return $payload;
    }


    /**
     * map data to a payload with a mapping configuration array
     *
     * @param array $payload
     * @param array $mapping
     * @return array
     */
    public function payloadToData(array $payload, array $mapping): array
    {
        $data = [];
        foreach ($mapping as $key => $configuration) {
            $key = mb_strtolower($key);
            $payloadValue = $payload;

            foreach (explode('.', $key) as $keyPart) {
                if (is_array($payloadValue)) {
                    $payloadValue = array_change_key_case($payloadValue);
                    $payloadValue = $payloadValue[$keyPart] ?? null;
                }
            }

            if ($payloadValue !== null) {
                if (isset($configuration['toggle']) && $configuration['toggle']) {
                    $payloadValue = !$payloadValue;
                }

                if (isset($configuration['mapOn'])) {
                    $data[$configuration['mapOn']] = $payloadValue;
                }

                if (isset($configuration['callback']) && isset($payload[$key])) {
                    /** @var EvaluatorInterface */
                    $evaluator = GeneralUtility::makeInstance($configuration['callback']);
                    $data = $evaluator->setResourceData($payload[$key], $data, $configuration['arguments'] ?? []);
                }
            }
        }

        return $data;
    }

    /**
     * return field map on a property the mapping
     *
     * @param string $property
     * @param array $mapping
     * @return string|false
     */
    public function findField(string $property, array $mapping): string|false
    {
        $currentMapping = $mapping;
        foreach (explode('.', $property) as $propertyItem) {
            foreach ($currentMapping as $key => $value) {
                if (mb_strtolower($key) === mb_strtolower($propertyItem)) {
                    if (isset($value['mapOn'])) {
                        return $value['mapOn'];
                    }

                    $currentMapping = $value;
                    break;
                }
            }
        }

        return false;
    }
}
