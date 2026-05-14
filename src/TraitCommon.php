<?php

namespace VocabularyAddon;

trait TraitCommon
{

    protected $moduleName = __NAMESPACE__;

    protected $mvcEvent;

    protected $serviceLocator;

    protected $applicationRouteMatch;

    protected $acl;

    protected $connection;

    protected $settings;

    protected $siteSettings;

    protected $userSettings;

    protected $config;

    protected $apiManager;

    protected $ModuleManager;

    protected $ControllerPluginManager;

    protected $ApiAdapterManager;

    protected $ApiAdapter = [];

    protected $entityManager;

    protected $logger;


    protected function isAppDevMode(): bool
    {

        if ((isset($_SERVER['APPLICATION_ENV']) && 'development' == $_SERVER['APPLICATION_ENV']) ||
        (isset($_SERVER['REDIRECT_APPLICATION_ENV']) && 'development' == $_SERVER['REDIRECT_APPLICATION_ENV'])){
            return True;
        }
        return False;

    }

    protected function modulePath(): string
    {
        return dirname((new \ReflectionClass(static::class))->getFileName());
    }

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

    public function setMvcEvent($mvcEvent)
    {
        $this->mvcEvent = $mvcEvent;
    }

    public function getMvcEvent()
    {
        return $this->mvcEvent;
    }

    public function getApiAdapterManager($resourceName = null)
    {

        if($this->serviceLocator){
            if(!$this->ApiAdapterManager){
                $this->ApiAdapterManager = $this->getServiceLocator()->get('Omeka\ApiAdapterManager');
            }
            if(!empty($resourceName)){
                if(empty($this->ApiAdapters[$resourceName])){
                    $this->ApiAdapters[$resourceName] = $this->ApiAdapterManager->get($resourceName);
                }
                return $this->ApiAdapters[$resourceName];
            }
            return $this->ApiAdapterManager;
        }
        return;

    }

    public function getModuleManager()
    {

        if($this->ModuleManager){
            if(!$this->ModuleManager){
                $this->ModuleManager = $this->getServiceLocator()->get('Omeka\ModuleManager');
            }
            return $this->ModuleManager;
        }
        return;

    }

