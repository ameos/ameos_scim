<?php

declare(strict_types=1);

namespace Ameos\Scim\Event;

use Ameos\Scim\Enum\Context;

final class PostDeleteGroupEvent
{
    /**
     * @param string $recordId
     * @param Context $context
     */
    public function __construct(
        private readonly string $recordId,
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
     * return context
     *
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }
}
