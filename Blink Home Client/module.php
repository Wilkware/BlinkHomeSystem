<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// Blink Home Client (I/O)
class BlinkHomeClient extends IPSModule
{
    // Helper Traits
    use BlinkHelper;
    use DebugHelper;

    /**
     * Overrides the internal IPSModule::Create($id) function
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // The Account ID is returned in a successful login response.
        $this->RegisterAttributeString('AccountID', '');
        // The Client ID is returned in a successful login response.
        $this->RegisterAttributeString('ClientID', '');
        // The auth token is provided in the response to a successful login.
        $this->RegisterAttributeString('AuthToken', '');
        // The unique id of the client
        $this->RegisterAttributeString('UniqueID', $this->UUIDv4());
        // Verify Client
        $this->RegisterAttributeBoolean('Verify', false);
        // Region where your Blink system is registered (default: prod).
        $this->RegisterAttributeString('Region', 'prod');

        // Username (email)
        $this->RegisterPropertyString('AccountUser', '');
        // Password
        $this->RegisterPropertyString('AccountPassword', '');
        // Heartbeat
        $this->RegisterPropertyInteger('HeartbeatInterval', 24);
        // Heartbeat Timer
        $this->RegisterTimer('TimerHeartbeat', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "heartbeat", "");');
    }

    /**
     * Overrides the internal IPSModule::Destroy($id) function
     */
    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    /**
     * Configuration Form.
     *
     * @return JSON configuration string.
     */
    public function GetConfigurationForm()
    {
        $token = $this->ReadAttributeString('AuthToken');
        $verify = $this->ReadAttributeBoolean('Verify');
        // Debug output
        $this->SendDebug(__FUNCTION__, 'AuthToken: ' . $token . ' Verify: ' . ($verify ? 'Y' : 'N'));
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        // LoggedIn?
        if (!empty($token)) {
            if ($verify) {
                $this->SetStatus(102);
            } else {
                $this->SetStatus(203);
            }
        } else {
            $this->SetStatus(104);
        }
        // Device Name (alias)
        // Debug output
        //$this->SendDebug(__FUNCTION__, $form);
        return json_encode($form);
    }

