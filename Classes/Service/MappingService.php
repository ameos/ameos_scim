<?php

declare(strict_types=1);

namespace Ameos\Scim\Service;

use Ameos\Scim\CustomObject\CustomObjectInterface;
use Ameos\Scim\Enum\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MappingService
{
    /**
     * map data to a payload with a mapping configuration array
     *
     * @param array $data
     * @param array $mapping
     * @param array $attributes
     * @param array $excludedAttributes
     * @param Context $context
     * @return array
     */
    public function dataToPayload(
        array $data,
        array $mapping,
        array $attributes = [],
        array $excludedAttributes = [],
        Context $context = Context::Frontend
    ): array {
        $payload = [];
        $attributes = array_map('mb_strtolower', $attributes);
        foreach ($mapping as $key => $configuration) {
            $key = mb_strtolower($key);
            if ((empty($attributes) || in_array($key, $attributes)) && !in_array($key, $excludedAttributes)) {
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

                if (isset($configuration['object'])) {
                    /** @var EvaluatorInterface */
                    $customObject = GeneralUtility::makeInstance($configuration['object']);
                    $value = $customObject->read($data, $configuration['arguments'] ?? [], $context);
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

                if (isset($configuration['object']) && isset($payload[$key])) {
                    /** @var CustomObjectInterface */
                    $customObject = GeneralUtility::makeInstance($configuration['object']);
                    $data = $customObject->write($payload[$key], $data, $configuration['arguments'] ?? []);
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
     * @return array|false
     */
    public function findPropertyConfiguration(string $property, array $mapping): array|false
    {
        if (preg_match('/([^\[]*)\[([^\]]*)\]/', $property, $matches)) {
            $property = $matches[1];
        }

        $currentMapping = $mapping;
        foreach (explode('.', $property) as $propertyItem) {
            foreach ($currentMapping as $key => $value) {
                if (mb_strtolower($key) === mb_strtolower($propertyItem)) {
                    if (isset($value['mapOn']) || isset($value['object'])) {
                        return $value;
                    }

                    $currentMapping = $value;
                    break;
                }
            }
        }

        return false;
    }

    /**
     * return field map on a property the mapping
     *
     * @param string $property
     * @param array $mapping
     * @param array $meta
     * @return array|false
     */
    public function findFieldsCorrespondingProperty(string $property, array $mapping, array $meta): array|false
    {
        $filter = null;
        if (preg_match('/([^\[]*)\[([^\]]*)\]/', $property, $matches)) {
            $property = $matches[1];
            $filter = $matches[2];
        }

        $currentMapping = array_merge($mapping, $meta);
        foreach (explode('.', $property) as $propertyItem) {
            foreach ($currentMapping as $key => $configuration) {
                if (mb_strtolower($key) === mb_strtolower($propertyItem)) {
                    if (isset($configuration['mapOn'])) {
                        return [$configuration['mapOn']];
                    }

                    if (isset($configuration['object'])) {
                        /** @var CustomObjectInterface */
                        $customObject = GeneralUtility::makeInstance($configuration['object']);
                        return $customObject->getAssociateFields($configuration, $filter);
                    }

                    $currentMapping = $configuration;
                    break;
                }
            }
        }

        return false;
    }
}
