<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 24/05/17
 * Time: 19:35
 */

namespace Blixit\MSFBundle\Form\ExampleTypes;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageType
    extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('url')->add('mime_type')->add('filename');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Blixit\MSFBundle\Entity\Example\Image'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'blixit_multi_step_formbundle';
    }
}