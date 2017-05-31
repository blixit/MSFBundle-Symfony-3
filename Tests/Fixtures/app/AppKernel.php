<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 31/05/17
 * Time: 08:46
 */

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{

    public function registerBundles()
    {
        return array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \Blixit\MSFBundle\BlixitMultiStepFormBundle()
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_test.yml');
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        $cacheDir = sys_get_temp_dir().'/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        return $cacheDir;
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        $logDir = sys_get_temp_dir().'/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        return $logDir;
    }
}