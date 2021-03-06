<?php
/**
 * ©[2019] SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
 */

namespace Sugarcrm\REST\Client;

use MRussell\REST\Client\AbstractClient;
use Sugarcrm\REST\Helpers\Helper;
use Sugarcrm\REST\Auth\SugarOAuthController;
use Sugarcrm\REST\Endpoint\Provider\SugarEndpointProvider;
use Sugarcrm\REST\Storage\SugarStaticStorage;

/**
 * The default Sugar 7 REST v10 API implementation
 * @package Sugarcrm\REST\Client\Abstracts\AbstractClient
 * @method \Sugarcrm\REST\Endpoint\Ping             ping()
 * @method \Sugarcrm\REST\Endpoint\Module           module(string $module = '',string $record_id = '')
 * @method \Sugarcrm\REST\Endpoint\ModuleFilter     list(string $module = '')
 * @method \Sugarcrm\REST\Endpoint\Search           search()
 * @method \Sugarcrm\REST\Endpoint\Metadata         metadata(string $module = '')
 * @method \Sugarcrm\REST\Endpoint\Me               me()
 * @method \Sugarcrm\REST\Endpoint\Enum             enum(string $module = '',string $field = '')
 * @method \Sugarcrm\REST\Endpoint\Bulk             bulk()
 */
class Sugar7API extends AbstractClient
{
    /**
     * The API Version to be used.
     * Defaults to 10 (for v10), but can be any number above 10,
     * since customizing API allows for additional versioning to allow for duplicate endpoints
     * @var string
     */
    protected $version = "10";

    /**
     * @var SugarOAuthController
     */
    protected $Auth;

    public function __construct($server = '', array $credentials = array())
    {
        $this->init();
        if ($server !== '' || !empty($server)){
            $this->setServer($server);
        }
        if (!empty($credentials)){
            $this->getAuth()->updateCredentials($credentials);
        }
    }

    /**
     * Setup the default Auth Controller and EndpointProvider
     */
    protected function init(){
        $this->setAuth(new SugarOAuthController());
        $this->setEndpointProvider(new SugarEndpointProvider());
        $Auth = $this->getAuth();
        $Auth->setActionEndpoint('authenticate',$this->EndpointProvider->getEndpoint('oauth2Token'));
        $Auth->setActionEndpoint('refresh',$this->EndpointProvider->getEndpoint('oauth2Refresh'));
        $Auth->setActionEndpoint('logout',$this->EndpointProvider->getEndpoint('oauth2Logout'));
        $Auth->setActionEndpoint('sudo',$this->EndpointProvider->getEndpoint('oauth2Sudo'));
        $Auth->setStorageController(new SugarStaticStorage());
    }

    /**
     * @inheritdoc
     */
    protected function setAPIUrl()
    {
        $this->apiURL = Helper::configureAPIURL($this->server, $this->version);
        $Auth = $this->getAuth();
        foreach($Auth->getActions() as $action){
            $EP = $Auth->getActionEndpoint($action);
            $EP->setBaseUrl($this->apiURL);
        }
    }

    /**
     * Helper Method to Login to Sugar Instance
     * @param null $username
     * @param null $password
     * @return bool
     */
    public function login($username = NULL,$password = NULL){
        $creds = array();
        if ($username !== NULL){
            $creds['username'] = $username;
        }
        if ($password !== NULL){
            $creds['password'] = $password;
        }
        if (!empty($creds)){
            $this->getAuth()->updateCredentials($creds);
        }
        return $this->getAuth()->authenticate();
    }

    /**
     * Helper Method to Refresh Authentication Token
     * @return bool
     */
    public function refreshToken(){
        $creds = $this->getAuth()->getCredentials();
        if (isset($creds['client_id']) &&
            isset($creds['client_secret'])){
            return $this->getAuth()->refresh();
        }
        return FALSE;
    }

    /**
     * Helper method to Logout of API
     * @return bool
     */
    public function logout(){
        return $this->getAuth()->logout();
    }

    /**
     * Helper method to Sudo to new user
     * @param $user string
     * @return bool
     */
    public function sudo($user)
    {
        return $this->getAuth()->sudo($user);
    }

    /**
     * Check if authenticated, and attempt Refresh/Login if not
     * @return bool
     * @codeCoverageIgnore
     */
    public function isAuthenticated()
    {
        $Auth = $this->getAuth();
        if ($Auth){
            if (!$Auth->isAuthenticated()){
                if (!$this->refreshToken()){
                    return $this->login();
                }
            }
        } else {
            return FALSE;
        }
        return TRUE;
    }

}
