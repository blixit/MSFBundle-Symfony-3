<?php

namespace Blixit\MSFBundle\Controller;

use Blixit\MSFBundle\Form\TemplateTypes\MSFTesterType;
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

        $time = microtime();

        $msf = $this->container->get('msf')->create(MSFTesterType::class,"blog");
        $form = $msf->getForm();
        $title = $msf->getLabel();
        $buttons = $msf->getButtons();
        $menu = $msf->getMenu('msfbundle');

        $time = microtime() - $time;


        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() ) {
            $msf->done() ;
        }

        return $this->render('BlixitMultiStepFormBundle:Default:default.html.twig',[
            'form'  =>  $form->createView(),
            'title' => $title,
            'buttons' => $buttons,
            'msf_time'  => $time,
            'menu'  => $menu,
        ]);
    }
}
