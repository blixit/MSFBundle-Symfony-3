<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 13:31
 */

namespace Blixit\MSFBundle\EventListener;

use Blixit\MSFBundle\Exception\MSFRedirectException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class MSFRedirectExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof MSFRedirectException) {
            $event->setResponse($event->getException()->getRedirectResponse());
        }
    }
}