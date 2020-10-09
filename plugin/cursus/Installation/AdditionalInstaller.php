<?php

namespace Claroline\CursusBundle\Installation;

use Claroline\CursusBundle\Installation\Updater\Updater130000;
use Claroline\InstallationBundle\Additional\AdditionalInstaller as BaseInstaller;

class AdditionalInstaller extends BaseInstaller
{
    public function preUpdate($currentVersion, $targetVersion)
    {
        if (version_compare($currentVersion, '13.0.0', '<')) {
            if (!$this->shouldReplayUpdaters() && $this->isUpdaterAlreadyExecuted(Updater130000::class)) {
                return;
            }
            $updater = $this->container->get(Updater130000::class);
            $updater->preUpdate();
        }
    }

    public function postUpdate($currentVersion, $targetVersion)
    {
        if (version_compare($currentVersion, '13.0.0', '<')) {
            $alreadyExecuted = $this->isUpdaterAlreadyExecuted(Updater130000::class);

            if (!$this->shouldReplayUpdaters() && $alreadyExecuted) {
                return;
            }

            $updater = $this->container->get(Updater130000::class);
            $updater->postUpdate();

            if (!$alreadyExecuted) {
                $this->markUpdaterAsExecuted(Updater\Updater130000::class);
            }
        }
    }
}
