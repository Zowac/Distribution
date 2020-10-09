<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\InstallationBundle\Additional;

use Claroline\AppBundle\Log\LoggableTrait;
use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Update\UpdaterExecution;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AdditionalInstaller implements LoggerAwareInterface, ContainerAwareInterface, AdditionalInstallerInterface
{
    use LoggableTrait;
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var bool Whether updaters should be executed even if they have been already.
     */
    private $shouldReplayUpdaters = false;

    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    public function setShouldReplayUpdaters(bool $shouldReplayUpdaters): void
    {
        $this->shouldReplayUpdaters = $shouldReplayUpdaters;
    }

    public function shouldReplayUpdaters(): bool
    {
        return $this->shouldReplayUpdaters;
    }

    public function isUpdaterAlreadyExecuted(string $updaterClass): bool
    {
        /** @var ObjectManager $om */
        $om = $this->container->get(ObjectManager::class);

        return 0 < $om->getRepository(UpdaterExecution::class)->count(['updaterClass' => $updaterClass]);
    }

    public function markUpdaterAsExecuted(string $updaterClass): void
    {
        /** @var ObjectManager $om */
        $om = $this->container->get(ObjectManager::class);

        $om->persist(new UpdaterExecution($updaterClass));
        $om->flush();
    }

    public function preInstall()
    {
    }

    public function postInstall()
    {
    }

    public function preUpdate($currentVersion, $targetVersion)
    {
    }

    public function postUpdate($currentVersion, $targetVersion)
    {
    }

    public function preUninstall()
    {
    }

    public function postUninstall()
    {
    }

    public function end($currentVersion, $targetVersion)
    {
    }
}
