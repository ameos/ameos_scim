<?php

declare(strict_types=1);

namespace Ameos\Scim\Controller;

use Ameos\Scim\Enum\Context;
use Ameos\Scim\Exception\NoResourceFoundException;
use Ameos\Scim\Service\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Log\Channel;

class UserController extends AbstractResourceController
{
    public function __construct(
        private readonly UserService $userService,
        #[Channel('scim')]
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * search action
     *
     * @param ServerRequestInterface $request
     * @param Context $context
     * @return ResponseInterface
     */
    public function searchAction(ServerRequestInterface $request, Context $context): ResponseInterface
    {
        try {
            $this->logger->info('Search users', $request->getQueryParams());
            return new JsonResponse(
                $this->userService->search(
                    $request->getQueryParams(),
                    $this->getConfiguration($request),
                    $context
                )
            );
        } catch (NoResourceFoundException $e) {
            $this->logger->warning('No user found', $request->getQueryParams());
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => $e->getMessage(),
                    'status' => 404
                ],
                404
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during user search', $request->getQueryParams());
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
     * @param Context $context
     * @return ResponseInterface
     */
    public function readAction(
        string $resourceId,
        ServerRequestInterface $request,
        Context $context
    ): ResponseInterface {
        try {
            $this->logger->info('Read user ' . $resourceId, $request->getQueryParams());
            return new JsonResponse(
                $this->userService->read(
                    $resourceId,
                    $request->getQueryParams(),
                    $this->getConfiguration($request),
                    $context
                )
            );
        } catch (NoResourceFoundException $e) {
            $this->logger->warning('No user found', $request->getQueryParams());
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => $e->getMessage(),
                    'status' => 404
                ],
                404
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during user fetch ' . $resourceId, $request->getQueryParams());
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
     * @param Context $context
     * @return ResponseInterface
     */
    public function createAction(ServerRequestInterface $request, Context $context): ResponseInterface
    {
        $payload = json_decode($request->getBody()->getContents(), true);
        $this->logger->info('Create user', []);
        if (!$payload) {
            $this->logger->error('Error during user creation', []);
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
            $payload = $this->userService->create($payload, $this->getConfiguration($request), $context);
        } catch (\Exception $e) {
            $this->logger->error('Error during user creation ' . $e->getMessage(), $payload);
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
     * @param Context $context
     * @return ResponseInterface
     */
    public function patchAction(
        string $resourceId,
        ServerRequestInterface $request,
        Context $context
    ): ResponseInterface {
        $payload = json_decode($request->getBody()->getContents(), true);
        if (!$payload) {
            $this->logger->error('Error during user patch ' . $resourceId, $payload);
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
            $this->logger->info('Patch user ' . $resourceId, $payload);
            return new JsonResponse(
                $this->userService->patch(
                    $resourceId,
                    $payload,
                    $this->getConfiguration($request),
                    $context
                )
            );
        } catch (NoResourceFoundException $e) {
            $this->logger->warning('No user found ' . $resourceId, $payload);
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => $e->getMessage(),
                    'status' => 404
                ],
                404
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during user patch ' . $e->getMessage(), $payload);
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
     * @param Context $context
     * @return ResponseInterface
     */
    public function updateAction(
        string $resourceId,
        ServerRequestInterface $request,
        Context $context
    ): ResponseInterface {
        $payload = json_decode($request->getBody()->getContents(), true);
        if (!$payload) {
            $this->logger->error('Error during user update ' . $resourceId, $payload);
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
            $this->logger->info('Update user ' . $resourceId, $payload);
            return new JsonResponse(
                $this->userService->update(
                    $resourceId,
                    $payload,
                    $this->getConfiguration($request),
                    $context
                )
            );
        } catch (NoResourceFoundException $e) {
            $this->logger->warning('No user found ' . $resourceId, $payload);
            return new JsonResponse(
                [
                    'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                    'detail' => $e->getMessage(),
                    'status' => 404
                ],
                404
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during user update ' . $e->getMessage(), $payload);
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
     * @param Context $context
     * @return ResponseInterface
     */
    public function deleteAction(
        string $resourceId,
        ServerRequestInterface $request,
        Context $context
    ): ResponseInterface {
        try {
            $this->logger->info('Delete user ' . $resourceId);
            $this->userService->delete($resourceId, $this->getConfiguration($request), $context);
            return new HtmlResponse('', 204);
        } catch (\Exception $e) {
            $this->logger->error('Error during user delete ' . $e->getMessage());
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
