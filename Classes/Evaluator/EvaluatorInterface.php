<?php

declare(strict_types=1);

namespace Ameos\Scim\Evaluator;

interface EvaluatorInterface
{
    /**
     * retrieve resource data
     *
     * @param array $data
     * @param array $configuration
     */
    public function retrieveResourceData(array $data, array $configuration);

    /**
     * set resource data
     *
     * @param array $payload
     * @param array $data
     * @param array $configuration
     */
    public function setResourceData(array $payload, array $data, array $configuration);
}
