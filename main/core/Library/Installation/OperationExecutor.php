<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Library\Installation;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\BundleRecorder\Detector\Detector;
use Claroline\BundleRecorder\Log\LoggableTrait;
use Claroline\CoreBundle\Library\Installation\Plugin\Installer;
use Claroline\CoreBundle\Manager\VersionManager;
use Claroline\InstallationBundle\Manager\InstallationManager;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Installs/updates platform bundles based on the comparison of
 * previous and current local composer repositories (i.e. the file
 * "vendor/composer/installed.json" and its backup in "app/config").
 */
class OperationExecutor
{
    use LoggableTrait;

    private $kernel;
    private $baseInstaller;
    private $pluginInstaller;
    private $installedRepoFile;
    private $previousRepoFile;
    private $detector;
    private $om;

    public function __construct(
        KernelInterface $kernel,
        $bundleFile,
        InstallationManager $baseInstaller,
        Installer $pluginInstaller,
        ObjectManager $om,
        VersionManager $versionManager
    ) {
        $this->kernel = $kernel;
        $this->versionManager = $versionManager;
        $this->baseInstaller = $baseInstaller;
        $this->pluginInstaller = $pluginInstaller;
        $this->previousRepoFile = $this->kernel->getRootDir().'/config/previous-installed.json';
        $this->installedRepoFile = $this->kernel->getRootDir().'/../vendor/composer/installed.json';
        $this->bundleFile = $bundleFile;
        $this->detector = new Detector();
        $this->versionManager = $versionManager;
        $this->om = $om;
    }

    /**
     * Overrides default local repository files (test purposes).
     *
     * @param string $previousRepoFile
     * @param string $installedRepoFile
     */
    public function setRepositoryFiles($previousRepoFile, $installedRepoFile)
    {
        $this->previousRepoFile = $previousRepoFile;
        $this->installedRepoFile = $installedRepoFile;
    }

    /**
     * Overrides the default bundle detector (test purposes).
     *
     * @param Detector $detector
     */
    public function setBundleDetector(Detector $detector)
    {
        $this->detector = $detector;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->baseInstaller->setLogger($logger);
        $this->pluginInstaller->setLogger($logger);
    }

