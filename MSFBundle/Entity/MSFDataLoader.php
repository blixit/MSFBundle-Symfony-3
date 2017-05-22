<?php

namespace Blixit\MSFBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Serializer;

/**
 * MSFDataLoader
 *
 * @ORM\Table(name="msf_data_loader")
 * @ORM\Entity(repositoryClass="Blixit\MSFBundle\Repository\MSFDataLoaderRepository")
 */
class MSFDataLoader
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=32)
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;

    /**
     * MSFDataLoader constructor.
     */
    function __construct()
    {
        $this->data = "{}"; //json object
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set state
     *
     * @param string $state
     *
     * @return MSFDataLoader
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set data
     *
     * @param string $data
     *
     * @return MSFDataLoader
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set data with an array
     * @param $data
     * @param Serializer $serializer
     * @param string $format
     * @return $this
     */
    public function setArrayData($data, Serializer $serializer, $format = 'json')
    {
        $this->data = $serializer->serialize($data, $format);

        return $this;
    }

    /**
     * Set data with an object
     * @param $object
     * @param Serializer $serializer
     * @param string $format
     * @return $this
     */
    public function setObjectData($object, Serializer $serializer, $format = 'json')
    {
        $this->data = $serializer->serialize($object, $format);

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

}

