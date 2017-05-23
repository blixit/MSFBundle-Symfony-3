<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 17:53
 */

namespace Blixit\MSFBundle\Form\Flow;


interface MSFFlowInterface
{

    /**
     * Navigate to the next form, the previous form, cancel or redirect to the set redirection path
     * @return null
     */
    public function done();

    public function hasCancel();

    public function cancel();

    public function getCancelPage();

    public function hasNext();

    public function next();

    public function getNextPage();

    public function hasPrevious();

    public function previous();

    public function getPreviousPage();
}