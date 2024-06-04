<?php

declare(strict_types=1);

namespace Ameos\Scim\Controller\Backend;

use Ameos\Scim\Controller\AbstractResourceController;
use Ameos\Scim\Exception\NoResourceFoundException;
use Ameos\Scim\Service\Backend\GroupService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Log\Channel;

class GroupController extends AbstractResourceController
{
    public function __construct(
        private readonly GroupService $groupService,
        #[Channel('scim')]
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * search action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function searchAction(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('Search groups', $request->getQueryParams());
            return new JsonResponse(
                $this->groupService->search(
                    $request->getQueryParams(),
                    $this->getConfiguration($request)
                )
            );
        } catch (NoResourceFoundException $e) {
            $this->logger->warning('No group found', $request->getQueryParams());
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => $e->getMessage(),
                    'status' => 404
                ],
                404
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during group search', $request->getQueryParams());
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

    /**
     * read action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function readAction(string $resourceId, ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('Read group ' . $resourceId, $request->getQueryParams());
            return new JsonResponse(
                $this->groupService->read(
                    $resourceId,
                    $request->getQueryParams(),
                    $this->getConfiguration($request)
                )
            );
        } catch (NoResourceFoundException $e) {
            $this->logger->warning('No group found', $request->getQueryParams());
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => $e->getMessage(),
                    'status' => 404
                ],
                404
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during group fetch ' . $resourceId, $request->getQueryParams());
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

    /**
     * create action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function createAction(ServerRequestInterface $request): ResponseInterface
    {
        $payload = json_decode($request->getBody()->getContents(), true);
        $this->logger->info('Create group', $payload);
        if (!$payload) {
            $this->logger->error('Error during group creation', $payload);
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => 'Invalid request',
                    'status' => 400
                ],
                400
            );
        }

        try {
            $payload = $this->groupService->create($payload, $this->getConfiguration($request));
        } catch (\Exception $e) {
            $this->logger->error('Error during group creation ' . $e->getMessage(), $payload);
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => 'Invalid request',
                    'status' => 400
                ],
                400
            );
        }

        return new JsonResponse(
            $payload,
            201,
            [
                'Location' => $payload['meta']['location']
            ]
        );
    }

    /**
     * patch action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function patchAction(string $resourceId, ServerRequestInterface $request): ResponseInterface
    {
        $payload = json_decode($request->getBody()->getContents(), true);
        if (!$payload) {
            $this->logger->error('Error during group patch ' . $resourceId, $payload);
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => 'Invalid requet',
                    'status' => 400
                ],
                400
            );
        }

        try {
            $this->logger->info('Patch group ' . $resourceId, $payload);
            return new JsonResponse(
                $this->groupService->patch(
                    $resourceId,
                    $payload,
                    $this->getConfiguration($request)
                )
            );
        } catch (NoResourceFoundException $e) {
            $this->logger->warning('No group found ' . $resourceId, $payload);
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => $e->getMessage(),
                    'status' => 404
                ],
                404
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during group patch ' . $e->getMessage(), $payload);
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

    /**
     * update action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function updateAction(string $resourceId, ServerRequestInterface $request): ResponseInterface
    {
        $payload = json_decode($request->getBody()->getContents(), true);
        if (!$payload) {
            $this->logger->error('Error during group update ' . $resourceId, $payload);
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => 'Invalid requet',
                    'status' => 400
                ],
                400
            );
        }

        try {
            $this->logger->info('Update group ' . $resourceId, $payload);
            return new JsonResponse(
                $this->groupService->update(
                    $resourceId,
                    $payload,
                    $this->getConfiguration($request)
                )
            );
        } catch (NoResourceFoundException $e) {
            $this->logger->warning('No group found ' . $resourceId, $payload);
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => $e->getMessage(),
                    'status' => 404
                ],
                404
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during group update ' . $e->getMessage(), $payload);
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

    /**
     * delete action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function deleteAction(string $resourceId, ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('Delete group ' . $resourceId);
            $this->groupService->delete($resourceId);
            return new HtmlResponse('', 204);
        } catch (\Exception $e) {
            $this->logger->error('Error during group delete ' . $e->getMessage());
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
