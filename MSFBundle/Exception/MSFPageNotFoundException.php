<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 15:33
 */

namespace Blixit\MSFBundle\Exception;


abstract class MSFPageNotFoundException extends \Exception
{
    public function __construct(
        $message = 'MSF Page Not Found',
        $code = 500,
        \Exception $previousException = null
    ) {
        parent::__construct($message, $code, $previousException);
    }
}