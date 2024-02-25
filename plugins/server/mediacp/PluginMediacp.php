<?php

require_once 'modules/admin/models/ServerPlugin.php';

class PluginMediacp extends ServerPlugin
{

    public $features = [
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => true
    ];

    var $host;
    var $port;
    var $apikey;
    var $useSSL;
    var $userPackage;

    function setup($args)
    {

        if ( isset($args['server']['variables']['ServerHostName'])
            && isset($args['server']['variables']['plugin_mediacp_API_Key'])
            && isset($args['server']['variables']['plugin_mediacp_Port'])
            && isset($args['server']['variables']['plugin_mediacp_Use_SSL'])) {
            $this->host = $args['server']['variables']['ServerHostName'];
            $this->apikey = $args['server']['variables']['plugin_mediacp_API_Key'];
            $this->port = $args['server']['variables']['plugin_mediacp_Port'];
            $this->useSSL = $args['server']['variables']['plugin_mediacp_Use_SSL'];
        } else {
            throw new CE_Exception('Missing Server Credentials: please fill out all information when editing the server.');
        }

    }

    public function getVariables()
    {
        $variables = [
            'Name' => [
                'type' => 'hidden',
                'description' => 'Used by CE to show plugin',
                'value' => 'Media Control Panel'
            ],
            'Description' => [
                'type' => 'hidden',
                'description' => 'Description viewable by admin in server settings',
                'value' => 'Media Control Panel (www.mediacp.net) integration'
            ],
            'Port' => [
                'type' => 'text',
                'description' => '',
                'value' => '2020',
            ],
            'Use SSL' => [
                'type' => 'yesno',
                'description' => 'Set to YES if SSL should be used to connect to the API service',
                'value' => '1',
            ],
            'API Key' => [
                'type' => 'password',
                'description' => '',
                'value' => '',
                'encryptable' => true
            ],
            'Service Name Custom Field' => [
                'type' => 'text',
                'description' => 'Enter the name of the package custom field that will hold the service name',
                'value'=>'Service Name'
            ],
            'Service Password Custom Field' => [
                'type' => 'text',
                'description' => 'Enter the name of the package custom field that will hold the service password',
                'value'=>'Service Password'
            ],
            'Service Portbase Custom Field' => [
                'type' => 'text',
                'description' => 'Enter the name of the package custom field that will hold the service portbase',
                'value'=>'Service Portbase'
            ],
            'Actions' => [
                'type' => 'hidden',
                'description' => 'Current actions that are active for this plugin per server',
                'value'=>'Create,Delete,Suspend,UnSuspend,Restart,Stop'
            ],
            'Registered Actions For Customer' => [
                'type' => 'hidden',
                'description' => 'Current actions that are active for this plugin per server for customers',
                'value' => 'authenticateClient'
            ],
            'package_addons' => [
                'type' => 'hidden',
                'description' => 'Supported signup addons variables',
                'value' => ['AUTODJ', 'BANDWIDTH', 'DISK', 'BITRATE', 'CONNECTIONS']
            ],
            'package_vars' => [
                'type' => 'hidden',
                'description' => 'Whether package settings are set',
                'value' => '1',
            ],
            'package_vars_values' => [
                'type'  => 'hidden',
                'description' => lang('Package Settings'),
                'value' => [
                    'Media Service' =>  [
                        'type' => 'dropdown',
                        'multiple' => false,
                        'getValues' => 'getMediaServiceValues',
                        'label' => 'Media Service Type',
                        'description' => '',
                        'value' => '',
                    ],
                    'AutoDJ Type' => [
                        'type' => 'dropdown',
                        'multiple' => false,
                        'getValues' => 'getAutoDJValues',
                        'label' => 'Audio Service - AutoDJ Type (Audio Services Only)',
                        'description' => 'AutoDJ Type (Audio Services Only)',
                        'value' => 'Liquidsoap',
                    ],
                    'Video Service Type' => [
                        'type' => 'dropdown',
                        'multiple' => false,
                        'getValues' => 'getVideoServiceTypesValues',
                        'label' => 'Video Service - Type',
                        'description' => '',
                        'value' => '',
                    ],

                    'Connections' => [
                        'type' => 'text',
                        'label' => 'Listeners / Viewers',
                        'description' => '',
                        'value' => '9999',
                    ],
                    'Bitrate' => [
                        'type' => 'dropdown',
                        'multiple' => false,
                        'getValues' => 'getBitrateSelectionValues',
                        'label' => 'Bitrate (Kbps)',
                        'description' => '',
                        'value' => '128',
                    ],
                    'Bandwidth' => [
                        'type' => 'text',
                        'label' => 'Bandwidth (MB)',
                        'description' => '',
                        'value' => 'Unlimited',
                    ],
                    'Quota' => [
                        'type' => 'text',
                        'label' => 'Disk Space (MB)',
                        'description' => '',
                        'value' => '1024',
                    ],
                    'Stream Targets' => [
                        'type' => 'text',
                        'label' => 'Stream Targets (Video Services)',
                        'description' => '',
                        'value' => 'Unlimited',
                    ],
                ]
            ]
        ];

        return $variables;
    }

