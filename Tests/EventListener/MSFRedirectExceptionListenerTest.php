<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 30/05/17
 * Time: 23:13
 */

namespace Blixit\MSFBundle\Tests\EventListener;

use Blixit\MSFBundle\EventListener\MSFRedirectExceptionListener;
use Blixit\MSFBundle\Exception\MSFRedirectException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class MSFRedirectExceptionListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor(){
        $redirection = new RedirectResponse("https://github.com/blixit/MSFBundle-Symfony-3");
        $exception = new MSFRedirectException($redirection);

        $listener = new MSFRedirectExceptionListener();
        $responseForException = $this->getMockBuilder(GetResponseForExceptionEvent::class)->disableOriginalConstructor()->getMock();
        $responseForException->method("getException")->willReturn($exception);
        $responseForException->method("setResponse")->willReturn(true);

        $listener->onKernelException($responseForException);

    }
}
