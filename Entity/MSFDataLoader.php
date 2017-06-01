<?php

namespace Blixit\MSFBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     * Use to cache $data
     * @var array
     */
    private $arrayData = [];

    /**
     * MSFDataLoader constructor.
     */
    function __construct()
    {
        $this->data = "[]"; //json object
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
     * @return MSFDataLoader
     * @throws \Exception
     */
    public function setData($data)
    {
        if(! is_string($data))
            throw new \Exception("The given value is not a string. ");

        $this->data = $data;
        $this->arrayData = null;

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

    /**
     * Set data with an array
     * @param $data
     * @param $serializer
     * @param string $format
     * @return $this
     */
    public function setArrayData($data, $serializer, $format = 'json')
    {
        $this->data = $serializer->serialize($data, $format);
        $this->arrayData = null;

        return $this;
    }

    /**
     * @param $serializer
     * @return mixed
     */
    public function getArrayData($serializer){
        if(empty($this->arrayData)){
            $this->arrayData = $serializer->deserialize($this->data,'array','json');
        }

        return $this->arrayData;
    }

}