    public function validateCredentials($args)
    {
    }

    public function doDelete($args)
    {
        $this->userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($this->userPackage);
        $this->delete($args);
        return 'Package has been deleted.';
    }

    public function doCreate($args)
    {
        $this->userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($this->userPackage);
        $this->create($args);
        return 'Package has been created.';
    }

    public function doUpdate($args)
    {
        $this->userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($this->userPackage);
        $this->update($args);
        return 'Package has been updated.';
    }

    public function doSuspend($args)
    {
        $this->userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($this->userPackage);
        $this->suspend($args);
        return 'Package has been suspended.';
    }

    public function suspend($args)
    {
        // Call suspend at the server
        $this->setup($args);
        # Suspend this service
        $this->call("/api/{$this->getCustomProperty("ServiceID")}/media-service/suspend", CURLOPT_POST, []);
    }

    public function doUnSuspend($args)
    {
        $this->userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($this->userPackage);
        $this->unsuspend($args);
        return 'Package has been unsuspended.';
    }

    public function unsuspend($args)
    {
        // Call Unsuspend at the server
        $this->setup($args);
        # Unsuspend this service
        $this->call("/api/{$this->getCustomProperty("ServiceID")}/media-service/unsuspend", NULL, []);
    }

    public function doRestart($args)
    {
        $this->userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($this->userPackage);
        $this->restart($args);
        return 'Package has been restarted.';
    }

    public function restart($args)
    {
        // Call restart at the server
        $this->setup($args);
        # Restart this service
        $this->call("/api/{$this->getCustomProperty("ServiceID")}/media-service/restartService", CURLOPT_POST, []);
    }
    public function doStop($args)
    {
        $this->userPackage = new UserPackage($args['userPackageId']);
        $args = $this->buildParams($this->userPackage);
        $this->stop($args);
        return 'Package has been restarted.';
    }

    public function stop($args)
    {
        // Call restart at the server
        $this->setup($args);
        # Restart this service
        $this->call("/api/{$this->getCustomProperty("ServiceID")}/media-service/stopService", CURLOPT_POST, []);
    }

    public function delete($args)
    {
        $this->setup($args);
        # Delete this service
        $this->call("/api/{$this->getCustomProperty("ServiceID")}/media-service/delete", NULL, [], false);

        # Delete user account if they have no other services
        $servers = $this->call("/api/0/media-service/list", NULL, ['user_id'=>$this->getCustomProperty("CustomerID")]);
        if ( !isset($servers->message) && count($servers) === 0 ){
            $this->call("/api/0/user/delete/{$this->getCustomProperty("CustomerID")}");
        }

        $this->resetCustomProperties();
        $this->resetCustomFields($args);
    }

    public function update($args)
    {
        foreach ($args['changes'] as $key => $value) {
            switch ($key) {
                case 'username':
                    // update username on server
                    break;
                case 'password':
                    // update password on server
                    break;
                case 'domain':
                    // update domain on server
                    break;
                case 'ip':
                    // update ip on server
                    break;
                case 'package':
                    // update package on server
                    break;
            }
        }
    }

    public function getAvailableActions($userPackage)
    {
        $this->userPackage = $userPackage;
        $args = $this->buildParams($this->userPackage);

        $ServiceCreated = $this->getCustomProperty('ServiceID') > 0;

        $actions = [];
        // Get Status at Server

        // If not created yet
        if ( !$ServiceCreated ) $actions[] = 'Create';

        // If we can delete
        if ( $ServiceCreated ) $actions[] = 'Delete';

        // If we can suspend
        if ( $ServiceCreated ) $actions[] = 'Suspend';

        // If suspended at Server
        if ( $ServiceCreated ) $actions[] = 'UnSuspend';

        if ( $ServiceCreated ) $actions[] = 'Restart';

        if ( $ServiceCreated ) $actions[] = 'Stop';
        return $actions;
    }

