<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 23/05/17
 * Time: 09:31
 */

namespace Blixit\MSFBundle\Exception;


class MSFFailedToValidateFormException extends \Exception
{
    public function __construct(
        $state,
        $flowDirection,
        $message = '',
        $code = 500,
        \Exception $previousException = null
    ) {
        if(empty($message)){
            $message = "The validation callback '".$flowDirection."' on the '".$state."' configuration has failed. See :\n".$message;
        }
        parent::__construct($message, $code, $previousException);
    }
}