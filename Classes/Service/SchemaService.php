<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Service;

use Ameos\AmeosScim\CustomObject\GroupObject;
use Ameos\AmeosScim\CustomObject\MemberObject;
use Ameos\AmeosScim\CustomObject\MultiValuedObject;
use Ameos\AmeosScim\Enum\Context;
use Ameos\AmeosScim\Enum\ResourceType;
use Ameos\AmeosScim\Event\ConvertMappingToSchemaEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class SchemaService
{
    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * convert mapping to attributes schema
     *
     * @param array $mapping
     * @param Context $context
     * @param ResourceType $resourceType
     * @return array
     */
    public function convertMapping(array $mapping, Context $context, ResourceType $resourceType): array
    {
        $attributes = [];

        foreach ($mapping as $property => $configuration) {
            $attributes = match ($configuration['object'] ?? 'Standard') {
                'Standard' => $this->mapStandard($property, $attributes),
                MultiValuedObject::class => $this->mapMultiValuedObject($property, $configuration, $attributes),
                MemberObject::class => $this->mapMemberObject($property, $configuration, $attributes),
                GroupObject::class => $this->mapGroupObject($property, $configuration, $attributes),
                default => $attributes
            };
        }

        $attributes = $this->eventDispatcher
            ->dispatch(new ConvertMappingToSchemaEvent($attributes, $mapping, $context, $resourceType))
            ->getSchema();

        return $attributes;
    }

    /**
     * map standard attribute
     *
     * @param string $property
     * @param array $attributes
     * @return array
     */
    private function mapStandard(string $property, array $attributes): array
    {
        $mainproperty = null;
        if (strpos($property, '.')) {
            [$mainproperty, $property] = explode('.', $property, 2);
        }

        $newAttribute = [
            'name' => $property,
            'type' => 'string',
            'multiValued' => false,
            'required' => false,
            'caseExact' => false,
            'mutability' => 'readWrite',
        ];


        if ($mainproperty !== null) {
            $mainAttribute = null;

            foreach ($attributes as $index => $attribute) {
                if ($attribute['name'] === $mainproperty) {
                    $mainAttribute = $attribute;
                    unset($attributes[$index]);
                }
            }

            if ($mainAttribute) {
                $mainAttribute['subAttributes'][] = $newAttribute;
                $attributes[] = $mainAttribute;
            } else {
                $attributes[] = [
                    'name' => $mainproperty,
                    'type' => 'complex',
                    'multiValued' => false,
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readWrite',
                    'subAttributes' => [$newAttribute],
                ];
            }
        } else {
            $attributes[] = $newAttribute;
        }

        $attributes = array_values($attributes);

        return $attributes;
    }

    /**
     * map multi valued object attribute
     *
     * @param string $property
     * @param array $configuration
     * @param array $attributes
     * @return array
     */
    private function mapMultiValuedObject(string $property, array $configuration, array $attributes): array
    {
        $attribute = [
            'name' => $property,
            'type' => 'complex',
            'multiValued' => false,
            'required' => false,
            'caseExact' => false,
            'mutability' => 'readWrite',
            'subAttributes' => [],
        ];

        $subAttributes = [];
        foreach ($configuration['arguments'] as $argument) {
            foreach ($argument as $subAttribute => $subArgument) {
                $type = isset($subArgument['value']) && is_bool($subArgument['value']) ? 'bool' : 'string';
                $subAttributes[$subAttribute] = [
                    'name' => $subAttribute,
                    'type' => $type,
                    'multiValued' => false,
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readWrite',
                ];
            }
        }
        $attribute['subAttributes'] = array_values($subAttributes);

        $attributes[] = $attribute;

        return $attributes;
    }

    /**
     * map member object
     *
     * @param string $property
     * @param array $configuration
     * @param array $attributes
     * @return array
     */
    private function mapMemberObject(string $property, array $configuration, array $attributes): array
    {
        $attributes[] = [
            'name' => $property,
            'type' => 'complex',
            'multiValued' => true,
            'required' => false,
            'caseExact' => false,
            'mutability' => 'readWrite',
            'subAttributes' => [
                [
                    'name' => 'value',
                    'type' => 'string',
                    'multiValued' => false,
                    'description' => 'The identifier of the member.',
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readOnly',
                    'returned' => 'default',
                    'uniqueness' => 'none',
                ],
                [
                    'name' => '$ref',
                    'type' => 'reference',
                    'referenceTypes' => ['Group', 'User'],
                    'multiValued' => false,
                    'description' => 'The URI of the corresponding resource',
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readOnly',
                    'returned' => 'default',
                    'uniqueness' => 'none',
                ],
                [
                    'name' => 'display',
                    'type' => 'string',
                    'multiValued' => false,
                    'description' => 'A human-readable name',
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readOnly',
                    'returned' => 'default',
                    'uniqueness' => 'none'
                ],
                [
                    'name' => 'type',
                    'type' => 'string',
                    'multiValued' => false,
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readOnly',
                    'returned' => 'default',
                    'uniqueness' => 'none',
                ]
            ],
        ];

        return $attributes;
    }

    /**
     * map group object
     *
     * @param string $property
     * @param array $configuration
     * @param array $attributes
     * @return array
     */
    private function mapGroupObject(string $property, array $configuration, array $attributes): array
    {
        $attributes[] = [
            'name' => $property,
            'type' => 'complex',
            'multiValued' => true,
            'required' => false,
            'caseExact' => false,
            'mutability' => 'readWrite',
            'subAttributes' => [
                [
                    'name' => 'value',
                    'type' => 'string',
                    'multiValued' => false,
                    'description' => 'The identifier of the User\'s group.',
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readOnly',
                    'returned' => 'default',
                    'uniqueness' => 'none',
                ],
                [
                    'name' => '$ref',
                    'type' => 'reference',
                    'referenceTypes' => ['Group'],
                    'multiValued' => false,
                    'description' => 'The URI of the corresponding \'Group\' resource to which the user belongs.',
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readOnly',
                    'returned' => 'default',
                    'uniqueness' => 'none',
                ],
                [
                    'name' => 'display',
                    'type' => 'string',
                    'multiValued' => false,
                    'description' => 'A human-readable name, primarily used for display purposes.  READ-ONLY.',
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readOnly',
                    'returned' => 'default',
                    'uniqueness' => 'none'
                ],
            ],
        ];

        return $attributes;
    }
}
