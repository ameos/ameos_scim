<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Event;

use Ameos\AmeosScim\Enum\Context;
use Ameos\AmeosScim\Enum\ResourceType;

final class ConvertMappingToSchemaEvent
{
    /**
     * @param array $schema
     * @param array $mapping
     * @param Context $context
     * @param ResourceType $resourceType
     */
    public function __construct(
        private array $schema,
        private readonly array $mapping,
        private readonly Context $context,
        private readonly ResourceType $resourceType
    ) {
    }

    /**
     * replace schema
     *
     * @param array $schema
     * @return self
     */
    public function setSchema(array $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * return schema
     *
     * @return array
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * return mapping
     *
     * @return array
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /**
     * return context
     *
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * return resourceType
     *
     * @return ResourceType
     */
    public function getResourceType(): ResourceType
    {
        return $this->resourceType;
    }
}
