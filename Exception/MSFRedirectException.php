<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 13:29
 */

namespace Blixit\MSFBundle\Exception;

use Symfony\Component\HttpFoundation\RedirectResponse;

class MSFRedirectException extends \Exception
{
    private $redirectResponse;

    public function __construct(
        RedirectResponse $redirectResponse,
        $message = 'MSF Redirection',
        $code = 0,
        \Exception $previousException = null
    ) {
        $this->redirectResponse = $redirectResponse;
        parent::__construct($message, $code, $previousException);
    }

    public function getRedirectResponse()
    {
        return $this->redirectResponse;
    }
}