    /**
     * Builds the list of operations to be executed based on the comparison
     * of previous and current installed dependencies.
     *
     * @return array
     */
    public function buildOperationList()
    {
        $this->log('Building install/update operations list...');
        $current = $this->versionManager->openRepository($this->installedRepoFile);

        foreach ($current->getCanonicalPackages() as $currentPackage) {
            $extra = $currentPackage->getExtra();
            //this is a meta package if the bundles key exists
            if (array_key_exists('bundles', $extra)) {
                //this is only valid for installable bundles
                $bundles = array_filter($extra['bundles'], function ($var) {
                    try {
                        return in_array('Claroline\InstallationBundle\Bundle\InstallableInterface', class_implements($var)) ? true : false;
                    } catch (\Exception $e) {
                        $this->log($var.' not found.', LogLevel::ERROR);

                        return false;
                    }
                });

                foreach ($bundles as $bundle) {
                    //if the corebundle is already installed, we can do database checks to be sure a plugin is already installed
                    //and not simply set to false in bundles.ini in previous versions.
                    $foundBundle = $this->isBundleAlreadyInstalled($bundle);

                    $previousPackage = $this->findPreviousPackage($bundle);

                    if ($foundBundle && $previousPackage) {
                        $isDistribution = 'claroline/distribution' === $currentPackage->getName();
                        $fromVersionEntity = $this->versionManager->getLatestUpgraded($bundle);
                        $toVersion = $this->versionManager->getCurrent();

                        if ($isDistribution && $fromVersionEntity && $toVersion) {
                            if ($fromVersionEntity->getVersion() === $toVersion && $fromVersionEntity->isUpgraded()) {
                                $this->log('Package '.$bundle.'already upgraded to the '.$toVersion.'version. Skipping...');
                            } else {
                                $operations[$bundle] = new Operation(Operation::UPDATE, $currentPackage, $bundle);
                                $operations[$bundle]->setFromVersion($fromVersionEntity->getVersion());
                                $operations[$bundle]->setToVersion($toVersion);
                            }
                            //old update <= v10
                        } else {
                            $operations[$bundle] = new Operation(Operation::UPDATE, $currentPackage, $bundle);
                            $operations[$bundle]->setFromVersion($previousPackage->getVersion());
                            $operations[$bundle]->setToVersion($currentPackage->getVersion());
                        }
                    } else {
                        //if we found something in the database, it means it was removed from composer.json and not properly uninstalled
                        if ($foundBundle) {
                            $operations[$bundle] = new Operation(Operation::UPDATE, $currentPackage, $bundle);
                            //we don't know wich version it came from so we trigger everything, we only know it's already here
                            $operations[$bundle]->setFromVersion('0.0.0');
                            $operations[$bundle]->setToVersion($currentPackage->getVersion());
                        } else {
                            $operations[$bundle] = new Operation(Operation::INSTALL, $currentPackage, $bundle);
                        }
                    }
                }
            } else {
                $previous = $this->versionManager->openRepository($this->previousRepoFile, true);
                $previousPackage = $previous->findPackage($currentPackage->getName(), '*');
                //old <= v6 package detection
                if (!$previousPackage) {
                    $this->log("Installation of {$currentPackage->getName()} required");
                    $operation = $this->buildOperation(Operation::INSTALL, $currentPackage);
                    $operation->setToVersion($currentPackage->getVersion());
                    $operations[$operation->getBundleFqcn()] = $operation;
                } else {
                    $this->log(sprintf(
                        'Update of %s from %s to %s required',
                        $previousPackage->getName(),
                        $previousPackage->getVersion(),
                        $currentPackage->getVersion()
                    ));
                    $operation = $this->buildOperation(Operation::UPDATE, $currentPackage);
                    $operation->setFromVersion($previousPackage->getVersion());
                    $operation->setToVersion($currentPackage->getVersion());
                    $operations[$operation->getBundleFqcn()] = $operation;
                }
            }
        }

        // TODO: we *should* do something in case a platform package is
        // removed (e.g. if the package is a plugin, at least unregister it)
        // but AFAIK we don't have anything now to support removal of a bundle
        // whose sources are already gone. Maybe the platform installer could
        // look after each update if there are records in the plugin table
        // that don't match any known bundle?

        $this->log('Sorting operations...');
        $bundles = $this->kernel->getBundles();
        $sortedOperations = [];

        foreach ($bundles as $bundle) {
            $bundleClass = $bundle->getNamespace() ?
                $bundle->getNamespace().'\\'.$bundle->getName() :
                $bundle->getName();

            if (isset($operations[$bundleClass])) {
                $sortedOperations[] = $operations[$bundleClass];
            }
        }

        return $sortedOperations;
    }

    public function buildOperationListForBundles(array $bundles, $fromVersion, $toVersion)
    {
        $operations = [];
        foreach ($bundles as $bundle) {
            $bundleFqcn = get_class($bundle['instance']);
            // If plugin is installed, update it. Otherwise, install it.
            if ($this->isBundleAlreadyInstalled($bundleFqcn, false)) {
                $operations[$bundleFqcn] = new Operation(Operation::UPDATE, $bundle['instance'], $bundleFqcn);
                $operations[$bundleFqcn]->setFromVersion($fromVersion);
                $operations[$bundleFqcn]->setToVersion($toVersion);
            } else {
                $operations[$bundleFqcn] = new Operation(Operation::INSTALL, $bundle['instance'], $bundleFqcn);
            }
        }

        return $operations;
    }

