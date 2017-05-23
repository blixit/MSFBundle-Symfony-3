<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 23/05/17
 * Time: 08:33
 */

namespace Blixit\MSFBundle\Exception;


use \Exception;

class MSFConfigurationNotFoundException extends Exception
{
    public function __construct(
        $state,
        $key,
        $message = '',
        $code = 500,
        \Exception $previousException = null
    ) {
        if(empty($message)){
            $message = "Configuration Not Found the key '".$key."' on the '".$state."' configuration ";
        }
        parent::__construct($message, $code, $previousException);
    }
}