<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// Blink Home Client (I/O)
class BlinkHomeClient extends IPSModuleStrict
{
    // Helper Traits
    use BlinkHelper;
    use DebugHelper;
    use EventHelper;
    use FormatHelper;

    /**
     * @var string Childs GUID
     */
    private const BLINK_CHILDS_GUID = '{7DD36C8D-6581-25FE-9FEA-98024108BED6}';

    /**
     * @var array<int,string> Blink Battery Device Types (up to now)
     */
    private const BLINK_BATTERY_DEVICES = [
        'cameras',
        'sirens',
        'doorbells',
        'doorbell_buttons',
        'accessories',
    ];

    /**
     * @var array<int,array{0:string,1:string,2:int,3:?string}> Echo map NOTIFICATIONS
     */
    private const BLINK_MAP_NOTIFICATIONS = [
        ['low_battery', 'Low battery', 5, null],
        ['camera_offline', 'Camera offline', 5, null],
        ['camera_usage', 'Camera usage', 5, null],
        ['scheduling', 'Scheduling (On/Off)', 5, null],
        ['motion', 'Motion', 5, null],
        ['sync_module_offline', 'Sync module offline', 5, null],
        ['temperature', 'High temperature', 5, null],
        ['doorbell', 'Doorbell', 5, null],
        ['wifi', 'Wifi', 5, null],
        ['lfr', 'Lost frame received', 5, null],
        ['bandwidth', 'Bandwidth', 5, null],
        ['battery_dead', 'Battery dead', 5, null],
        ['local_storage', 'Local storage', 5, null],
        ['accessory_connected', 'Accessory connected', 5, null],
        ['accessory_disconnected', 'Accessory disconnected', 5, null],
        ['accessory_low_battery', 'Accessory low battery', 5, null],
        ['general', 'System offline', 5, null],
    ];

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting IP-Symcon.
     * Therefore, status variables and module properties which the module requires permanently should be created here.
     *
     * @return void
     */
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        // The Account ID is returned in a successful login process.
        $this->RegisterAttributeString('AccountID', '');
        // The access token is provided in the response to a successful login process.
        $this->RegisterAttributeString('AccessToken', '');
        // The refresh token is provided in the response to a successful login process.
        $this->RegisterAttributeString('RefreshToken', '');
        // The unique id of the client
        $this->RegisterAttributeString('UniqueID', $this->UUIDv4());
        // Verify Client
        $this->RegisterAttributeInteger('VerifyClient', self::$BLINK_LOGOUT);
        // Region where your Blink system is registered (default: prod).
        $this->RegisterAttributeString('Region', 'prod');