    /**
     * Executes a list of install/update operations. Each successful operation
     * is followed by an update of the previous local repository, so that the
     * process can be resumed after an interruption (e.g. due to an error)
     * without triggering again already executed operations. When there's no
     * more operation to execute, the snapshot of the previous local repository
     * is deleted.
     *
     * @param Operation[] $operations
     *
     * @throws \RuntimeException if the the previous repository file is not writable
     */
    public function execute(array $operations)
    {
        $this->log('Executing install/update operations...');
        $bundles = $this->getBundlesByFqcn();

        foreach ($operations as $operation) {
            if (Operation::INSTALL === $operation->getType()) {
                $this->pluginInstaller->install($bundles[$operation->getBundleFqcn()]);
            } elseif (Operation::UPDATE === $operation->getType()) {
                if (array_key_exists($operation->getBundleFqcn(), $bundles)) {
                    $this->pluginInstaller->update(
                      $bundles[$operation->getBundleFqcn()],
                      $operation->getFromVersion(),
                      $operation->getToVersion()
                  );
                    // there's no cleaner way to update the version of a package...
                    $version = new \ReflectionProperty('Composer\Package\Package', 'version');
                    $version->setAccessible(true);
                    $version->setValue($operation->getPackage(), $operation->getToVersion());
                } else {
                    $this->log("Could not update {$operation->getBundleFqcn()}... Please update manually.", LogLevel::ERROR);
                }
            }
        }

        $this->log('Removing previous local repository snapshot...');
        $filesystem = new Filesystem();
        $filesystem->remove($this->previousRepoFile);
        $this->end($operations);
    }

    public function end(array $operations)
    {
        $this->log('Ending operations...');
        $bundles = $this->getBundlesByFqcn();

        foreach ($operations as $operation) {
            if (Operation::INSTALL === $operation->getType()) {
                $this->pluginInstaller->end($bundles[$operation->getBundleFqcn()]);
            } elseif (Operation::UPDATE === $operation->getType()) {
                $this->pluginInstaller->end(
                    $bundles[$operation->getBundleFqcn()],
                    $operation->getFromVersion(),
                    $operation->getToVersion()
                );
            }
        }
    }

    private function getBundlesByFqcn()
    {
        $byFqcn = [];
        foreach ($this->kernel->getBundles(false, true) as $bundle) {
            $fqcn = $bundle->getNamespace() ?
                $bundle->getNamespace().'\\'.$bundle->getName() :
                $bundle->getName();
            $byFqcn[$fqcn] = $bundle;
        }

        return $byFqcn;
    }

    private function findPreviousPackage($bundle)
    {
        $previous = $this->versionManager->openRepository($this->previousRepoFile, true);

        if (!$previous) {
            return;
        }

        foreach ($previous->getCanonicalPackages() as $package) {
            $extra = $package->getExtra();

            if ($extra && array_key_exists('bundles', $extra)) {
                //Otherwise convert the name in a dirty little way
                //If it's a metapackage, check in the bundle list
                foreach ($extra['bundles'] as $installedBundle) {
                    if ($installedBundle === $bundle) {
                        return $package;
                    }
                }
            } else {
                $bundleParts = explode('\\', $bundle);

                //magic !
                $packagePrettyName = preg_replace('/[^A-Za-z0-9]/', '', $package->getPrettyName());
                $bundlePrettyName = strtolower($bundleParts[2]);
                if ($packagePrettyName === $bundlePrettyName) {
                    return $package;
                }
            }
        }

        //Not found. We return null and we'll try to install it later on.
        return;
    }

    //Composer\Package\PackageInterface but the use causes some issue
    private function buildOperation($type, $package)
    {
        $vendorDir = $this->kernel->getRootDir().'/../vendor';
        $targetDir = $package->getTargetDir() ?: '';
        $packageDir = empty($targetDir) ?
            $package->getPrettyName() :
            "{$package->getName()}/{$targetDir}";
        $fqcn = $this->detector->detectBundle("{$vendorDir}/{$packageDir}");

        return new Operation($type, $package, $fqcn);
    }

    private function isBundleAlreadyInstalled($bundleFqcn, $checkCoreBundle = true)
    {
        if ('Claroline\CoreBundle\ClarolineCoreBundle' === $bundleFqcn && !$checkCoreBundle) {
            return true;
        }

        try {
            return $this->om->getRepository('ClarolineCoreBundle:Plugin')->findOneByBundleFQCN($bundleFqcn);
        } catch (TableNotFoundException $e) {
            // we're probably installing the platform because the database isn't here yet do... return false
            return false;
        }
    }
}
