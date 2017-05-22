<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 15:35
 */

namespace Blixit\MSFBundle\Exception;


class MSFPreviousPageNotFoundException extends MSFPageNotFoundException
{
    public function __construct(
        $message = "MSF Previous Page Not Found",
        $code = 500,
        \Exception $previousException = null
    ) {
        $message = "MSF did not found 'before' key in the '".$message."' configuration. To enable use of '__root' path, enable set '__default_paths' to true. ";
        parent::__construct($message, $code, $previousException);
    }
}