        // Username (email)
        $this->RegisterPropertyString('AccountUser', '');
        // Password
        $this->RegisterPropertyString('AccountPassword', '');
        // Heartbeat
        $this->RegisterPropertyBoolean('Heartbeat', true);
        // Heartbeat Timer
        $this->RegisterTimer('TimerHeartbeat', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "heartbeat", "");');
    }

    /**
     * This function is called when deleting the instance during operation and when updating via "Module Control".
     * The function is not called when exiting IP-Symcon.
     *
     * @return void
     */
    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();
    }

    /**
     * The content can be overwritten in order to transfer a self-created configuration page.
     * This way, content can be generated dynamically.
     * In this case, the "form.json" on the file system is completely ignored.
     *
     * @return string Content of the configuration page.
     */
    public function GetConfigurationForm(): string
    {
        $verify = $this->ReadAttributeInteger('VerifyClient');
        // Debug output
        $this->LogDebug(__FUNCTION__, ' Verify: ' . $verify);
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        // LoggedIn?
        switch ($verify) {
            case self::$BLINK_VERIFY:
                $form['actions'][1]['items'][1]['enabled'] = true;  // Verify
                $form['actions'][1]['items'][2]['enabled'] = false; // Refresh
                $form['actions'][3]['items'][0]['enabled'] = false; // Options
                break;
            case self::$BLINK_LOGIN:
                $form['actions'][1]['items'][1]['enabled'] = false; // Verify
                $form['actions'][1]['items'][2]['enabled'] = true;  // Refresh
                $form['actions'][3]['items'][0]['enabled'] = true;  // Options
                break;
            case self::$BLINK_LOGOUT:
            default:
                $form['actions'][1]['items'][1]['enabled'] = false;  // Verify
                $form['actions'][1]['items'][2]['enabled'] = false;  // Refresh
                $form['actions'][3]['items'][0]['enabled'] = false;  // Options
                break;
        }
        // Debug output
        //$this->LogDebug(__FUNCTION__, $form);
        return json_encode($form);
    }

    /**
     * Is executed when "Apply" is pressed on the configuration page and immediately after the instance has been created.
     *
     * @return void
     */
    public function ApplyChanges(): void
    {
        //Never delete this line!
        parent::ApplyChanges();

        // Set Status
        $verify = $this->ReadAttributeInteger('VerifyClient');
        if ($verify === self::$BLINK_LOGIN) {
            $this->SetStatus(102);
        } elseif ($verify === self::$BLINK_VERIFY) {
            $this->SetStatus(203);
        } else {
            $this->SetStatus(104);
        }

        // Try Refresh Token after restart or enabling heartbeat...
        $heartbeat = $this->ReadPropertyBoolean('Heartbeat');
        if (!$heartbeat) {
            $this->SetTimerInterval('TimerHeartbeat', 0);
        } else {
            $this->Heartbeat();
        }
    }

    /**
     * Is called when, for example, a button is clicked in the visualization.
     *
     * @param string $ident Ident of the variable
     * @param mixed $value The value to be set
     *
     * @return void
     */
    public function RequestAction(string $ident, mixed $value): void
    {
        // Debug output
        $this->LogDebug(__FUNCTION__, $ident . ' => ' . $value);
        switch ($ident) {
            case 'login':
                $this->Login();
                break;
            case 'verify':
                $this->Verify($value);
                break;
            case 'refresh':
                $this->Refresh();
                break;
            case 'heartbeat':
                $this->Heartbeat();
                break;
            default:
                break;
        }
        //return true;
    }

    /**
     * This function is called by IP-Symcon and processes sent data and forwards it to the parent instance.
     * Data can be sent using the SendDataToParent function.
     * Further information on data forwarding can be found under Dataflow.
     *
     * @param string $json
     *
     * @return string Result of the function, which is returned to the calling child instance
     */
    public function ForwardData(string $json): string
    {
        $this->LogDebug(__FUNCTION__, $json);
        $data = json_decode($json, true);
        $result = '[]';
        $encode = false;
        // Verify
        $verify = $this->ReadAttributeInteger('VerifyClient');
        if (!$verify) {
            return $result;
        }
        // Inputs
        $account = $this->ReadAttributeString('AccountID');
        $token = $this->ReadAttributeString('AccessToken');
        $region = $this->ReadAttributeString('Region');
        // Endpoint
        if (isset($data['Endpoint'])) {
            switch ($data['Endpoint']) {
                case 'homescreen':
                    $result = $this->doHomeScreen($token, $region, $account);
                    $this->SetBatteryStates($result);
                    break;
                case 'arm':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID'])) {
                        $result = $this->doArm($token, $region, $account, $params['NetworkID']);
                    }
                    break;
                case 'config':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['DeviceID']) && isset($params['DeviceType'])) {
                        $result = $this->doConfig($token, $region, $account, $params['NetworkID'], $params['DeviceID'], $params['DeviceType']);
                    }
                    break;
                case 'disarm':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID'])) {
                        $result = $this->doDisarm($token, $region, $account, $params['NetworkID']);
                    }
                    break;
                case 'network':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID'])) {
                        $result = $this->doNetwork($token, $region, $params['NetworkID']);
                    }
                    break;
                case 'local_storage_status':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['DeviceID'])) {
                        $result = $this->doLocalStorageStatus($token, $region, $account, $params['NetworkID'], $params['DeviceID']);
                    }
                    break;
                case 'image':
                    $params = (array) $data['Params'];
                    if (isset($params['Path'])) {
                        $result = $this->doImage($token, $region, $params['Path']);
                        $encode = true;
                    }
                    break;
                case 'record':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['DeviceID']) && isset($params['DeviceType'])) {
                        $result = $this->doRecord($token, $region, $account, $params['NetworkID'], $params['DeviceID'], $params['DeviceType']);
                    }
                    break;
                case 'thumbnail':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['DeviceID']) && isset($params['DeviceType'])) {
                        $result = $this->doThumbnail($token, $region, $account, $params['NetworkID'], $params['DeviceID'], $params['DeviceType']);
                    }
                    break;
                case 'motion':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['DeviceID']) && isset($params['DeviceType']) && isset($params['Detection'])) {
                        $result = $this->doMotion($token, $region, $account, $params['NetworkID'], $params['DeviceID'], $params['DeviceType'], $params['Detection']);
                    }
                    break;
                case 'light':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['DeviceID']) && isset($params['TargetID']) && isset($params['Switch'])) {
                        $result = $this->doLight($token, $region, $account, $params['NetworkID'], $params['TargetID'], $params['DeviceID'], $params['Switch']);
                    }
                    break;
                case 'command':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['CommandID'])) {
                        $result = $this->doCommand($token, $region, $params['NetworkID'], $params['CommandID']);
                    }
                    break;
                case 'liveview':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['DeviceID']) && isset($params['DeviceType'])) {
                        $result = $this->doLive($token, $region, $account, $params['NetworkID'], $params['DeviceID'], $params['DeviceType']);
                    }
                    break;
                case 'events':
                    $params = (array) $data['Params'];
                    if (isset($params['Timestamp']) && isset($params['Page'])) {
                        $result = $this->doEvents($token, $region, $account, $params['Timestamp'], $params['Page']);
                    }
                    break;
                case 'manifest':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['DeviceID'])) {
                        $result = $this->doManifest($token, $region, $account, $params['NetworkID'], $params['DeviceID']);
                    }
                    break;
                case 'clip':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['DeviceID']) && isset($params['ManifestID']) && isset($params['ClipID'])) {
                        $result = $this->doClip($token, $region, $account, $params['NetworkID'], $params['DeviceID'], $params['ManifestID'], $params['ClipID']);
                        $encode = true;
                    }
                    break;
                case 'video':
                    $params = (array) $data['Params'];
                    if (isset($params['MediaID'])) {
                        $result = $this->doVideo($token, $region, $params['MediaID']);
                        $encode = true;
                    }
                    break;
                default:
                    $this->LogDebug(__FUNCTION__, 'Invalid Command: ' . $data['Endpoint']);
                    break;
            }
        }
        $this->LogDebug(__FUNCTION__, $result);
        // safty check
        if ($result === false) {
            $result = '[]';
        }
        // binary data
        if ($encode == true) {
            $result = bin2hex($result);
            //$result = utf8_encode($result);
        }
        // Return
        return $result;
    }

    /**
     * Login to the blink server.
     *
     * @return int 0 for failure, 1 for success and 2 for verification needed.
     */
    public function Login(): int
    {
        $verify = self::$BLINK_LOGOUT;
        $user = $this->ReadPropertyString('AccountUser');
        $password = $this->ReadPropertyString('AccountPassword');
        $uuid = $this->ReadAttributeString('UniqueID');
        //$this->LogDebug(__FUNCTION__, 'Username: ' . $user . ', Password: ' . (empty($password) ? 'N' : 'Y') . ', UUID: ' . $uuid);

        // Safty check
        if (empty($user)) {
            $this->SetStatus(201);
            echo $this->Translate('Login not possible!');
            return $verify;
        }
        if (empty($password)) {
            $this->SetStatus(202);
            echo $this->Translate('Login not possible!');
            return $verify;
        }
        // API call
        $response = $this->doLogin($user, $password, $uuid, null, null);
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->LogDebug(__FUNCTION__, $params);
            // Verification?
            $verify = isset($params['next_time_in_secs']) ? self::$BLINK_VERIFY : self::$BLINK_LOGOUT;
            if ($verify === self::$BLINK_VERIFY) {
                $this->UpdateFormField('Verify', 'enabled', true);
                $this->SetStatus(203);
                echo $this->Translate('Login must be verified!');
            } else {
                $this->UpdateFormField('Verify', 'enabled', false);
                $this->SetStatus(104);
                echo $this->Translate('Login was not successfull!');
            }
        } else {
            $this->SetStatus(104);
            echo $this->Translate('Login was not successfull!');
        }
        // Return
        $this->WriteAttributeInteger('VerifyClient', $verify);
        return $verify;
    }

    /**
     * Verify the Login/Account
     *
     * @param string $pin  Verfication Code
     *
     * @return int 0 for failure, 1 for success.
     */
    public function Verify(string $pin): int
    {
        $verify = self::$BLINK_LOGOUT;
        $username = $this->ReadPropertyString('AccountUser');
        $password = $this->ReadPropertyString('AccountPassword');
        $uuid = $this->ReadAttributeString('UniqueID');
        //$this->LogDebug(__FUNCTION__, 'Username: ' . $username . ', Password: ' . (empty($password) ? 'N' : 'Y') . ', UUID: ' . $uuid);

        // Safty check
        if (empty($username)) {
            $this->SetStatus(201);
            echo $this->Translate('Verify not possible!');
            return $verify;
        }
        if (empty($password)) {
            $this->SetStatus(202);
            echo $this->Translate('Verify not possible!');
            return $verify;
        }
        if (empty($pin)) {
            $this->SetStatus(203);
            echo $this->Translate('Verify not possible!');
            return $verify;
        }
        // API call
        $response = $this->doLogin($username, $password, $uuid, $pin, null);
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->LogDebug(__FUNCTION__, $params);
            // Token?
            $verify = isset($params['access_token']) ? self::$BLINK_LOGIN : self::$BLINK_LOGOUT;
            $this->WriteAttributeInteger('VerifyClient', $verify);
            if ($verify === self::$BLINK_LOGIN) {
                $this->LogDebug(__FUNCTION__, 'Verified!');
                // Save tokens
                $this->WriteAttributeString('AccessToken', $params['access_token']);
                $this->WriteAttributeString('RefreshToken', $params['refresh_token']);
                // Start Heartbeat
                $this->SetTimerInterval('TimerHeartbeat', $params['expires_in'] * 1000);
                // Account/Client ID
                $response = $this->doTier($params['access_token']);
                if ($response !== false) {
                    $params = json_decode($response, true);
                    $this->LogDebug(__FUNCTION__, $params);
                    $this->WriteAttributeString('AccountID', $params['account_id']);
                    $this->WriteAttributeString('Region', $params['tier']);
                }
                $this->UpdateFormField('Refresh', 'enabled', true);
                $this->UpdateFormField('Options', 'enabled', true);
                $this->SetStatus(102);
                echo $this->Translate('Verify was successfull!');
            } else {
                $this->LogDebug(__FUNCTION__, 'Not verified!');
                $this->SetStatus(203);
                echo $this->Translate('Verify was not successfull!');
            }
        } else {
            $this->SetStatus(203);
            echo $this->Translate('Verify was not successfull!');
        }
        // Return
        return $verify;
    }

    /**
     * Execute a keep alive call
     *
     * @return int 0 for success, 1 for failure.
     */
    public function Refresh(): int
    {
        // Login check
        $verify = $this->ReadAttributeInteger('VerifyClient');
        if ($verify !== self::$BLINK_LOGIN) {
            $this->LogDebug(__FUNCTION__, 'Not logged in!');
            return self::$BLINK_LOGOUT;
        }

        $user = $this->ReadPropertyString('AccountUser');
        $password = 'password'; // Do not use the password for refresh
        $token = $this->ReadAttributeString('RefreshToken');
        $uuid = $this->ReadAttributeString('UniqueID');
        $region = $this->ReadAttributeString('Region');

        // Safty check
        if (empty($user) || empty($token)) {
            $this->SetStatus(201);
            echo $this->Translate('Refresh not possible!');
            return self::$BLINK_LOGOUT;
        }
        // API call
        $response = $this->doLogin($user, $password, $uuid, null, $token);
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->LogDebug(__FUNCTION__, $params);
            // Verification?
            $verify = isset($params['access_token']) ? self::$BLINK_LOGIN : self::$BLINK_LOGOUT;
            if ($verify === self::$BLINK_LOGIN) {
                // Save tokens
                $this->WriteAttributeString('AccessToken', $params['access_token']);
                $this->WriteAttributeString('RefreshToken', $params['refresh_token']);
                // Start Heartbeat
                $this->SetTimerInterval('TimerHeartbeat', $params['expires_in'] * 1000);
                // Distribute for liveview
                $this->SendDataToChildren(json_encode([
                    'DataID'    => self::BLINK_CHILDS_GUID,
                    'AuthData'  => [
                        'account'   => $user,
                        'region'    => $region,
                        'token'     => $params['access_token']
                    ],
                    'Battery'   => unserialize($this->GetBuffer('battery'))
                ]));

                $this->SetStatus(102);
                echo $this->Translate('Refresh was successfull!');
            } else {
                $this->SetStatus(104);
                echo $this->Translate('Refresh was not successfull!');
            }
        } else {
            $this->SetStatus(104);
            echo $this->Translate('Refresh was not successfull!');
        }
        // Return
        $this->WriteAttributeInteger('VerifyClient', $verify);
        return $verify;
    }

    /**
     * Display Account Notifications
     *
     * BHS_Notification();
     *
     * @return bool true for success, false for failure.
     */
    public function Notification(): bool
    {
        $ret = false;
        // Debug
        $this->LogDebug(__FUNCTION__, 'Obtain device information.');
        // Safty check
        $verify = $this->ReadAttributeInteger('VerifyClient');
        if (!$verify) {
            echo $this->Translate('Logged out!');
            return $ret;
        }
        // Inputs
        $account = $this->ReadAttributeString('AccountID');
        $token = $this->ReadAttributeString('AccessToken');
        $region = $this->ReadAttributeString('Region');
        // API call
        $response = $this->doNotification($token, $region, $account);
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->LogDebug(__FUNCTION__, $params);
            // Prepeare Info
            echo $this->PrettyPrint(self::BLINK_MAP_NOTIFICATIONS, json_encode($params['notifications']));
            $ret = true;
        } else {
            echo $this->Translate('Call was not successfull!');
        }
        // Return
        return $ret;
    }

    /**
     * Execute a keep alive call
     *
     * @return void
     */
    private function Heartbeat()
    {
        $heartbeat = $this->ReadPropertyBoolean('Heartbeat');
        // Safty check
        if (!$heartbeat) {
            return;
        }
        // Buffer echo
        ob_start();
        $return = $this->Refresh();
        $buf = ob_get_clean();
        if ($return != self::$BLINK_LOGIN) {
            $this->LogMessage($buf, KL_ERROR);
        }
    }

    /**
     * Extracts the battery info form the devices an store it in the buffer
     *
     * @param string|false $data Response from homescreen request
     *
     * @return void
     */
    private function SetBatteryStates(string|false $data): void
    {
        if ($data === false) return;
        // find device
        $devises = json_decode($data, true);
        $states = [];
        foreach (self::BLINK_BATTERY_DEVICES as $type) {
            if (isset($devises[$type])) {
                foreach ($devises[$type] as $dev) {
                    if ($type !== 'accessories') {
                        // battery or usb
                        if (isset($dev['battery'])) {
                            // if battery than signals
                            if (isset($dev['signals'])) {
                                if (isset($dev['signals']['battery'])) {
                                    $states[] = ['device' => $dev['id'], 'battery' => $dev['signals']['battery']];
                                }
                            } else {
                                $states[] = ['device' => $dev['id'], 'battery' => $dev['battery']];
                            }

                        }
                    } else {
                        foreach ($dev as $acc) {
                            if (isset($acc['battery'])) {
                                $states[] = ['device' => $acc['id'], 'battery' => $acc['battery']];
                            }
                        }
                    }
                }
            }
        }
        $this->SetBuffer('battery', serialize($states));
    }

    /**
     * Generates a valid v4 UUID in the form of 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx' (where y is 8, 9, A, or B)
     *
     * @return string Returns a valid UUID identifier.
     */
    private function UUIDv4(): string
    {
        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}