    /**
     * Overrides the internal IPSModule::ApplyChanges($id) function
     */
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $heartbeat = $this->ReadPropertyInteger('HeartbeatInterval');
        // Timer ?
        $this->SetTimerInterval('TimerHeartbeat', 60 * 1000 * $heartbeat);
    }

    /**
     * RequestAction.
     *
     *  @param string $ident Ident.
     *  @param string $value Value.
     */
    public function RequestAction($ident, $value)
    {
        // Debug output
        $this->SendDebug(__FUNCTION__, $ident . ' => ' . $value);
        switch ($ident) {
            case 'heartbeat':
                $this->Heartbeat();
                break;
            default:
                break;
        }
        //return true;
    }

    /**
     * Overrides the internal IPSModule::ForwardData($JSONStringString) function
     */
    public function ForwardData($json)
    {
        $this->SendDebug(__FUNCTION__, $json);
        $data = json_decode($json, true);
        $result = '[]';
        $encode = false;
        // Verify
        $verify = $this->ReadAttributeBoolean('Verify');
        if (!$verify) {
            return $result;
        }
        // Inputs
        $account = $this->ReadAttributeString('AccountID');
        $client = $this->ReadAttributeString('ClientID');
        $token = $this->ReadAttributeString('AuthToken');
        $region = $this->ReadAttributeString('Region');
        // Endpoint
        if (isset($data['Endpoint'])) {
            switch ($data['Endpoint']) {
                case 'homescreen':
                    $result = $this->doHomeScreen($token, $region, $account);
                    break;
                case 'arm':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID'])) {
                        $result = $this->doArm($token, $region, $account, $params['NetworkID']);
                    }
                    break;
                case 'disarm':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID'])) {
                        $result = $this->doDisarm($token, $region, $account, $params['NetworkID']);
                    }
                    break;
                case 'image':
                    $params = (array) $data['Params'];
                    if (isset($params['Path'])) {
                        $result = $this->doImage($token, $region, $params['Path']);
                        $encode = true;
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
                    if (isset($params['NetworkID']) && isset($params['DeviceID']) && isset($params['Detection'])) {
                        $result = $this->doMotion($token, $region, $params['NetworkID'], $params['DeviceID'], $params['Detection']);
                    }
                    break;
                case 'command':
                    $params = (array) $data['Params'];
                    if (isset($params['NetworkID']) && isset($params['CommandID'])) {
                        $result = $this->doCommand($token, $region, $params['NetworkID'], $params['CommandID']);
                    }
                    break;
                default:
                    $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $data['Endpoint']);
                    break;
            }
        }
        $this->SendDebug(__FUNCTION__, $result);
        // safty check
        if ($result === false) {
            $result = '[]';
        }
        // binary data
        if ($encode == true) {
            $result = utf8_encode($result);
        }
        // Return
        return $result;
    }

    /**
     * Logout from the blink server.
     *
     * @return True if successful, otherwise false.
     */
    public function Login()
    {
        $ret = self::$BLINK_FAILURE;
        $user = $this->ReadPropertyString('AccountUser');
        $password = $this->ReadPropertyString('AccountPassword');
        $uuid = $this->ReadAttributeString('UniqueID');
        // Safty check
        if (empty($user)) {
            $this->SetStatus(201);
            echo $this->Translate('Login not possible!');
            return $ret;
        }
        if (empty($password)) {
            $this->SetStatus(202);
            echo $this->Translate('Login not possible!');
            return $ret;
        }
        // API call
        $response = $this->doLogin($user, $password, $uuid);
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->SendDebug(__FUNCTION__, $params);
            if (isset($params['account']['account_id'], $params['account']['client_id'], $params['auth']['token'], $params['account']['tier'])) {
                // AccountID
                $this->WriteAttributeString('AccountID', $params['account']['account_id']);
                // ClientID
                $this->WriteAttributeString('ClientID', $params['account']['client_id']);
                // AuthToken
                $this->WriteAttributeString('AuthToken', $params['auth']['token']);
                // AccountTier
                $this->WriteAttributeString('Region', $params['account']['tier']);
            } else {
                $this->SetStatus(104);
                if (isset($params['message'])) {
                    echo $params['message'];
                } else {
                    echo $this->Translate('Login not possible!');
                }
                return $ret;
            }
            // Verification?
            $verify = !($params['account']['client_verification_required']);
            $this->WriteAttributeBoolean('Verify', $verify);
            if ($verify) {
                $this->SetStatus(102);
                echo $this->Translate('Login was successfull!');
                $ret = self::$BLINK_SUCCESS;
            } else {
                $this->SetStatus(203);
                echo $this->Translate('Login must be verified!');
                $ret = self::$BLINK_WEAKNESS;
            }
        } else {
            echo $this->Translate('Login was not successfull!');
        }
        // Return
        return $ret;
    }

    /**
     * Logout from the blink server.
     */
    public function Verify(string $pin)
    {
        $ret = self::$BLINK_FAILURE;
        $account = $this->ReadAttributeString('AccountID');
        $client = $this->ReadAttributeString('ClientID');
        $token = $this->ReadAttributeString('AuthToken');
        $region = $this->ReadAttributeString('Region');
        // Safty check
        if (empty($token) || empty($account) || empty($client)) {
            $this->SendDebug(__FUNCTION__, 'Token: ' . $token . ', AccountID: ' . $account . ', ClientID: ' . $client);
            echo $this->Translate('Verify not possible!');
            return $ret;
        }
        // API call
        $response = $this->doVerify($token, $pin, $region, $account, $client);
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->SendDebug(__FUNCTION__, $params);
            // Verification?
            $verify = !($params['require_new_pin']);
            $this->WriteAttributeBoolean('Verify', $verify);
            if ($verify) {
                $this->SetStatus(102);
                echo $this->Translate('Verify was successfull!');
                $ret = self::$BLINK_SUCCESS;
            } else {
                $this->SetStatus(203);
                echo $this->Translate('Verify was not successfull!');
            }
        } else {
            $this->SetStatus(203);
            echo $this->Translate('Verify was not successfull!');
        }
        // Return
        return $ret;
    }

    /**
     * Logout from the blink server.
     */
    public function Logout()
    {
        $ret = self::$BLINK_FAILURE;
        $account = $this->ReadAttributeString('AccountID');
        $client = $this->ReadAttributeString('ClientID');
        $token = $this->ReadAttributeString('AuthToken');
        $region = $this->ReadAttributeString('Region');
        // Safty check
        if (empty($token) || empty($account) || empty($client) || empty($region)) {
            $this->SendDebug(__FUNCTION__, 'Token: ' . $token . ', AccountID: ' . $account . ', ClientID: ' . $client . ', Region: ' . $region);
            echo $this->Translate('Logout not possible!');
            return $ret;
        }
        // API call
        $response = $this->doLogout($token, $region, $account, $client);
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->SendDebug(__FUNCTION__, $params);
            // Reset all
            $this->WriteAttributeString('AccountID', '');
            $this->WriteAttributeString('ClientID', '');
            $this->WriteAttributeString('AuthToken', '');
            $this->SetStatus(104);
            echo $this->Translate('Logout was successfull!');
            $ret = self::$BLINK_SUCCESS;
        } else {
            echo $this->Translate('Logout was not successfull!');
        }
        // Return
        return $ret;
    }

    /**
     * Display Account Notifications
     *
     * BHS_Notification();
     */
    public function Notification()
    {
        $ret = self::$BLINK_FAILURE;
        // Debug
        $this->SendDebug(__FUNCTION__, 'Obtain device information.', 0);
        // Safty check
        $verify = $this->ReadAttributeBoolean('Verify');
        if (!$verify) {
            echo $this->Translate('Logged out!');
            return $ret;
        }
        // Inputs
        $account = $this->ReadAttributeString('AccountID');
        $token = $this->ReadAttributeString('AuthToken');
        $region = $this->ReadAttributeString('Region');
        // API call
        $response = $this->doNotification($token, $region, $account);
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->SendDebug(__FUNCTION__, $params);
            // Prepeare Info
            $format = $this->Translate("Low battery: %s\nCamera offline: %s\nCamera usage: %s\nScheduling (On/Off): %s\nMotion: %s\nSync module offline: %s\nHigh temperature: %s\nDoorbell: %s\nWifi: %s\nLost frame received: %s\nBandwidth: %s\nBattery dead: %s\nLocal storage: %s\nAccessory connected: %s\nAccessory disconnected: %s\nAccessory low battery: %s\nGeneral: %s");
            $info = sprintf(
                $format,
                $params['notifications']['low_battery'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['camera_offline'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['camera_usage'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['scheduling'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['motion'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['sync_module_offline'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['temperature'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['doorbell'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['wifi'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['lfr'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['bandwidth'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['battery_dead'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['local_storage'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['accessory_connected'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['accessory_disconnected'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['accessory_low_battery'] ? $this->Translate('ON') : $this->Translate('OFF'),
                $params['notifications']['general'] ? $this->Translate('ON') : $this->Translate('OFF')
            );
            echo $info;
            $ret = self::$BLINK_SUCCESS;
        } else {
            echo $this->Translate('Call was not successfull!');
        }
        // Return
        return $ret;
    }

    /**
     * Execute a keep alive call
     */
    private function Heartbeat()
    {
        $heartbeat = $this->ReadPropertyInteger('HeartbeatInterval');
        // Safty check
        if ($heartbeat == 0) {
            return;
        }
        // Buffer echo
        ob_start();
        $return = $this->Login();
        $buf = ob_get_clean();
        if ($return != self::$BLINK_SUCCESS) {
            $this->LogMessage($buf, KL_ERROR);
        }
    }

    /**
     * Generates a valid v4 UUID in the form of 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx' (where y is 8, 9, A, or B)
     */
    private function UUIDv4()
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