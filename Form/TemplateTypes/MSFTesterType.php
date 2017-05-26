<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 26/05/17
 * Time: 14:56
 */

namespace Blixit\MSFBundle\Form\TemplateTypes;

use Blixit\MSFBundle\Core\MSFService;
use Blixit\MSFBundle\Entity\Example\Blog;
use Blixit\MSFBundle\Form\Builder\MSFBuilderInterface;
use Blixit\MSFBundle\Form\Type\MSFAbstractType;

class MSFTesterType
    extends MSFAbstractType
{
    function __construct(MSFService $msf, $defaultState)
    {
        parent::__construct($msf, $defaultState);
    }

    public function configure()
    {
        return [
            '__default_formType_path'=>'\Blixit\MSFBundle\Form\ExampleTypes',
            'blog'  =>  [
                'entity'    => Blog::class
            ]
        ];
    }

    /**
     * Modify the form
     * @return $this
     */
    public function buildMSF()
    {
        // TODO: Implement buildMSF() method.
    }

    public function setCancelPage($page)
    {
        // TODO: Implement setCancelPage() method.
    }

    public function setNextPage($page)
    {
        // TODO: Implement setNextPage() method.
    }

    public function setPreviousPage($page)
    {
        // TODO: Implement setPreviousPage() method.
    }

    public function getSteps()
    {
        // TODO: Implement getSteps() method.
    }

    public function getStepsWithLink()
    {
        // TODO: Implement getStepsWithLink() method.
    }

    public function initTransitions()
    {
        // TODO: Implement initTransitions() method.
    }

    public function isAvailable($state)
    {
        // TODO: Implement isAvailable() method.
    }
}