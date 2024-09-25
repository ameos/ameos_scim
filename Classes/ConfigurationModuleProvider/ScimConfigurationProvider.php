<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\ConfigurationModuleProvider;

use Ameos\AmeosScim\Configuration;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\AbstractProvider;

class ScimConfigurationProvider extends AbstractProvider
{
    /**
     * Returns the configuration, displayed in the module
     */
    public function getConfiguration(): array
    {
        return (new YamlFileLoader())->load(Configuration::getConfigurationFilepath());
    }
}
