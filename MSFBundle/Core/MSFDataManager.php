<?php
/**
 * Created by PhpStorm.
 * User: blixit
 * Date: 21/05/17
 * Time: 12:31
 */

namespace Blixit\MSFBundle\Core;

use Symfony\Component\HttpFoundation\Session\Session ;
use Doctrine\ORM\EntityManager;

class MSFDataManager
{
    /**
     * Database storage
     */
    const STORAGE_DB = 'db';
    /**
     * Session storage
     */
    const STORAGE_SESSION = 'se';
    /**
     * File system storage
     */
    const STORAGE_MEMORY = 'in';

    /**
     * @var string
     */
    private $storageSystem;

    /**
     * @var array
     */
    private $store;

    /**
     * @var Session
     */
    public $session;

    /**
     * @var EntityManager
     */
    public $entityManager;

    /**
     * MSFDataManager constructor.
     * @param EntityManager $entityManager
     * @param Session $session
     * @param string $storageSystem
     */
    function __construct(EntityManager $entityManager, Session $session, $storageSystem = self::STORAGE_SESSION)
    {
        $this->storageSystem = $storageSystem;
        $this->entityManager = $entityManager;
        $this->session = $session;
    }

    /**
     * @return mixed
     */
    public final function getStore()
    {
        return $this->store;
    }

    /**
     * @param mixed $store
     * @return $this
     */
    public final function setStore($store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * Persist data in the store with a key
     * @param $key
     * @param $entity
     * @return $this
     */
    public final function persistAs($key, $entity)
    {
        $this->store[$key] = $entity;
        return $this;
    }

    public final function flush(){

        switch ($this->storageSystem){
            case self::STORAGE_DB:
                $this->flushDB();
                break;
            case self::STORAGE_SESSION:
                $this->flushSession();
                break;
            default:
                throw new \Exception("This storage is not supported yet.");
        }
    }

    private final function flushDB(){
        foreach ($this->store as $item) {
            $this->entityManager->persist($item);
        }
        $this->entityManager->flush();
    }

    private final function flushSession(){
        foreach ($this->store as $key => $item) {
            $this->session->set('__msf_'.$key,$item);
        }
    }
}