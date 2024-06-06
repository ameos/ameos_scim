<?php

declare(strict_types=1);

namespace Ameos\Scim\Service;

use Ameos\Scim\Exception\PatchTestErrorException;

class PatchService
{
    public const OP_ADD = 'add';
    public const OP_REMOVE = 'remove';
    public const OP_REPLACE = 'replace';
    public const OP_MOVE = 'move';
    public const OP_COPY = 'copy';
    public const OP_TEST = 'test';

    /**
     * @param MappingService $mappingService
     */
    public function __construct(private readonly MappingService $mappingService)
    {
    }

    /**
     * apply a patch
     *
     * @param array $record
     * @param array $payload
     * @param array $mapping
     * @return array
     */
    public function apply(array $record, array $payload, array $mapping): array
    {
        $original = $record;

        $payload = array_change_key_case($payload);
        foreach ($payload['operations'] as $operation) {
            $operation = array_change_key_case($operation);
            $record = match ($operation['op']) {
                self::OP_ADD => $this->add($record, $operation, $mapping),
                self::OP_REMOVE => $this->remove($record, $operation, $mapping),
                self::OP_REPLACE => $this->replace($record, $operation, $mapping),
                self::OP_MOVE => $this->move($record, $operation, $mapping),
                self::OP_COPY => $this->copy($record, $operation, $mapping),
                self::OP_TEST => $this->test($record, $operation, $mapping),
            };
        }

        return array_diff_assoc($record, $original);
    }

    /**
     * process add operation on $record
     *
     * @param array $record
     * @param array $operation
     * @param array $mapping
     * @return array
     */
    private function add(array $record, array $operation, array $mapping): array
    {
        $path = trim(str_replace('/', '.', $operation['path']), '/');

        if (!isset($mapping[$path]['callback'])) {
            $field = $this->mappingService->findField($path, $mapping);
            $configuration = $this->mappingService->findPropertyConfiguration($path, $mapping);
            if (isset($configuration['toggle'])) {
                $operation['value'] = !$operation['value'];
            }
            if (isset($configuration['cast']) && $configuration['cast'] === 'bool') {
                $operation['value'] = $operation['value'] ? 1 : 0;            
            }
            $record[$field] = $operation['value'];
        }

        return $record;
    }

    /**
     * process remove operation on $record
     *
     * @param array $record
     * @param array $operation
     * @param array $mapping
     * @return array
     */
    private function remove(array $record, array $operation, array $mapping): array
    {
        $path = trim(str_replace('/', '.', $operation['path']), '/');

        if (!isset($mapping[$path]['callback'])) {
            $field = $this->mappingService->findField($path, $mapping);
            $record[$field] = '';
        }

        return $record;
    }

    /**
     * process replace operation on $record
     *
     * @param array $record
     * @param array $operation
     * @param array $mapping
     * @return array
     */
    private function replace(array $record, array $operation, array $mapping): array
    {
        $path = trim(str_replace('/', '.', $operation['path']), '/');

        if (!isset($mapping[$path]['callback'])) {
            $field = $this->mappingService->findField($path, $mapping);
            $configuration = $this->mappingService->findPropertyConfiguration($path, $mapping);
            if (isset($configuration['toggle'])) {
                $operation['value'] = !$operation['value'];
            }
            if (isset($configuration['cast']) && $configuration['cast'] === 'bool') {
                $operation['value'] = $operation['value'] ? 1 : 0;            
            }
            $record[$field] = $operation['value'];
        }

        return $record;
    }

    /**
     * process move operation on $record
     *
     * @param array $record
     * @param array $operation
     * @param array $mapping
     * @return array
     */
    private function move(array $record, array $operation, array $mapping): array
    {
        $from = trim(str_replace('/', '.', $operation['from']), '/');
        $path = trim(str_replace('/', '.', $operation['path']), '/');

        if (!isset($mapping[$path]['callback'])) {
            $fromField = $this->mappingService->findField($from, $mapping);
            $pathField = $this->mappingService->findField($path, $mapping);

            $record[$pathField] = $record[$fromField];
            $record[$fromField] = '';
        }

        return $record;
    }

    /**
     * process copy operation on $record
     *
     * @param array $record
     * @param array $operation
     * @param array $mapping
     * @return array
     */
    private function copy(array $record, array $operation, array $mapping): array
    {
        $from = trim(str_replace('/', '.', $operation['from']), '/');
        $path = trim(str_replace('/', '.', $operation['path']), '/');

        if (!isset($mapping[$path]['callback'])) {
            $fromField = $this->mappingService->findField($from, $mapping);
            $pathField = $this->mappingService->findField($path, $mapping);

            $record[$pathField] = $record[$fromField];
        }

        return $record;
    }

    /**
     * process test operation on $record
     *
     * @param array $record
     * @param array $operation
     * @param array $mapping
     * @return array
     */
    private function test(array $record, array $operation, array $mapping): array
    {
        $path = trim(str_replace('/', '.', $operation['path']), '/');
        $field = $this->mappingService->findField($path, $mapping);

        $configuration = $this->mappingService->findPropertyConfiguration($path, $mapping);
        if (isset($configuration['toggle'])) {
            $operation['value'] = !$operation['value'];
        }
        if (isset($configuration['cast']) && $configuration['cast'] === 'bool') {
            $operation['value'] = $operation['value'] ? 1 : 0;            
        }

        if ($record[$field] !== $operation['value']) {
            throw new PatchTestErrorException('Test failed');
        }

        return $record;
    }
}
