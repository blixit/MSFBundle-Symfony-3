<?php

namespace Blixit\MSFBundle\Controller;

use Blixit\MSFBundle\Entity\MSFDataLoader;
use Blixit\MSFBundle\Exception\MSFPreviousPageNotFoundException;
use Blixit\MSFBundle\Form\ExampleTypes\ArticleType;
use Blixit\MSFBundle\Form\ExampleTypes\MSFRegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{

    /**
     * @Route("/msfbundle", name="msfbundle")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request){

        /**
         * Symfony Form
         */
        $form = $this->createForm(ArticleType::class);
        //$form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //return $this->redirectToRoute('ads_show', array('id' => $ad->getId()));
        }

        /**
         * Blixit MSFBundle Form
         */
        $msf = $this->container->get('msf')->create(MSFRegistrationType::class);
        $form2 = $msf->getForm();

        $form2->handleRequest($request);

        if($form2->isSubmitted() && $form2->isValid() ) {

            $msf->done() ;
        }


        return $this->render('BlixitMultiStepFormBundle:Default:default.html.twig',[
            'form'  =>  $form->createView(),
            'form2'  =>  $form2->createView()
        ]);
    }
}