    public function getApplicationRouteMatch()
    {

        if($this->applicationRouteMatch){
            if(!$this->applicationRouteMatch){
                $this->applicationRouteMatch = $this->getServiceLocator()->get('Application')->getMvcEvent()->getRouteMatch();
            }
            return $this->applicationRouteMatch;
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

    public function getControllerPluginManager()
    {

        if($this->serviceLocator){
            if(!$this->ControllerPluginManager){
                $this->ControllerPluginManager = $this->getServiceLocator()->get('ControllerPluginManager');
            }
            return $this->ControllerPluginManager;
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

    public function getSiteSettings()
    {

        if($this->serviceLocator){
            if(!$this->siteSettings){
                $this->siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
            }
            return $this->siteSettings;
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

    public function getMediaIngesters()
    {
        return $this->getServiceLocator()->get('Omeka\Media\Ingester\Manager');
    }

    public function getConf($name = Null, $param = Null, $default = Null, $all = False)
    {

        $config = $this->getConfigs()[$this->moduleName];
        if(!empty($name)){
            if(!empty($config[$name])){
                if(!empty($param)){
                    if(isset($config[$name][$param])){
                        return $config[$name][$param];
                    }else{
                        return $default;
                    }
                }else{
                    return $config[$name];
                }
            }
        }else{
            if($all){
                return $config;
            }else{
                return $default;
            }
        }

    }

    public function getOps($name, $default = Null)
    {

        return $this->getConf('options', $name, $default);        

    }

    public function getSets($name, $callback = [])
    {
        
        $ops = $this->getOps($name, $name);
        $r = $this->getSettings()->get($ops, $this->getConf('settings', $ops));
        if(!empty($callback)){
            $r = call_user_func_array($callback, [$r]);
        }
        return $r;
        
    }

    public function setSets($name, $value)
    {
        
        $ops = $this->getOps($name, $name);
        $this->getSettings()->set($ops, $value);
        
    }

    public function getSiteSets($name, $siteID = Null, $callback = [])
    {

        $ops = $this->getOps($name, $name);
        if($siteID){
            $r = $this->getSiteSettings()->get($ops, $this->getConf('settings', $ops), $siteID);
        }else{
            $r = $this->getSiteSettings()->get($ops, $this->getConf('settings', $ops));
        }
        
        if(!empty($callback)){
            $r = call_user_func_array($callback, [$r]);
        }
        return $r;
        
    }

    public function setSiteSets($name, $value)
    {
        
        $ops = $this->getOps($name, $name);
        $this->getSiteSettings()->set($ops, $value);
        
    }

    public function getSiteID($slug)
    {

        $rc = $this->getConnection()->executeQuery("SELECT id FROM `site` WHERE `slug` = '{$slug}' LIMIT 1;")->fetchAssociative();
        if(!empty($rc['id'])){
            return $rc['id'];
        }
        return False;
        
    }

    public function getUserSets($name, $userId, $callback = [])
    {
        
        $name = (($opt = $this->getOps($name)) ? $opt : $name);
        $r = $this->getUserSettings()->get($name, Null, $userId);
        if(!empty($callback)){
            $r = call_user_func_array($callback, [$r]);
        }
        return $r;
        
    }

    public function setUserSets($userId, $name, $value)
    {

        $name = (($opt = $this->getOps($name)) ? $opt : $name);
        $this->getUserSettings()->set($name, $value, $userId);

    }

    public function getCurrentUserID()
    {

        $user = $this->getAcl()->getAuthenticationService()->getIdentity();
        if($user){
            return $user->getId();
        }
        return Null;

    }

    public function getRoleCurrentUser()
    {

        $r = 'public';
        $rc = $this->getAcl()->getAuthenticationService()->getIdentity();
        if($rc){
            $r = $rc->getRoleId();
        }
        return $r;

    }

    public function userIsGuest()
    {
    
        return ($this->getRoleCurrentUser() == 'public');

    }

    public function userIsGlobalAdmin()
    {
        
        return ($this->getRoleCurrentUser() == $this->getAcl()::ROLE_GLOBAL_ADMIN);
        
    }

    public function getRoleUser($userID)
    {

        $r = $this->getUser($userID);
        if(!empty($r['role'])){
            return $r['role'];
        }
        return False;

    }

    public function getUser($userID)
    {

        $rc = $this->getConnection()->executeQuery("SELECT id, name, email, role, created FROM `user` WHERE `id` = '{$userID}' LIMIT 1;");
        if(!empty($rc)){
            return $rc->fetchAssociative();
        }
        return False;

    }

    private function getUserEntry($id)
    {
        return $this->getApiAdapterManager('users')->findEntity($id);
    }

    public function getStrConf($name, $param = Null)
    {

        $rc = $this->getConf($name, $param);
        if(!empty($rc)){
            return $this->translate($rc);
        }
        return False;

    }

    public function getListMediaTypes()
    {

        foreach ($this->getMediaIngesters()->getRegisteredNames() as $ingester) {
            $r[$ingester] = $this->getMediaIngesters()->get($ingester)->getLabel();
        }
        return $r;

    }

    public function arrayToTextList($string, $separator = ' = ')
    {

        if(!empty($string)){
            if(is_string($string)){
                $rc = json_decode($string, True);
            }else{
                $rc = $string;
            }
            $r = '';
            foreach($rc as $k => $v){
                $r .= "$k$separator$v\r\n";
            }
            return $r;
        }
        return;

    }

    /**
     * Get each line of a string separately as a key-value list.
     *
     * @param string $string
     * @return array
     */
    public function textListToArray($string, $keyValueSeparator = ' = ')
    {

        $result = [];
        foreach ($this->stringToList($string) as $keyValue) {
            [$key, $value] = array_map('trim', explode($keyValueSeparator, $keyValue, 2));
            $result[$key] = $value;
        }
        return $result;

    }

    /**
     * Get each line of a string separately as a list.
     *
     * @param string $string
     * @return array
     */
    public function stringToList($string)
    {
        return array_filter(array_map('trim', explode("\n", $this->fixEndOfLine($string))), 'strlen');
    }

    /**
     * Clean the text area from end of lines.
     *
     * This method fixes Windows and Apple copy/paste from a textarea input.
     *
     * @param string $string
     * @return string
     */
    public function fixEndOfLine($string)
    {
        return str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], (string) $string);
    }

    public function findKeyInArray($rc, $need)
    {

        $r = [];
        if(!empty($rc[$need])){
            $r[$need] = $rc[$need];
        }
        foreach($rc as $a => $b){
            if(!empty($b[$need])){
                $r[$a] = $b[$need];
            }
            if(!empty($b) && is_array($b)){
                foreach($b as $c => $d){
                    if(!empty($d[$need])){
                        $r[$a] = $d[$need];
                    }
                }
            }
        }
        return $r;

    }

    public function isEntrieExistInArray($haystack, $needs, $key = False)
    {

        foreach($haystack as $i => $a){
            $rc = [];
            foreach($needs as $n => $v){
                if(isset($a[$n]) && $a[$n] === $v){
                    $rc[] = $n;
                }
            }
            if(count($needs) === count($rc)){
                if($key && isset($a[$key])){
                    return $a[$key];
                }else{
                    return $i;
                }
            }
        }
        return False;

    }

    public function searchInArray($haystack, $needs)
    {

        $r = [];
        foreach($haystack as $i => $v){
            if(is_array($v)){
                $rc = $this->searchInArray($v, $needs);
                if(!empty($rc)){
                    $r[] = $i;
                }
            }else{
                $rc = $this->procSearchInArray($i, $v, $needs);
                if(!empty($rc)){
                    $r[] = $rc;
                }
            }
        }
        return $r;

    }

    private function procSearchInArray($i, $v, $needs)
    {

        foreach($needs as $k => $n){
            if($k == $i && $n == $v){
                return $i;
            }
        }
        return False;

    }

    public function convert_size($size)
    {
        $unit=array('b','Kb','Mb','Gb','Tb','Pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    public function redirecToURL($url, $status = 301)
    {

        $response = $this->getMvcEvent()->getResponse();
        $response->getHeaders()->addHeaderLine('Location', $url);
        $response->setStatusCode($status);
        $response->sendHeaders();
        return $response;
        
    }

}
