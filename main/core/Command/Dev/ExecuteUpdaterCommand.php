<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Command\Dev;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Library\Maintenance\MaintenanceHandler;
use Claroline\InstallationBundle\Updater\Updater;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Executes a single plugin updater.
 */
class ExecuteUpdaterCommand extends Command
{
    private $om;
    private $updaters;

    /**
     * @param ObjectManager                $om
     * @param ContainerInterface|Updater[] $updaters The Updater service registry
     */
    public function __construct(ObjectManager $om, ContainerInterface $updaters)
    {
        $this->om = $om;
        $this->updaters = $updaters;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Executes a single plugin updater.')
            ->addArgument('updater_class', InputArgument::REQUIRED, 'The fully qualified classname of the updater that should be executed.')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'When set to true, the Updater will be executed regardless if it has been already.'
            );
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $updaterClass = $input->getArgument('updater_class');

        if (!$this->updaters->has($updaterClass)) {
            throw new InvalidArgumentException(sprintf('Updater "%s" does not exist.', $updaterClass));
        }

        // TODO unless --force is passed, check that updater has not been executed yet (abort otherwise)

        MaintenanceHandler::enableMaintenance();

        $output->writeln(
            sprintf('<comment>%s - Executing pre-update operations from "%s"...</comment>', date('H:i:s'), $updaterClass)
        );

        /** @var Updater $updater */
        $updater = $this->updaters->get($updaterClass);
        $updater->preUpdate();

        $output->writeln(
            sprintf('<comment>%s - Executing post-update operations from "%s"...</comment>', date('H:i:s'), $updaterClass)
        );

        $updater->postUpdate();

        MaintenanceHandler::disableMaintenance();

        $output->writeln(
            sprintf('<comment>%s - Update terminated.</comment>', date('H:i:s'))
        );

        return 0;
    }
}
