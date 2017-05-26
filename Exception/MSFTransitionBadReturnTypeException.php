<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 23/05/17
 * Time: 11:15
 */

namespace Blixit\MSFBundle\Exception;


class MSFTransitionBadReturnTypeException extends \Exception
{
    public function __construct(
        $state,
        $transition,
        $message = '',
        $code = 500,
        \Exception $previousException = null
    ) {
        if(empty($message)){
            $message = "The transition '".$transition."' found on the '".$state."' configuration should return a name or null or a callable.";
        }
        parent::__construct($message, $code, $previousException);
    }
}