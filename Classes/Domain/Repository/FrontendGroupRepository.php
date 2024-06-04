<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository;

class FrontendGroupRepository extends AbstractResourceRepository
{
    /**
     * return table name
     *
     * @return string
     */
    protected function getTable(): string
    {
        return 'fe_groups';
    }
}
