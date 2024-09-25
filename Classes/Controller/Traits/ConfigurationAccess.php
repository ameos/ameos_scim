<?php

namespace Ameos\AmeosScim\Controller\Traits;

use Ameos\AmeosScim\Configuration;
use Ameos\AmeosScim\Enum\Context;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;

trait ConfigurationAccess
{
    /**
     * return configuration from request
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getConfiguration(ServerRequestInterface $request): array
    {
        $configuration = [];
        $mappingKey = $request->getAttribute('scim_context') === Context::Frontend ? 'frontend' : 'backend';
        $yamlConfiguration = (new YamlFileLoader())->load(Configuration::getConfigurationFilepath());
        $configuration = $yamlConfiguration['scim'][$mappingKey];

        if ($request->getAttribute('scim_context') === Context::Frontend) {
            $typoscript = $request->getAttribute('frontend.typoscript')
                ->getSetupArray()['plugin.']['tx_ameosscim.']['settings.'] ?? [];
        }

        $configuration['pid'] = isset($typoscript['pid']) ? (int)$typoscript['pid'] : 0;
        return $configuration;
    }
}
