<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Controller\Administration;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Claroline\CoreBundle\Event\StrictDispatcher;
use Claroline\CoreBundle\Manager\ToolManager;
use Claroline\CoreBundle\Manager\DependencyManager;
use Claroline\CoreBundle\Manager\BundleManager;
use Claroline\CoreBundle\Manager\IPWhiteListManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;

class PackageController extends Controller
{
    private $toolManager;
    private $eventDispatcher;
    private $adminToolPlugin;
    private $sc;
    private $ipwlm;
    private $bundleManager;
    private $platformConfigHandler;

    /**
     * @DI\InjectParams({
     *      "eventDispatcher" = @DI\Inject("claroline.event.event_dispatcher"),
     *      "toolManager"     = @DI\Inject("claroline.manager.tool_manager"),
     *      "dm"              = @DI\Inject("claroline.manager.dependency_manager"),
     *      "sc"              = @DI\Inject("security.context"),
     *      "ipwlm"           = @DI\Inject("claroline.manager.ip_white_list_manager"),
     *      "bundleManager"   = @DI\Inject("claroline.manager.bundle_manager"),
     *      "configHandler"   = @DI\Inject("claroline.config.platform_config_handler")
     * })
     */
    public function __construct(
        StrictDispatcher             $eventDispatcher,
        ToolManager                  $toolManager,
        SecurityContextInterface     $sc,
        DependencyManager            $dm,
        IPWhiteListManager           $ipwlm,
        BundleManager                $bundleManager,
        PlatformConfigurationHandler $configHandler

    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->toolManager     = $toolManager;
        $this->adminToolPlugin = $toolManager->getAdminToolByName('platform_packages');
        $this->sc              = $sc;
        $this->dm              = $dm;
        $this->ipwlm           = $ipwlm;
        $this->bundleManager   = $bundleManager;
        $this->configHandler   = $configHandler;
    }

    /**
     * @EXT\Route(
     *     "/",
     *     name="claro_admin_plugins"
     * )
     *
     * @EXT\Template()
     *
     * Display the plugin list
     *
     * @return Response
     */
    public function listAction()
    {
        $this->checkOpen();
        $coreBundle = $this->bundleManager->getBundle('CoreBundle');
        $coreVersion = $coreBundle->getVersion();
        $api = $this->configHandler->getParameter('repository_api');
        $url = $api . "/version/$coreVersion/tags/last";
        //ask the server wich are the last available packages now.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        $fetched = json_decode($data);
        $installed = $this->bundleManager->getInstalled();
        $uninstalled = $this->bundleManager->getUninstalledFromServer($fetched);

        return array(
            'fetched' => $fetched,
            'installed' => $installed,
            'uninstalled' => $uninstalled
        );
    }

    /**
     * @EXT\Route(
     *     "/bundle/{bundle}/install",
     *     name="claro_admin_plugins_install"
     * )
     *
     * Install a plugin.
     *
     * @return Response
     */
    public function installFromRemoteAction($bundle)
    {
        $this->checkOpen();
        $this->bundleManager->installRemoteBundle($bundle);
    }

    /**
     * @EXT\Route(
     *     "/install/log",
     *     name="claro_admin_plugins_log"
     * )
     *
     * Install a plugin.
     *
     * @return Response
     */
    public function displayUpdateLog()
    {
        $this->checkOpen();

        return file_get_contents($this->bundleManager->getLogFile());
    }

    /**
     * @EXT\Route(
     *     "/plugin/parameters/{pluginShortName}",
     *     name="claro_admin_plugin_parameters"
     * )
     */
    public function pluginParametersAction($pluginShortName)
    {
        $this->checkOpen();
        $eventName = "plugin_options_{$pluginShortName}";
        $event = $this->eventDispatcher->dispatch($eventName, 'PluginOptions', array());

        return $event->getResponse();
    }

    private function checkOpen()
    {
        if ($this->sc->isGranted('OPEN', $this->adminToolPlugin)) {
            return true;
        }

        throw new AccessDeniedException();
    }
}
