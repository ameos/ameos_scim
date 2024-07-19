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
     * @param array $meta
     * @return array
     */
    public function apply(array $record, array $payload, array $mapping, array $meta): array
    {
        $original = $record;

        $payload = array_change_key_case($payload);
        foreach ($payload['operations'] as $operation) {
            $operation = array_change_key_case($operation);
            $operation['op'] = mb_strtolower($operation['op']);
            $record = match ($operation['op']) {
                self::OP_ADD => $this->add($record, $operation, $mapping, $meta),
                self::OP_REMOVE => $this->remove($record, $operation, $mapping, $meta),
                self::OP_REPLACE => $this->replace($record, $operation, $mapping, $meta),
                self::OP_MOVE => $this->move($record, $operation, $mapping, $meta),
                self::OP_COPY => $this->copy($record, $operation, $mapping, $meta),
                self::OP_TEST => $this->test($record, $operation, $mapping, $meta),
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
     * @param array $meta
     * @return array
     */
    private function add(array $record, array $operation, array $mapping, array $meta): array
    {
        $path = trim(str_replace('/', '.', $operation['path']), '/');

        if (!isset($mapping[$path]['object'])) {
            $fields = $this->mappingService->findFieldsCorrespondingProperty($path, $mapping, $meta);
            if ($fields !== false) {
                $value = $this->getOperationValue($operation, $path, $mapping);
                foreach ($fields as $field) {
                    $record[$field] = $value;
                }
            }
        }

        return $record;
    }

    /**
     * process remove operation on $record
     *
     * @param array $record
     * @param array $operation
     * @param array $mapping
     * @param array $meta
     * @return array
     */
    private function remove(array $record, array $operation, array $mapping, array $meta): array
    {
        $path = trim(str_replace('/', '.', $operation['path']), '/');

        if (!isset($mapping[$path]['object'])) {
            $fields = $this->mappingService->findFieldsCorrespondingProperty($path, $mapping, $meta);
            if ($fields !== false) {
                foreach ($fields as $field) {
                    $record[$field] = '';
                }
            }
        }

        return $record;
    }

    /**
     * process replace operation on $record
     *
     * @param array $record
     * @param array $operation
     * @param array $mapping
     * @param array $meta
     * @return array
     */
    private function replace(array $record, array $operation, array $mapping, array $meta): array
    {
        $path = trim(str_replace('/', '.', $operation['path']), '/');

        if (!isset($mapping[$path]['object'])) {
            $fields = $this->mappingService->findFieldsCorrespondingProperty($path, $mapping, $meta);
            if ($fields !== false) {
                $value = $this->getOperationValue($operation, $path, $mapping);
                foreach ($fields as $field) {
                    $record[$field] = $value;
                }
            }
        }

        return $record;
    }

    /**
     * process move operation on $record
     *
     * @param array $record
     * @param array $operation
     * @param array $mapping
     * @param array $meta
     * @return array
     */
    private function move(array $record, array $operation, array $mapping, array $meta): array
    {
        $from = trim(str_replace('/', '.', $operation['from']), '/');
        $path = trim(str_replace('/', '.', $operation['path']), '/');

        if (!isset($mapping[$path]['object'])) {
            $fromField = $this->mappingService->findFieldsCorrespondingProperty($from, $mapping, $meta)[0];
            $pathField = $this->mappingService->findFieldsCorrespondingProperty($path, $mapping, $meta)[0];

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
     * @param array $meta
     * @return array
     */
    private function copy(array $record, array $operation, array $mapping, array $meta): array
    {
        $from = trim(str_replace('/', '.', $operation['from']), '/');
        $path = trim(str_replace('/', '.', $operation['path']), '/');

        if (!isset($mapping[$path]['object'])) {
            $fromField = $this->mappingService->findFieldsCorrespondingProperty($from, $mapping, $meta)[0];
            $pathField = $this->mappingService->findFieldsCorrespondingProperty($path, $mapping, $meta)[0];

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
     * @param array $meta
     * @return array
     */
    private function test(array $record, array $operation, array $mapping, array $meta): array
    {
        $path = trim(str_replace('/', '.', $operation['path']), '/');
        $fields = $this->mappingService->findFieldsCorrespondingProperty($path, $mapping, $meta);
        if ($fields === false) {
            throw new PatchTestErrorException('Test failed');
        }

        $value = $this->getOperationValue($operation, $path, $mapping);
        foreach ($fields as $field) {
            if ($record[$field] !== $value) {
                throw new PatchTestErrorException('Test failed');
            }
        }

        return $record;
    }

    /**
     * return value for an operation
     *
     * @param array $operation
     * @param string $path
     * @param array $mapping
     * @return mixed
     */
    private function getOperationValue(array $operation, string $path, array $mapping): mixed
    {
        $configuration = $this->mappingService->findPropertyConfiguration($path, $mapping);
        $value = $operation['value'];
        if (isset($configuration['toggle'])) {
            $value = !$value;
        }
        if (isset($configuration['cast']) && $configuration['cast'] === 'bool') {
            $value = $value ? 1 : 0;
        }
        return $value;
    }
}
