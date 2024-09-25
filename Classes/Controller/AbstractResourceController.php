<?php

declare(strict_types=1);

namespace Ameos\AmeosScim\Controller;

use Ameos\AmeosScim\Enum\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractResourceController
{
    use Traits\ConfigurationAccess;

    /**
     * search action
     *
     * @param ServerRequestInterface $request
     * @param Context $context
     * @return ResponseInterface
     */
    abstract public function searchAction(
        ServerRequestInterface $request,
        Context $context
    ): ResponseInterface;

    /**
     * read action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @param Context $context
     * @return ResponseInterface
     */
    abstract public function readAction(
        string $resourceId,
        ServerRequestInterface $request,
        Context $context
    ): ResponseInterface;

    /**
     * create action
     *
     * @param ServerRequestInterface $request
     * @param Context $context
     * @return ResponseInterface
     */
    abstract public function createAction(
        ServerRequestInterface $request,
        Context $context
    ): ResponseInterface;

    /**
     * patch action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @param Context $context
     * @return ResponseInterface
     */
    abstract public function patchAction(
        string $resourceId,
        ServerRequestInterface $request,
        Context $context
    ): ResponseInterface;

    /**
     * update action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @param Context $context
     * @return ResponseInterface
     */
    abstract public function updateAction(
        string $resourceId,
        ServerRequestInterface $request,
        Context $context
    ): ResponseInterface;

    /**
     * delete action
     *
     * @param string $resourceId
     * @param ServerRequestInterface $request
     * @param Context $context
     * @return ResponseInterface
     */
    abstract public function deleteAction(
        string $resourceId,
        ServerRequestInterface $request,
        Context $context
    ): ResponseInterface;
}
