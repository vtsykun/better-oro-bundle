<?php

namespace Okvpn\Bundle\BetterOroBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class TemplateControllerListener
{
    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $routesToTemplateMap = $this->getRoutesForReplace();
        $route = $request->attributes->get('_route');

        if (array_key_exists($route, $routesToTemplateMap)) {
            $request->attributes->set('_template', $routesToTemplateMap[$route]);
        }
    }

    /**
     * @return array
     */
    protected function getRoutesForReplace()
    {
        return [
            'oro_message_queue_child_jobs' => '@OkvpnBetterOro/MessageQueue/childJobs.html.twig',
        ];
    }
}
