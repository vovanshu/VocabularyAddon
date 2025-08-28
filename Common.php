<?php declare(strict_types=1);

namespace VocabularyAddon;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Vocabulary;
use Omeka\Permissions\Acl;
use Interop\Container\ContainerInterface;

trait Common
{

    protected $services;

    protected $requestedName;

    protected $options;

    protected $acl;

    protected $connection;

    protected $settings;

    protected $config;

    protected $apiManager;

    protected $ApiAdapter = [];

    protected $logger;
    
    protected $entityManager;
    
    /**
     * Set the service locator.
     *
     * @param $serviceLocator
     */
    public function setServiceLocator($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Get the service locator.
     *
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function getAdapter($resourceName)
    {

        if($this->serviceLocator){
            if(empty($this->ApiAdapter[$resourceName])){
                $this->ApiAdapter[$resourceName] = $this->getServiceLocator()->get('Omeka\ApiAdapterManager')->get($resourceName);
            }
            return $this->ApiAdapter[$resourceName];
        }
        return;

    }

    public function getConnection()
    {

        if($this->serviceLocator){
            if(!$this->connection){
                $this->connection = $this->getServiceLocator()->get('Omeka\Connection');
            }
            return $this->connection;
        }
        return;

    }

    public function getLogger()
    {

        if($this->serviceLocator){
            if(!$this->logger){
                $this->logger = $this->getServiceLocator()->get('Omeka\Logger');
            }
            return $this->logger;
        }
        return;

    }

    public function getApiManager()
    {

        if($this->serviceLocator){
            if(!$this->apiManager){
                $this->apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
            }
            return $this->apiManager;
        }
        return;

    }

    public function getEntityManager()
    {

        if($this->serviceLocator){
            if(!$this->entityManager){
                $this->entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
            }
            return $this->entityManager;
        }
        return;

    }

    public function getAcl()
    {

        if($this->serviceLocator){
            if(!$this->acl){
                $this->acl = $this->getServiceLocator()->get('Omeka\Acl');
            }
            return $this->acl;
        }
        return;

    }

    public function getSettings()
    {

        if($this->serviceLocator){
            if(!$this->settings){
                $this->settings = $this->getServiceLocator()->get('Omeka\Settings');
            }
            return $this->settings;
        }
        return;

    }

    public function getUserSettings()
    {

        if($this->serviceLocator){
            if(!$this->userSettings){
                $this->userSettings = $this->getServiceLocator()->get('Omeka\Settings\User');
            }
            return $this->userSettings;
        }
        return;

    }

    public function getConfigs()
    {

        if($this->serviceLocator){
            if(!$this->config){
                $this->config = $this->getServiceLocator()->get('Config');
            }
            return $this->config;
        }
        return;
        
    }

    public function getConf($name = Null, $param = Null, $all = False)
    {

        $config = $this->getConfigs()['VocabularyAddon']['config'];
        if(!empty($name) && !empty($config[$name])){
            if(!empty($param)){
                if(!empty($config[$name][$param])){
                    return $config[$name][$param];
                }else{
                    return False;
                }
            }else{
                return $config[$name];
            }
        }else{
            if($all){
                return $config;
            }else{
                return False;
            }
        }

    }

    public function getOps($name)
    {
        return $this->getConf('options', $name);
    }

    public function getSets($name, $callback = [])
    {
        
        if(!empty($opt = $this->getOps($name))){
            $r = $this->getSettings()->get($opt);
        }else{
            $r = $this->getSettings()->get($name);
        }
        if(!empty($callback)){
            $r = call_user_func_array($callback, [$r]);
        }
        return $r;
        
    }

    public function setSets($name, $value)
    {
        
        $opt = $this->getOps($name);
        if(!empty($opt)){
            $this->getSettings()->set($opt, $value);
        }else{
            $this->getSettings()->set($name, $value);
        }
        
    }

    public function getCurentUserID()
    {

        $user = $this->getAcl()->getAuthenticationService()->getIdentity();
        if($user){
            return $user->getId();
        }
        return Null;

    }

    private function getRoleCurentUser()
    {

        $r = 'public';
        $rc = $this->getAcl()->getAuthenticationService()->getIdentity();
        if($rc){
            $r = $rc->getRoleId();
        }
        return $r;

    }

    private function getRoleUser($userID)
    {

        $r = $this->getUser($userID);
        if(!empty($r['role'])){
            return $r['role'];
        }
        return False;

    }

    private function getUser($userID)
    {

        $rc = $this->getConnection()->executeQuery("SELECT id, name, email, role, created FROM `user` WHERE `id` = '{$userID}' LIMIT 1;");
        if(!empty($rc)){
            return $rc->fetchAssociative();
        }
        return False;

    }

    private function getUserEntry($id)
    {
        return $this->getAdapter('users')->findEntity($id);
    }

    public function whoIt($userID = Null)
    {

        if(empty($userID)){
            $userID = $this->getCurentUserID();
        }
        $rc = $this->getConnection()->executeQuery("SELECT name, email, role FROM `user` WHERE `id` = '{$userID}' LIMIT 1;");
        if(!empty($rc)){
            return $rc->fetchAssociative();
        }
        return False;

    }

    public function getTplStrByConf($name, $param = Null)
    {

        $rc = $this->getConf($name, $param);
        if(!empty($rc)){
            return $this->translate($rc);
        }
        return False;

    }

    private function getResourceTemplate($id)
    {

        return $this->getServiceLocator()
            ->get('Omeka\ApiAdapterManager')
            ->get('resource_templates')
            ->findEntity($id);

    }

    private function getSelectVocabularies()
    {

        $response = $this->api()->search('vocabularies');
        $vocabularies = $response->getContent();
        foreach ($vocabularies as $vocabulary){
            $result[$vocabulary->id()] = $vocabulary->label();
        }
        return $result;

    }

    private function getVocabularyID($data = Null)
    {

        $params = $this->params()->fromRoute();
        $id = Null;
        if(!empty($params['id'])){
            $id = $params['id'];
        }
        if(!empty($data['o:vocabulary'])){
            $id = $data['o:vocabulary']->jsonSerialize()['o:id'];
        }
        return $id;

    }

    private function getVocabularyEntry($id)
    {
        return $this->getAdapter('vocabularies')->findEntity($id);
    }

}
