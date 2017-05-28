<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 19:43
 */

namespace Blixit\MSFBundle\Form\Flow;


use Symfony\Component\HttpFoundation\RedirectResponse;

interface MSFValidationInterface
{
    /**
     * @param string $userRoute
     * @return null
     */
    public function done($userRoute = '');

    /**
     * @param $msfData
     * @param $object
     * @return bool
     */
    public function validate($msfData, &$object);

    /**
     * @param \Exception $e
     * @return mixed
     */
    public function onFailure(\Exception $e);
}