    public function create($args)
    {
        $this->setup($args);
        $this->userPackage = new UserPackage($args['package']['id'], [], $this->user);

        # Create Customer Account or catch already exists gracefully
        $userPassword = $this->mediacp_generateStrongPassword(12,false,'lud');
        $response = $this->call("/api/0/user/store", CURLOPT_POST, [
            'name' => $args['customer']['first_name'] . ' ' . $args['customer']['last_name'],
            'username' => $args['customer']['email'],
            'user_email' => $args['customer']['email'],
            'password' => $userPassword,
        ]);
        if ( isset($response->errors->username) || $response->errors->username == 'Username must be unique' ){
            $user = $this->call("/api/0/user/show", NULL, ['username'=>$args['customer']['email']]);
            $userPassword = '[EXISTING PASSWORD]';
        }else{
            $user = $response->user;
        }

        if ( !$user || empty($user->id) ) throw new CE_Exception("User was not created successfully.");

        $this->setCustomProperty( 'CustomerID', $user->id);
        $this->userPackage->setCustomField("Password", $userPassword);
        $this->userPackage->setCustomField("Customer Password", $userPassword, CUSTOM_FIELDS_FOR_PACKAGE); # Optionally available for email templates

        # Create Service
        $response = $this->call("/api/0/media-service/store", CURLOPT_POST, $this->buildServiceParameters($args, $user));
        if ( $response->status != 1 ) throw new CE_Exception("Unable to create service.\n\n{$response->error}\n\nDebugging: " . print_r($response,true));

        $this->userPackage->setCustomField("User Name", $args['customer']['email']);
        $this->setCustomProperty( 'ServiceID', $response->service_id);
        $this->setCustomProperty( 'PortBase', $response->return->portbase);

        $response = $this->call("/api/{$response->service_id}/media-service/show");
        $this->userPackage->setCustomField($args['server']['variables']['plugin_mediacp_Service_Name_Custom_Field'], $response->unique_id, CUSTOM_FIELDS_FOR_PACKAGE); # Optionally available for email templates
        $this->userPackage->setCustomField($args['server']['variables']['plugin_mediacp_Service_Portbase_Custom_Field'], $response->portbase, CUSTOM_FIELDS_FOR_PACKAGE); # Optionally available for email templates
        $this->userPackage->setCustomField($args['server']['variables']['plugin_mediacp_Service_Password_Custom_Field'], $response->password, CUSTOM_FIELDS_FOR_PACKAGE); # Optionally available for email templates

        CE_Lib::log(4, "Account vars: " . json_encode($this->buildParams($this->userPackage),true));
    }

