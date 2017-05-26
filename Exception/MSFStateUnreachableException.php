<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 26/05/17
 * Time: 22:51
 */

namespace Blixit\MSFBundle\Exception;


use Throwable;

class MSFStateUnreachableException
    extends MSFBadStateException
{
    public function __construct($state = '', $message = "This state is unreachable.", $code = 0, Throwable $previous = null)
    {
        parent::__construct($state, $message, $code, $previous);
        $this->message .= " A state is reachable by url if the form has already reached that state or if the related data are available on the session.";
    }
}