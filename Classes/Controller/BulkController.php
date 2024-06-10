<?php

declare(strict_types=1);

namespace Ameos\Scim\Controller;

use Ameos\Scim\Enum\Context;
use Ameos\Scim\Service\BulkService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Log\Channel;

class BulkController
{
    use Traits\ConfigurationAccess;

    public function __construct(
        private readonly BulkService $bulkService,
        #[Channel('scim')]
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * bulk action
     *
     * @param ServerRequestInterface $request
     * @param Context $context
     * @return ResponseInterface
     */
    public function bulkAction(ServerRequestInterface $request, Context $context): ResponseInterface
    {
        $payload = json_decode($request->getBody()->getContents(), true);
        try {
            $this->logger->info('Bulk action', $payload);
            return new JsonResponse(
                $this->bulkService->execute(
                    $payload,
                    $this->getConfiguration($request),
                    $context
                )
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during bulk action', $payload);
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => $e->getMessage(),
                    'status' => 400
                ],
                400
            );
        }
    }
}
