<?php

declare(strict_types=1);

namespace Ameos\Scim\Domain\Repository;

class FrontendUserRepository extends AbstractResourceRepository
{
    /**
     * return table name
     *
     * @return string
     */
    protected function getTable(): string
    {
        return 'fe_users';
    }
}