    public function buildServiceParameters($args, $user)
    {
        $unique_id = $this->userPackage->getCustomField($args['server']['variables']['plugin_mediacp_Service_Name_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);
        $portbase = (int) $this->userPackage->getCustomField($args['server']['variables']['plugin_mediacp_Service_Portbase_Custom_Field'], CUSTOM_FIELDS_FOR_PACKAGE);

        $server = new stdClass();
        $server->userid = $user->id;
        if ( !empty($unique_id) ) $server->unique_id = $unique_id;
        if ( !empty($portbase) && $portbase > 1024 && $portbase < 65000 ) $server->portbase = $portbase;
        $server->plugin = $args['package']['variables']['Media_Service'];
        $server->maxuser = $args['package']['variables']['Connections'];
        $server->bitrate = $args['package']['variables']['Bitrate'] >= 24 ? $args['package']['variables']['Bitrate'] : 128;
        $server->bandwidth = strtolower($args['package']['variables']['Bandwidth']) == 'unlimited' ? 0 : $args['package']['variables']['Bandwidth'];
        $server->quota = strtolower($args['package']['variables']['Quota']) == 'unlimited' ? 0 : $args['package']['variables']['Quota'];
        $server->stream_targets_limit = strtolower($args['package']['variables']['Stream_Targets']) == 'unlimited' ? -1 : $args['package']['variables']['Stream_Targets'];

        switch($server->plugin){
            case 'shoutcast198':
            case 'shoutcast2':
                $server->password = $this->mediacp_generateStrongPassword();
                $server->adminpassword = $this->mediacp_generateStrongPassword();
                break;

            case 'icecast':
            case 'icecast_kh':
                $server->source_password = $this->mediacp_generateStrongPassword();
                $server->password = $this->mediacp_generateStrongPassword();
                break;
        }


        # Source Plugin
        if (in_array($server->plugin, ['shoutcast198','shoutcast2','icecast','icecast_kh']) && !empty($args['package']['variables']['AutoDJ_Type'])) {
            $server->sourceplugin = $args['package']['variables']['AutoDJ_Type'];
        }

        return (array) $server;
    }

    public function testConnection($args)
    {
        CE_Lib::log(4, 'Testing connection to server');
        $this->setup($args);

        if (!$this->call("/api/0/media-service/list", null, [
            'user_id' => 0
        ])) {
            throw new CE_Exception("Connection to server failed.");
        }

    }

    public function getMediaServiceValues()
    {
        $values = [
            'shoutcast198' => 'Audio - Shoutcast 198',
            'shoutcast2' => 'Audio - Shoutcast 2',
            'icecast' => 'Audio - Icecast 2',
            'icecast_kh' => 'Audio - Icecast 2 KH',
            'AudioTranscoder' => 'Audio - Transcoder',
            'WowzaMedia' => 'Video - Wowza Streaming Engine',
            'Flussonic' => 'Video - Flussonic',
            'NginxRtmp' => 'Video - Nginx-Rtmp',
        ];

        return $values;
    }
    public function getAutoDJValues()
    {
        $values = [
            '' => 'No AutoDJ Service',
            'liquidsoap' => 'Liquidsoap',
            'ices04' => 'Ices 0.4',
            'ices20' => 'Ices 2.0',
            'sctransv1' => 'Shoutcast Transcoder V1',
            'sctransv2' => 'Shoutcast Transcoder V2',
        ];

        return $values;
    }

    public function getVideoServiceTypesValues()
    {
        $values = [
            '0' => 'Live Streaming',
            '1' => 'TV Station',
            '2' => 'Ondemand Streaming',
            '3' => 'Stream Relay',
        ];

        return $values;
    }
    public function getBitrateSelectionValues()
    {
        return explode(",", "24,32,40,48,56,64,80,96,112,128,160,192,224,256,320,400,480,560,640,720,800,920,1024,1280,1536,1792,2048,2560,3072,3584,4096,4068,5120,5632,6144,6656,7168,7680,8192,9216,10240,11264,12228,13312,14336,99999");
    }

    public function getDirectLink($userPackage, $getRealLink = true, $fromAdmin = false, $isReseller = false)
    {
        $linkText = $this->user->lang('Login to Server');
        $args = $this->buildParams($userPackage);

        $this->setup($args);
        $url = ($this->useSSL == 1 ? 'https' : 'http') . '://' . $this->host .':'. $this->port;
        if ($getRealLink) {
            // call login at server

            return [
                'link'    => '<li><a target="_blank" href="'.$url.'">' .$linkText . '</a></li>',
                'rawlink' =>  $url,
                'form'    => ''
            ];
        } else {
            return [
                'link' => '<li><a target="_blank" href="index.php?fuse=clients&controller=products&action=openpackagedirectlink&packageId='.$userPackage->getId().'&sessionHash='.CE_Lib::getSessionHash().'">' .$linkText . '</a></li>',
                'form' => ''
            ];
        }
    }

    public function dopanellogin($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $response = $this->getDirectLink($userPackage);
        return $response['rawlink'];
    }

    public function dopanellogin_reseller($args)
    {
        $userPackage = new UserPackage($args['userPackageId']);
        $response = $this->getDirectLink($userPackage, true, false, true);
        return $response['rawlink'];
    }

    /**
     * @param string $path
     * @param null|int $method NULL|CURLOPT_POST|CURLOPT_DELETE|CURLOPT_PUT
     * @param array $params
     * @param $args
     * @return array
     */
    function call(string $path, null|int $method = NULL, array $params = [], bool $catchHttpCodeErrors = true)
    {
        if (!function_exists('curl_init')) {
            CE_Lib::log(4, "plugin_mediacp::error: cURL is required in order to connect to Media Control Panel");
            throw new CE_Exception('cURL is required in order to connect to Media Control Panel');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            "Authorization: Bearer {$this->apikey}"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if ( $method ) {
            curl_setopt($ch, $method, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);


        $url = ($this->useSSL == 1 ? 'https' : 'http') . '://' . $this->host .':'. $this->port . $path;

        if ( !$method ) $url .= '?'.http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $url);


        CE_Lib::log(4, 'MediaCP Request - ' . $url . ' - ' . json_encode($params));
        $data = curl_exec($ch);
        CE_Lib::log(4, 'MediaCP Response - '.curl_getinfo($ch, CURLINFO_HTTP_CODE).' - ' . $data);

        if ( curl_getinfo($ch, CURLINFO_HTTP_CODE) == 401 ){
            $error = "Unauthorized.<br /><br />The provided API key for the server is not valid.<br /><br />Refer to <a href='https://www.mediacp.net/doc/admin-server-manual/billing/clientexec-integration-guide/'>module documentation</a> for more information.";
            CE_Lib::log(4, $error);
            throw new CE_Exception($error);
        }

        $response = json_decode($data);

        if ($data === false || (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200 && $catchHttpCodeErrors)) {
            if ( $response->errors ){
                $error = "MediaCP API Error:\nCall: {$path}\n";
                foreach($response->errors as $message)
                    $error .= "{$message}\n";
            }else{
                $error = "MediaCP API Request / cURL Error: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . ' - '.curl_error($ch) . '<br /><br />' . print_r($response ?: $data, true) . "<br /><br />Refer to <a href='https://www.mediacp.net/doc/admin-server-manual/billing/clientexec-integration-guide/'>module documentation</a> for more information.";
            }

            CE_Lib::log(4, $error);
            throw new CE_Exception($error);
        }
        curl_close($ch);

       # throw new CE_Exception(var_export($response,true));
        return $response;
    }

    function mediacp_generateStrongPassword($length = 12, $add_dashes = false, $available_sets = 'luds')
    {
        $sets = array();
        if(strpos($available_sets, 'l') !== false)
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        if(strpos($available_sets, 'u') !== false)
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        if(strpos($available_sets, 'd') !== false)
            $sets[] = '23456789';
        if(strpos($available_sets, 's') !== false)
            $sets[] = '!@$%*?';

        $all = '';
        $password = '';
        foreach($sets as $set)
        {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);
        for($i = 0; $i < $length - count($sets); $i++)
            $password .= $all[array_rand($all)];

        $password = str_shuffle($password);

        if(!$add_dashes)
            return $password;

        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while(strlen($password) > $dash_len)
        {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;
        return $dash_str;
    }

    public function setCustomProperty($field, $value)
    {
        $props = !empty($this->userPackage->getCustomField("Server Acct Properties")) && json_decode($this->userPackage->getCustomField("Server Acct Properties")) ? json_decode($this->userPackage->getCustomField("Server Acct Properties")) : new stdClass;

        $props->{$field} = $value;
        $this->userPackage->setCustomField("Server Acct Properties", json_encode($props));
    }

    public function getCustomProperty($field)
    {
        $props = !empty($this->userPackage->getCustomField("Server Acct Properties")) && json_decode($this->userPackage->getCustomField("Server Acct Properties")) ? json_decode($this->userPackage->getCustomField("Server Acct Properties")) : new stdClass;
        return $props->{$field} ?? null;
    }

    protected function getCustomProperties()
    {
        return !empty($this->userPackage->getCustomField("Server Acct Properties")) && json_decode($this->userPackage->getCustomField("Server Acct Properties")) ? json_decode($this->userPackage->getCustomField("Server Acct Properties")) : new stdClass;
    }
    protected function resetCustomProperties(){
        $this->userPackage->setCustomField("Server Acct Properties", json_encode([]));
    }
    protected function resetCustomFields($args){
        $this->userPackage->setCustomField($args['server']['variables']['plugin_mediacp_Service_Password_Custom_Field'], "", CUSTOM_FIELDS_FOR_PACKAGE); # Optionally available for email templates
        $this->userPackage->setCustomField($args['server']['variables']['plugin_mediacp_Service_Portbase_Custom_Field'], "", CUSTOM_FIELDS_FOR_PACKAGE); # Optionally available for email templates
    }

}
