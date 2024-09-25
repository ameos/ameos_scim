<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Registry;

use Ameos\AmeosScim\Enum\ResourceType;
use Ameos\AmeosScim\Exception\RegistryItemNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;

class BulkIdRegistry implements SingletonInterface
{
    /**
     * @param array $registry
     */
    public function __construct(private array $registry = [])
    {
    }

    /**
     * add resource to registry
     *
     * @param string $bulkId
     * @param string $resourceId
     * @param ResourceType $resourceType
     * @return void
     */
    public function addResource(string $bulkId, string $resourceId, ResourceType $resourceType): void
    {
        $this->registry[$bulkId] = ['id' => $resourceId, 'type' => $resourceType];
    }

    /**
     * get resource
     *
     * @param string $bulkId
     * @return array
     */
    public function getResource(string $bulkId): array
    {
        if (!isset($this->registry[$bulkId])) {
            throw new RegistryItemNotFoundException('Item not found in bulk id registry');
        }
        return $this->registry[$bulkId];
    }
}
