<?php

declare(strict_types=1);

namespace Ameos\Scim\Controller;

use Ameos\Scim\Enum\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractResourceController
{
    /**
     * search action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function searchAction(ServerRequestInterface $request): ResponseInterface;

    /**
     * read action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function readAction(string $resourceId, ServerRequestInterface $request): ResponseInterface;

    /**
     * create action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function createAction(ServerRequestInterface $request): ResponseInterface;

    /**
     * patch action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function patchAction(string $resourceId, ServerRequestInterface $request): ResponseInterface;

    /**
     * update action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function updateAction(string $resourceId, ServerRequestInterface $request): ResponseInterface;

    /**
     * delete action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function deleteAction(string $resourceId, ServerRequestInterface $request): ResponseInterface;

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
        if (preg_match('/' . $baseRegex . '([a-zA-Z]*)\/?.*/i', $request->getUri()->getPath(), $matches)) {
            $mappingKey = match (mb_strtolower($matches[1])) {
                'users' => $request->getAttribute('scim_context') === Context::Frontend
                    ? 'frontend.user' : 'backend.user',
                'groups' => $request->getAttribute('scim_context') === Context::Frontend
                    ? 'frontend.group' : 'backend.group',
            };

            $yamlConfiguration = (new YamlFileLoader())->load($GLOBALS['TYPO3_CONF_VARS']['SCIM']['Configuration']);
            $configuration['mapping'] = $yamlConfiguration['scim'][$mappingKey]['mapping'];
        }

        if ($request->getAttribute('scim_context') === Context::Frontend) {
            $typoscript = $request->getAttribute('frontend.typoscript')
                ->getSetupArray()['plugin.']['tx_scim.']['settings.'] ?? [];
        }

        $configuration['pid'] = isset($typoscript['pid']) ? (int)$typoscript['pid'] : 0;
        return $configuration;
    }
}
