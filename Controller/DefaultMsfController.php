<?php

namespace Blixit\MSFBundle\Controller;

use Blixit\MSFBundle\Entity\MSFDataLoader;
use Blixit\MSFBundle\Exception\MSFPreviousPageNotFoundException;
use Blixit\MSFBundle\Form\ExampleTypes\ArticleType;
use Blixit\MSFBundle\Form\ExampleTypes\MSFRegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultMsfController extends Controller
{

    /**
     * @Route("/msfbundle", name="msfbundle")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request){

        /**
         * Blixit MSFBundle Form
         */
        $msf = $this->container->get('msf')->create(MSFRegistrationType::class);
        $form = $msf->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() ) {

            $msf->done() ;
        }


        return $this->render('BlixitMultiStepFormBundle:Default:default.html.twig',[
            'form'  =>  $form->createView()
        ]);
    }
}
