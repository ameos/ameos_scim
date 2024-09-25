<?php

declare(strict_types=1);

namespace Ameos\AmeosScim;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class Configuration
{
    /**
     * register a custom configuration
     *
     * @param string $filepath
     * @return void
     */
    public static function registerConfiguration(string $filepath): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SCIM']['Configuration']['Custom'] = $filepath;
    }

    /**
     * register default configuration
     *
     * @param string $filepath
     * @return void
     */
    public static function registerDefaultConfiguration(string $filepath): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SCIM']['Configuration']['Default'] = $filepath;
    }

    /**
     * return configuration file path
     *
     * @return string
     */
    public static function getConfigurationFilepath(): string
    {
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['SCIM']['Configuration']['Custom'])
            && !empty($GLOBALS['TYPO3_CONF_VARS']['SCIM']['Configuration']['Custom'])
        ) {
            $filepath = GeneralUtility::getFileAbsFileName(
                $GLOBALS['TYPO3_CONF_VARS']['SCIM']['Configuration']['Custom']
            );
            if (!empty($filepath) && file_exists($filepath)) {
                return $GLOBALS['TYPO3_CONF_VARS']['SCIM']['Configuration']['Custom'];
            }
        }

        return $GLOBALS['TYPO3_CONF_VARS']['SCIM']['Configuration']['Default'];
    }
}
