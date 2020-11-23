<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Library\Mailing;

use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class TransportFactory
{
    private const COMMAND = '/usr/sbin/sendmail -bs';
    private const TIME_OUT = 30;

    private $configHandler;
    private $eventDispatcher;
    private $logger;

    public function __construct(
        PlatformConfigurationHandler $configHandler,
        EventDispatcherInterface $eventDispatcher,
        Logger $logger
    ) {
        $this->configHandler = $configHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function getTransport()
    {
        $type = $this->configHandler->getParameter('mailer_transport');

        if ('sendmail' === $type) {
            return new SendmailTransport(
                self::COMMAND,
                $this->eventDispatcher,
                $this->logger
            );
        } elseif ('gmail' === $type) {
            $transport = new GmailSmtpTransport(
                $this->configHandler->getParameter('mailer_username'),
                $this->configHandler->getParameter('mailer_password'),
                $this->eventDispatcher,
                $this->logger
            );
            $transport->setUsername($this->configHandler->getParameter('mailer_username'));
            $transport->setPassword($this->configHandler->getParameter('mailer_password'));

            return $transport;
        }

        // Default smtp
        $encryption = null;
        if (!empty($this->configHandler->getParameter('mailer_encryption')) && 'none' !== $this->configHandler->getParameter('mailer_encryption')) {
            $encryption = (bool) $this->configHandler->getParameter('mailer_encryption');
        }

        $transport = new EsmtpTransport(
            $this->configHandler->getParameter('mailer_host'),
            $this->configHandler->getParameter('mailer_port'),
            $encryption,
            $this->eventDispatcher,
            $this->logger
        );
        $transport->setUsername($this->configHandler->getParameter('mailer_username'));
        $transport->setPassword($this->configHandler->getParameter('mailer_password'));
        // should probably be configurable too
        $transport->getStream()->setTimeout(self::TIME_OUT);
        $transport->setSourceIp(null);

        return $transport;
    }
}
