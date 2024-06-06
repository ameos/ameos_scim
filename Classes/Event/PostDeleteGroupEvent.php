<?php

declare(strict_types=1);

namespace Ameos\Scim\Event;

use Ameos\Scim\Enum\Context;

final class PostDeleteGroupEvent
{
    /**
     * @param string $recordId
     * @param array $mapping
     * @param Context $context
     */
    public function __construct(
        private readonly string $recordId,
        private readonly array $mapping,
        private readonly Context $context
    ) {
    }

    /**
     * return record id
     *
     * @return string
     */
    public function getRecordId(): string
    {
        return $this->recordId;
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
}
