<?php

namespace Icap\WikiBundle\Listener;

use Claroline\CoreBundle\Event\Log\LogCreateDelegateViewEvent;
use Twig\Environment;

/**
 * LogListener.
 */
class LogListener
{
    /** @var Environment */
    private $templating;

    /**
     * LogListener constructor.
     *
     * @param Environment $templating
     */
    public function __construct(Environment $templating)
    {
        $this->templating = $templating;
    }

    /**
     * @param LogCreateDelegateViewEvent $event
     */
    public function onCreateLogListItem(LogCreateDelegateViewEvent $event)
    {
        $content = $this->templating->render(
            'IcapWikiBundle:log:log_list_item.html.twig',
            ['log' => $event->getLog()]
        );

        $event->setResponseContent($content);
        $event->stopPropagation();
    }

    /**
     * @param LogCreateDelegateViewEvent $event
     */
    public function onSectionCreateLogDetails(LogCreateDelegateViewEvent $event)
    {
        $content = $this->templating->render(
            'IcapWikiBundle:log:log_details.html.twig',
            [
                'log' => $event->getLog(),
                'listItemView' => $this->templating->render(
                    'IcapWikiBundle:log:log_list_item.html.twig',
                    ['log' => $event->getLog()]
                ),
            ]
        );

        $event->setResponseContent($content);
        $event->stopPropagation();
    }
}
