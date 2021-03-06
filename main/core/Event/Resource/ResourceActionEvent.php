<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Event\Resource;

use Claroline\CoreBundle\Entity\Resource\AbstractResource;
use Claroline\CoreBundle\Entity\Resource\ResourceNode;
use Symfony\Component\HttpFoundation\Response;

/**
 * An event which is dispatched when an action is requested on a Resource.
 */
class ResourceActionEvent extends ResourceEvent
{
    /**
     * The data passed to the action (eg. new data).
     *
     * NB. Data depend on the requested action, so we can not validate it.
     * This is the duty of the attached listener to check it gets what it wants.
     *
     * @var array
     */
    private $data = null;

    /**
     * The files passed to the action (eg. uploaded files).
     *
     * @var array
     */
    private $files = null;

    /**
     * The options of the action (eg. list query string).
     *
     * NB. Options depend on the requested action, so we can not validate them.
     * This is the duty of the attached listener to check it gets what it wants.
     *
     * @var array
     */
    private $options = [];

    /**
     * The response generated by the action.
     *
     * @var Response
     */
    private $response = null;

    /**
     * ResourceActionEvent constructor.
     *
     * @param AbstractResource $resource
     * @param array            $options
     * @param array            $data
     * @param array            $files
     * @param ResourceNode     $resourceNode
     */
    public function __construct(
        AbstractResource $resource = null,
        array $options = [],
        array $data = null,
        array $files = null,
        ResourceNode $resourceNode = null)
    {
        parent::__construct($resource, $resourceNode);

        $this->data = $data;
        $this->files = $files;
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }
}
