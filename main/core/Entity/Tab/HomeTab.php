<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Entity\Tab;

use Claroline\AppBundle\Entity\Identifier\Id;
use Claroline\AppBundle\Entity\Identifier\Uuid;
use Claroline\AppBundle\Entity\Meta\Poster;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\Widget\WidgetContainer;
use Claroline\CoreBundle\Entity\Workspace\Workspace;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="claro_home_tab")
 */
class HomeTab
{
    use Id;
    use Poster;
    use Uuid;

    const TYPE_WORKSPACE = 'workspace';
    const TYPE_DESKTOP = 'desktop';
    const TYPE_ADMIN_DESKTOP = 'administration';
    const TYPE_HOME = 'home';
    const TYPE_ADMIN = 'admin';

    /**
     * @ORM\Column(nullable=false)
     *
     * @var string
     */
    private $type;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\User"
     * )
     * @ORM\JoinColumn(name="user_id", nullable=true, onDelete="CASCADE")
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Claroline\CoreBundle\Entity\Workspace\Workspace"
     * )
     * @ORM\JoinColumn(name="workspace_id", nullable=true, onDelete="CASCADE")
     *
     * @var Workspace
     */
    private $workspace = null;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Claroline\CoreBundle\Entity\Tab\HomeTabConfig",
     *     mappedBy="homeTab",
     *     cascade={"persist", "remove"}
     * )
     *
     * @var HomeTabConfig[]|ArrayCollection
     */
    private $homeTabConfigs;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Claroline\CoreBundle\Entity\Widget\WidgetContainer",
     *     mappedBy="homeTab",
     *     cascade={"persist", "remove"}
     * )
     *
     * @var WidgetContainer[]|ArrayCollection
     */
    private $widgetContainers;

    /**
     * HomeTab constructor.
     */
    public function __construct()
    {
        $this->refreshUuid();

        $this->homeTabConfigs = new ArrayCollection();
        $this->widgetContainers = new ArrayCollection();
    }

    public function getSlug()
    {
        if (!empty($this->homeTabConfigs) && !empty($this->homeTabConfigs[0]) && !empty($this->homeTabConfigs[0]->getLongTitle())) {
            return substr(strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->homeTabConfigs[0]->getLongTitle()))), 0, 128);
        }

        return 'new';
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    public function setWorkspace(Workspace $workspace)
    {
        $this->workspace = $workspace;
    }

    /**
     * @return WidgetContainer[]|ArrayCollection
     */
    public function getWidgetContainers()
    {
        return $this->widgetContainers;
    }

    /**
     * @param string $containerId
     *
     * @return WidgetContainer|null
     */
    public function getWidgetContainer($containerId)
    {
        $found = null;

        foreach ($this->widgetContainers as $container) {
            if ($container->getUuid() === $containerId) {
                $found = $container;
                break;
            }
        }

        return $found;
    }

    public function addWidgetContainer(WidgetContainer $widgetContainer)
    {
        if (!$this->widgetContainers->contains($widgetContainer)) {
            $this->widgetContainers->add($widgetContainer);
        }
    }

    public function removeWidgetContainer(WidgetContainer $widgetContainer)
    {
        if ($this->widgetContainers->contains($widgetContainer)) {
            $this->widgetContainers->removeElement($widgetContainer);
        }
    }

    public function getHomeTabConfigs()
    {
        return $this->homeTabConfigs;
    }

    public function addHomeTabConfig(HomeTabConfig $config)
    {
        if (!$this->homeTabConfigs->contains($config)) {
            $this->homeTabConfigs->add($config);
        }
    }

    public function removeHomeTabConfig(HomeTabConfig $config)
    {
        if ($this->homeTabConfigs->contains($config)) {
            $this->homeTabConfigs->removeElement($config);
        }
    }
}
