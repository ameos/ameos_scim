<?php

declare(strict_types=1);

namespace Ameos\Scim;

class Configuration
{
    /**
     * register configuration
     *
     * @param string $filepath
     * @return void
     */
    public static function registerConfiguration(string $filepath): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SCIM']['Configuration'] = $filepath;
    }
}
