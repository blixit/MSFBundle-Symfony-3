<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 20:34
 */

namespace Blixit\MSFBundle\Exception;


class MSFNextPageNotFoundException
    extends MSFPageNotFoundException
{
    public function __construct(
        $message = "MSF Next Page Not Found",
        $code = 500,
        \Exception $previousException = null
    ) {
        $message = "MSF did not found 'redirection' key in the '".$message."' configuration. To enable use of '__final_route' path, enable set '__default_paths' to true. ";
        parent::__construct($message, $code, $previousException);
    }
}