<?php

declare(strict_types=1);

namespace Ameos\Scim\Middleware;

use Ameos\Scim\Service\RoutingService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Error\Http\ForbiddenException;
use TYPO3\CMS\Core\Log\Channel;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ScimRoutingMiddleware implements MiddlewareInterface
{
    /**
     * @param RoutingService $routingService
     * @param ConnectionPool $connectionPool
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RoutingService $routingService,
        private readonly ConnectionPool $connectionPool,
        #[Channel('scim')]
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * process middle ware
     * if uri start with /v2/Users or /v2/Groups : call Scim controller
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('is_scim_request')) {
            $authorization = $request->getHeader('authorization');
            if (isset($authorization[0]) && preg_match('/Bearer\s(\S+)/', $authorization[0], $matches)) {
                if (!$this->isAuthorizedSecret($matches[1])) {
                    $this->logger->critical('Access denied - Bearer not match');
                    throw new ForbiddenException('Access denied');
                }
            } else {
                $this->logger->critical('Access denied - Bearer missing');
                throw new ForbiddenException('Access denied');
            }

            return $this->routingService->route($request);
        }

        return $handler->handle($request);
    }

    /**
     * return true if secret is authorized
     *
     * @param string $secret
     * @return bool
     */
    private function isAuthorizedSecret(string $secret): bool
    {
        $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('BE');

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_scim_access');
        $result = $qb->select('*')->from('tx_scim_access')->executeQuery();
        while ($access = $result->fetchAssociative()) {
            if ($hashInstance->checkPassword($secret, $access['secret'])) {
                return true;
            }
        }
        return false;
    }
}
