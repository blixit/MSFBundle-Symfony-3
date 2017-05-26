<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 26/05/17
 * Time: 16:21
 */

namespace Blixit\MSFBundle\Exception;


use Throwable;

class MSFBadStateException
    extends \Exception
{
    public function __construct($state='',$message = "This state is not available.", $code = 0, Throwable $previous = null)
    {
        if( ! empty($state)){
            $message = "'".$state."' is not an available state.";
        }
        parent::__construct($message, $code, $previous);
    }
}