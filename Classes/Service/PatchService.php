<?php

declare(strict_types=1);

namespace Ameos\Scim\Service;

use Ameos\Scim\Exception\PatchTestErrorException;

class PatchService
{
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

        foreach ($payload['Operations'] as $operation) {
            $record = match ($operation['op']) {
                'add' => $this->add($record, $operation, $mapping),
                'remove' => $this->remove($record, $operation, $mapping),
                'replace' => $this->replace($record, $operation, $mapping),
                'move' => $this->move($record, $operation, $mapping),
                'copy' => $this->copy($record, $operation, $mapping),
                'test' => $this->test($record, $operation, $mapping),
            };
        }

        return array_diff($original, $record);
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
        $path = trim(str_replace('/', '.', $operation['path'], '/'));
        $field = $this->mappingService->findField($path, $mapping);

        $record[$field] = $operation['value'];

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
        $path = trim(str_replace('/', '.', $operation['path'], '/'));
        $field = $this->mappingService->findField($path, $mapping);

        $record[$field] = $operation['value'];

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
        $path = trim(str_replace('/', '.', $operation['path'], '/'));
        $field = $this->mappingService->findField($path, $mapping);

        $record[$field] = $operation['value'];

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
        $from = trim(str_replace('/', '.', $operation['from'], '/'));
        $path = trim(str_replace('/', '.', $operation['path'], '/'));
        $fromField = $this->mappingService->findField($from, $mapping);
        $pathField = $this->mappingService->findField($path, $mapping);

        $record[$pathField] = $record[$fromField];
        $record[$fromField] = '';

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
        $from = trim(str_replace('/', '.', $operation['from'], '/'));
        $path = trim(str_replace('/', '.', $operation['path'], '/'));
        $fromField = $this->mappingService->findField($from, $mapping);
        $pathField = $this->mappingService->findField($path, $mapping);

        $record[$pathField] = $record[$fromField];

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
        $path = trim(str_replace('/', '.', $operation['path'], '/'));
        $field = $this->mappingService->findField($path, $mapping);

        if ($record[$field] !== $operation['value']) {
            throw new PatchTestErrorException('Test failed');
        }

        return $record;
    }
}
