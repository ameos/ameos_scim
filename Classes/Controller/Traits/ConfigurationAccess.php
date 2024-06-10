<?php

namespace Ameos\Scim\Controller\Traits;

use Ameos\Scim\Configuration;
use Ameos\Scim\Enum\Context;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('scim');
        $baseRegex = str_replace(
            '/',
            '\/',
            $request->getAttribute('scim_context') === Context::Frontend ? $config['fe_path'] : $config['be_path']
        );

        $configuration = [];
        $mappingKey = $request->getAttribute('scim_context') === Context::Frontend ? 'frontend' : 'backend';
        $yamlConfiguration = (new YamlFileLoader())->load(Configuration::getConfigurationFilepath());
        $configuration = $yamlConfiguration['scim'][$mappingKey];

        if ($request->getAttribute('scim_context') === Context::Frontend) {
            $typoscript = $request->getAttribute('frontend.typoscript')
                ->getSetupArray()['plugin.']['tx_scim.']['settings.'] ?? [];
        }

        $configuration['pid'] = isset($typoscript['pid']) ? (int)$typoscript['pid'] : 0;
        return $configuration;
    }
}
