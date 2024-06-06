<?php

declare(strict_types=1);

namespace Ameos\Scim\Evaluator;

use Ameos\Scim\Enum\Context;

interface EvaluatorInterface
{
    /**
     * retrieve resource data
     *
     * @param array $data
     * @param array $configuration
     * @param Context $context
     */
    public function retrieveResourceData(array $data, array $configuration, Context $context);

    /**
     * set resource data
     *
     * @param array $payload
     * @param array $data
     * @param array $configuration
     */
    public function setResourceData(array $payload, array $data, array $configuration);

    /**
     * return field
     *
     * @param array $configuration
     * @return string
     */
    public function getFields(array $configuration): ?array;
}
