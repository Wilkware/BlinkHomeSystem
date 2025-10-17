<?php

/**
 * BlinkHelper.php
 *
 * PHP Wrapper for Client API of the Blink Wire-Free HD Home Monitoring & Alert System.
 * https://github.com/MattTW/BlinkMonitorProtocol
 *
 * @package       traits
 * @author        Heiko Wilknitz <heiko@wilkware.de>
 * @copyright     2022 Heiko Wilknitz
 * @link          https://wilkware.de
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 *
 * ------------------------------ API Overview --------------------------------
 *
 * Initial server URL:
 *  - https://rest-prod.immedia-semi.com
 *  - see Login for notes on possible redirection to a locale specific server after login.
 * Auth Token:
 *  - Authentication is done by passing a TOKEN_AUTH header. The auth token is provided in the response to a successful login.
 * Account:
 *  - An account corresponds to a single set of login credentials. The Account ID is returned in a successful login response.
 * Client:
 *  - A unique client/app to the account. A single account may have many client apps.
 *    Clients that the Blink servers believe are new will generate an out-of-band PIN OTP workflow.
 *    The Client ID is returned in a successful login response.
 * Network:
 *  - A single account may have many networks. A network corresponds conceptually to a Blink Synch module.
 *    An account could have multiple networks/synch modules - e.g. multiple sites/homes.
 *    Network and Synch Module information associated with an account is returned in the homescreen call.
 * Camera:
 *  - A network (synch module) may have one or more cameras. Camera information is returned in the homescreen call.
 * Command:
 *  - Some operations reach out from the Blink Servers to your local Blink module.
 *    These operations are asynchronous and return a Command ID to be polled for completion via the Command Status call.
 *
 * ---------------------------- API Endpoints ---------------------------------
 *
 * Authentication:
 *  - Login : POST /api/v5/account/login
 *  - Logout : POST /api/v4/account/{account_id}/client/{client_id}/logout
 *  * Verify Pin : POST /api/v4/account/{account_id}/client/{client_id}/pin/verify
 *
 * System
 *  - HomeScreen : GET /api/v3/accounts/{account_id}/homescreen
 *  - Get Account Notification Flags : GET /api/v1/accounts/{account_id}/notifications/configuration
 *  - Set Notification Flags : POST /api/v1/accounts/{account_id}/notifications/configuration
 *  - Get Client Options : GET /api/v1/accounts/{account_id}/clients/{client_id}/options
 *  - Set Client Options : POST /client/{client_id}/update
 *
 * Network
 *  - Command Status : GET /network/{network_id}/command/{command_id}
 *  - Arm System : POST /api/v1/accounts/{account_id}/networks/{network_id}/state/arm
 *  - Disarm System : POST api/v1/accounts/{account_id}/networks/{network_id}/state/disarm
 *  - List Schedules : GET /api/v1/networks/{network_id}/programs
 *  - Enable Schedule : POST /api/v1/networks/{network_id}/programs/{program_id}/enable
 *  - Disable Schedule : POST /api/v1/networks/{network_id}/programs/{program_id}/disable
 *  - Update Schedule : POST /api/v1/networks/{network_id}/programs/{program_id/update
 *
 * Cameras
 *  - Enable Motion Detection : POST /network/{network_id}/camera/{camera_id}/enable
 *  - Disable Motion Detection : POST /network/{network_id}/camera/{camera_id}/disable
 *  - Get Current Thumbnail : GET /media/production/account/{account_id}/network/{network_id}/camera/{camera_id}/{JPEG_File_Name}.jpg
 *  - Create New Thumbnail : POST /network/{network_id}/camera/{camera_id}/thumbnail
 *  - Liveview : POST /api/v5/accounts/{account_id}/networks/{network_id}/cameras/{camera_id}/liveview
 *  - Record Video Clip from Camera : POST /network/{network_id}/camera/{camera_id}/clip
 *  - Get Camera Config : GET /network/{network_id}/camera/{camera_id}/config
 *  - Update Camera Config : POST /network/{network_id}/camera/{camera_id}/update
 *
 * Accessories
 *  - Enable Fllodlight: POST /api/v1/accounts/{account_id}/networks/{network_id}/cameras/${camera_id}/accessories/storm/${storm_id}/lights/on
 *  - Disable Fllodlight: POST /api/v1/accounts/{account_id}/networks/{network_id}/cameras/${camera_id}/accessories/storm/${storm_id}/lights/off
 *
 * Videos
 *  - Get Video Events : GET /api/v1/accounts/{account_id}/media/changed?since={timestamp}&page={PageNumber}
 *  - Get Video : GET /api/v2/accounts/{account_id}/media/clip/{mp4_Filename}
 *  - Get Video Thumbnail : GET /api/v2/accounts/{account_id}/media/thumb/{jpg_filename}
 *  - Set Video Options : POST /api/v1/account/video_options
 *  - Delete Videos : POST /api/v1/accounts/{account_id}/media/delete
 *
 * Misc
 *  - App Version Check : GET /api/v1/version
 *  - Get Regions : GET /regions?locale={Two Character Country Locale}
 *  - Upload Logs : POST /app/logs/upload
 *  - Account Options : GET /api/v1/account/options
 *
 */

declare(strict_types=1);

/**
 * Helper class for the debug output.
 */
trait BlinkHelper
{
    /**
     * @var int Logged out indicator value
     */
    private static $BLINK_LOGOUT = 0;

    /**
     * @var int Loggeg in indicator value
     */
    private static $BLINK_LOGIN = 1;

    /**
     * @var int Verfiy indicator value
     */
    private static $BLINK_VERIFY = 2;

    /**
     * @var string App version
     */
    private static $APP_VERSION = '48.1';

    /**
     * @var string App build number
     */
    private static $APP_BUILD = 'IOS_2509241604';

    /**
     * @var string Client name
     */
    private static $CLIENT_NAME = 'IPSymconBlinkModul';

    /**
     * @var string App client type (ios | android)
     */
    private static $CLIENT_TYPE = 'ios';

    /**
     * @var string Device id name
     */
    private static $DEVICE_ID = 'IP-Symcon Modul';

    /**
     * @var string User Agernt
     */
    private static $USER_AGENT = 'Blink/2210311418 CFNetwork/1399 Darwin/22.1.0';

    /**
     * @var int Request wait time in milli seconds
     */
    private static $REQUEST_WAIT = 1000;

    /**
     * @var int Request retry (5 trys by default)
     */
    private static $REQUEST_RETRY = 5;

    /**
     * Arm the given network - that is, start recording/reporting motion events for enabled cameras.
     *
     * POST /api/v1/accounts/{account_id}/networks/{network_id}/state/arm
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API
     *      call using the returned Command Id.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     *
     * @return string|false Response data or false on failure
     */
    private function doArm(string $token, string $region, string $account, string $network): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/state/arm";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null, 'POST');
    }

    /**
     * Retrieve the stored MP4 video clip.
     *
     * POST /api/v1/accounts/{account_id}/networks/{network_id}/sync_modules/{sync_modul}/local_storage/manifest/{manifest}/clip/request/{clip_id}
     *
     * Headers
     *      content-type: application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      An array with id's
     *
     * GET /api/v1/accounts/{account_id}/networks/{network_id}/sync_modules/{sync_modul_id}/local_storage/manifest/{manifest_id}/clip/request/{clip_id}
     *
     * Headers
     *      content-type: video/mp4
     *      token-auth - bearer auth token
     *
     * Response
     *      MP4 formated video
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     * @param string $device    Device ID
     * @param string $manifest  Manifest ID
     * @param string $clip      Clip ID
     *
     * @return string|false Response data or false on failure
     */
    private function doClip(string $token, string $region, string $account, string $network, string $device, string $manifest, string $clip): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/sync_modules/$device/local_storage/manifest/$manifest/clip/request/$clip";

        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];

        $retry = self::$REQUEST_RETRY;
        while ($retry--) {
            // read request
            $response = $this->SendRequest($url, $headers, null, 'POST');
            if ($response == false) {
                return $response;
            }
            // check result
            $params = json_decode($response, true);
            $this->LogDebug(__FUNCTION__, $params);
            if (isset($params['id'])) {
                // wait a little bit
                IPS_Sleep(self::$REQUEST_WAIT);
                // return request
                return $this->SendRequest($url, $headers, null);
            }
            // wait a little bit
            IPS_Sleep(self::$REQUEST_WAIT);
        }
        return false;
    }

    /**
     * Return the status of the given command.
     *
     * GET /network/{network_id}/command/{command_id}
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      An array of program objects
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $network   Network ID
     * @param string $command   Command ID
     *
     * @return string|false Response data or false on failure
     */
    private function doCommand(string $token, string $region, string $network, string $command): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/network/$network/command/$command";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null);
    }

    /**
     * Disarm the given network - that is, stop recording/reporting motion events for enabled cameras.
     *
     * POST /api/v1/accounts/{account_id}/networks/{network_id}/state/arm
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API
     *      call using the returned Command Id.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     *
     * @return string|false Response data or false on failure
     */
    private function doDisarm(string $token, string $region, string $account, string $network): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/state/disarm";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null, 'POST');
    }

    /**
     * Ask for a live video stream of the given camera
     *
     * GET /api/v1/accounts/{account_id}/media/changed?since={time_stamp}&page={page_number}
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Parameters
     *      since - a timestamp to return events since. e.g. 2020-08-03T16:50:24+0000.
     *              The official mobile client seems to use the epoch to return all available events - i.e. 1970-01-01T00:00:00+0000
     *      page - page number for multiple pages of results.
     *
     * Response
     *      An array of media event objects
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param int    $timestamp Timestamp
     * @param int    $page      Page number
     *
     * @return string|false Response data or false on failure
     */
    private function doEvents(string $token, string $region, string $account, int $timestamp = 0, int $page = 1): string|false
    {
        //format timestamp
        $ts = date('c', $timestamp);
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/media/changed?since=$ts&page=$page";

        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null);
    }

    /**
     * Retrieve Client "home screen" data. Returns detailed information about the Account including Network, Synch Module, and Camera Info.
     *
     * GET /api/v3/accounts/{account_id}/homescreen
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A array of account information.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     *
     * @return string|false Response data or false on failure
     */
    private function doHomeScreen(string $token, string $region, string $account): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v3/accounts/$account/homescreen";
        // prepeare header
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null);
    }

    /**
     * Retrieve the JPEG thumbnail picture of the given camera. The URL path is specified in the thumbnail attribute of the camera,
     * for example from the HomeScreen call. Add the .jpg extension to the URL path.
     *
     * GET /media/production/account/{account_id}/network/{network_id}/camera/{camera_id}/{clip_file_name}.jpg
     *
     * Headers
     *      content-type: image/jpeg
     *      token-auth - bearer auth token
     *
     * Response
     *      JPEG formated image
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $path      Path to the image
     *
     * @return string|false Response data or false on failure
     */
    private function doImage(string $token, string $region, string $path): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com" . $path;
        if (strpos($url, '.jpg') == false) {
            $url = $url . '.jpg';
        }
        // prepeare header
        $headers = [
            'content-type: image/jpeg',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null);
    }

    /**
     * Ask for a live video stream of the given camera
     *
     * POST /api/v5/accounts/{account_id}/networks/{network_id}/cameras/{camera_id}/liveview
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      An array of program objects
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     * @param string $device    Device ID
     * @param string $type      Device Type
     *
     * @return string|false Response data or false on failure
     */
    private function doLive(string $token, string $region, string $account, string $network, string $device, string $type): string|false
    {
        // prepeare url
        if ($type == 'owls') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/owls/$device/liveview";
        } elseif ($type == 'doorbells') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/doorbells/$device/liveview";
        } else {
            $url = "https://rest-$region.immedia-semi.com/api/v5/accounts/$account/networks/$network/cameras/$device/liveview";
        }
        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // prepeare body (v5)
        $body = [
            'intent: liveview',
            'motion_event_start_time: ',
        ];
        $request = json_encode($body);
        // return request
        return $this->SendRequest($url, $headers, $request);
    }
    /**
     * Enable/Disable floodlight for the given accessorie.
     *
     * POST /api/v1/accounts/{account_id}/networks/{network_id}/cameras/${camera_id}/accessories/storm/${storm_id}/lights/on
     * POST /api/v1/accounts/{account_id}/networks/{network_id}/cameras/${camera_id}/accessories/storm/${storm_id}/lights/off
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API
     *      call using the returned Command Id.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     * @param string $device    Devcie ID
     * @param string $storm     Strom ID
     * @param bool   $switch    On/Off state
     *
     * @return string|false Response data or false on failure
     */
    private function doLight(string $token, string $region, string $account, string $network, string $device, string $storm, bool $switch): string|false
    {
        // prepeare request
        $request = null;
        // transform value
        $state = $switch ? 'on' : 'off';
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/cameras/$device/accessories/storm/$storm/lights/$state";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, $request, 'POST');
    }

    /**
     * Request the local storage state of a sync modul.
     *
     * GET /api/v1/accounts/{account_id}/networks/{network_id}/owls/{camera_id}/config"
     * GET /network/{network_id}/camera/{camera_id}/config
     *
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API
     *      call using the returned Command Id.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     * @param string $device    Device ID
     * @param string $type      Device Type
     *
     * @return string|false Response data or false on failure
     */
    private function doConfig(string $token, string $region, string $account, string $network, string $device, string $type): string|false
    {
        // prepeare url
        if ($type == 'owls') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/owls/$device/config";
        } elseif ($type == 'cameras') {
            $url = "https://rest-$region.immedia-semi.com/network/$network/camera/$device/config";
        } else {
            $this->LogDebug(__FUNCTION__, 'Camera ' . $device . ' with product type ' . $type . ' config get not implemented.');
            return false;
        }

        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null);
    }

    /**
     * Request the local storage state of a sync modul.
     *
     * GET  /api/v1/accounts/{account_id}/networks/{network_id}/sync_modules/{sync_module_id}/local_storage/status")
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API
     *      call using the returned Command Id.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     * @param string $device    Device ID
     *
     * @return string|false Response data or false on failure
     */
    private function doLocalStorageStatus(string $token, string $region, string $account, string $network, string $device): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/sync_modules/$device/local_storage/status";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null);
    }

    /**
     * Client Login/Verify to Blink Account on Blink Servers (OAuth2)
     *
     * POST https://api.oauth.blink.com/oauth/token
     *
     * Headers
     *      content-type - application/x-www-form-urlencoded',
     *      hardware_id  - Unique ID,
     *      2fa-code     - <code> # only for verifying pin
     *
     * Body
     *      username    - Account userid/email
     *      password    - Account password
     *      grant_type  - 'password'
     *      client_id   - 'android',
     *      scope       - 'client',
     *
     * @param string        $username  User ID/Mail adresse
     * @param string        $password  User account password
     * @param string        $uuid      Unique ID
     * @param string|null   $code      PIN provided in email or sms
     * @param string|null   $token     refresh token for re-login
     *
     * @return string|false Response data or false on failure
     */
    private function doLogin(string $username, string $password, string $uuid, ?string $code, ?string $token): string|false
    {
        // prepeare url
        $url = 'https://api.oauth.blink.com/oauth/token';
        // prepeare header
        $headers = [
            'content-type: application/x-www-form-urlencoded',
            'hardware_id: ' . $uuid
        ];
        if ($code != null) {
            $headers[] = '2fa-code: ' . $code;
        }

        // prepeare body (Login oauth2)
        $body = [
            'username'   => $username,
            'client_id'  => 'android',
            'scope'      => 'client',
        ];

        if ($token != null) {
            $body['grant_type'] = 'refresh_token';
            $body['refresh_token'] = $token;
        } else {
            $body['grant_type'] = 'password';
            $body['password'] = $password;
        }

        $request = http_build_query($body);
        // return request
        return $this->SendRequest($url, $headers, $request);
    }

    /**
     * Client Logout Blink Account on Blink Servers
     *
     * POST /api/v4/account/{account_id}/client/{client_id}/logout
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API
     *      call using the returned Command Id.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $client    Client ID
     *
     * @return string|false Response data or false on failure
     */
    private function doLogout(string $token, string $region, string $account, string $client): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v4/account/$account/client/$client/logout";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null, 'POST');
    }

    /**
     * Ask for local storage manifest, which lists all stored clips.
     *
     * POST /api/v1/accounts/{account_id}/networks/{network_id}/sync_modules/{sync_modul_id}/local_storage/manifest/request
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      An array with the request manfest id
     *
     * GET /api/v1/accounts/{account_id}/networks/{network_id}/sync_modules/{sync_modul_id}/local_storage/manifest/request/{manifest_request_id}
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      An array with the manfest id and clip id's
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     * @param string $device    Device ID
     *
     * @return string|false Response data or false on failure
     */
    private function doManifest(string $token, string $region, string $account, string $network, string $device): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/sync_modules/$device/local_storage/manifest/request";

        // prepeare header
        $headers = [
            'content-type: application/json; charset=utf-8',
            'authorization: Bearer ' . $token
        ];

        $retry = self::$REQUEST_RETRY;
        while ($retry--) {
            // read request
            $response = $this->SendRequest($url, $headers, null, 'POST');
            if ($response == false) {
                return $response;
            }
            // check result
            $params = json_decode($response, true);
            $this->LogDebug(__FUNCTION__, $params);
            if (isset($params['id'])) {
                $id = $params['id'];
                // wait a little bit
                IPS_Sleep(self::$REQUEST_WAIT);
                // prepeare url
                $url = $url . "/$id";
                // return request
                return $this->SendRequest($url, $headers, null);
            }
            // wait a little bit
            IPS_Sleep(self::$REQUEST_WAIT);
        }
        return false;
    }

    /**
     * Enable/Disable motion detection for the given Camera.
     * Note: No motion detection or video recording will take place unless the system is armed.
     *
     * POST /network/{network_id}/camera/{camera_id}/enable
     * POST /network/{network_id}/camera/{camera_id}/disable
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API
     *      call using the returned Command Id.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     * @param string $device    Device ID
     * @param string $type      Device Type
     * @param bool   $detection true=on / false=off
     *
     * @return string|false Response data or false on failure
     */
    private function doMotion(string $token, string $region, string $account, string $network, string $device, string $type, bool $detection): string|false
    {
        // prepeare request
        $request = null;
        // transform value
        $state = $detection ? 'enable' : 'disable';
        // prepeare url
        if ($type == 'owls') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/owls/$device/config";
            $body = [
                'enabled' => $detection,
            ];
            $request = json_encode($body);
        } elseif ($type == 'doorbells') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/doorbells/$device/$state";
        } else {
            $url = "https://rest-$region.immedia-semi.com/network/$network/camera/$device/$state";
        }
        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, $request, 'POST');
    }

    /**
     * Get Account Nofification Flags
     *
     * GET/POST /api/v1/accounts/{account_id}/notifications/configuration
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      Flag status for various notifications, see example
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     *
     * @return string|false Response data or false on failure
     */
    private function doNotification(string $token, string $region, string $account): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/notifications/configuration";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // body
        $body = [
            'notifications' => [
                'camera_usage' => true,
            ],
        ];
        $request = json_encode($body);
        // return request
        return $this->SendRequest($url, $headers, null);
    }

    /**
     * Get network info for sync-less module.
     *
     * GET /network/{network_id}
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A array of network information.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $network   Network ID
     *
     * @return string|false Response data or false on failure
     */
    private function doNetwork(string $token, string $region, string $network): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/network/$network";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null);
    }

    /**
     * Starts recording a clip..
     *
     * POST /network/{network_id}/camera/{camera_id}/clip"
     * POST /api/v1/accounts/{account_id}/networks({network_id}/owls/{device_id}/clip
     * POST /api/v1/accounts/{account_id}/networks({network_id}/doorbells/{device_id}/clip
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API
     *      call using the returned Command Id.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     * @param string $device    Device ID
     * @param string $type      Device Type
     *
     * @return string|false Response data or false on failure
     */
    private function doRecord(string $token, string $region, string $account, string $network, string $device, string $type): string|false
    {
        // prepeare url
        if ($type == 'owls') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/owls/$device/clip";
        } elseif ($type == 'doorbells') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/doorbells/$device/clip";
        } else {
            $url = "https://rest-$region.immedia-semi.com/network/$network/camera/$device/clip";
        }

        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null, 'POST');
    }

    /**
     * Set the thumbail by taking a snapshot of the current view of the camera.
     *
     * POST /network/{network_id}/camera/{camera_id}/thumbnail
     * POST /api/v1/accounts/{account_id}/networks({network_id}/owls/{device_id}/thumbnail
     * POST /api/v1/accounts/{account_id}/networks({network_id}/doorbells/{device_id}/thumbnail
     *
     * Headers
     *      content-type - application/json
     *      token-auth - bearer auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API
     *      call using the returned Command Id.
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $account   Account ID
     * @param string $network   Network ID
     * @param string $device    Device ID
     * @param string $type      Device Type
     *
     * @return string|false Response data or false on failure
     */
    private function doThumbnail(string $token, string $region, string $account, string $network, string $device, string $type): string|false
    {
        // prepeare url
        if ($type == 'owls') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/owls/$device/thumbnail";
        } elseif ($type == 'doorbells') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/doorbells/$device/thumbnail";
        } else {
            $url = "https://rest-$region.immedia-semi.com/network/$network/camera/$device/thumbnail";
        }

        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];
        // return request
        return $this->SendRequest($url, $headers, null, 'POST');
    }

    /**
     * Retrieve the account_id and region info.
     *
     * GET /api/v1/users/tier_info
     *
     * Headers
     *      content-type - video/mp4
     *      authorization - bearer auth token
     *
     * Response
     *      Account and region information
     *
     * @param string $token Auth Bearer token
     *
     * @return string|false Response data or false on failure
     */
    private function doTier(string $token): string|false
    {
        // prepeare url
        $url = 'https://rest-prod.immedia-semi.com/api/v1/users/tier_info';

        // prepeare header
        $headers = [
            'content-type: application/json',
            'authorization: Bearer ' . $token
        ];

        // return request
        return $this->SendRequest($url, $headers, null);
    }

    /**
     * Retrieve the stored MP4 video event.
     *
     * GET /api/v3/media/accounts/{account_id}/networks/{network_id}/{type_id}/{device_id}/pir/{video_id}.mp4
     *
     * Headers
     *      content-type: video/mp4
     *      token-auth - bearer auth token
     *
     * Response
     *      MP4 formated video
     *
     * @param string $token     Auth Bearer token
     * @param string $region    Region code
     * @param string $media     Media path/name
     *
     * @return string|false Response data or false on failure
     */
    private function doVideo(string $token, string $region, string $media): string|false
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com$media";

        // prepeare header
        $headers = [
            'content-type: video/mp4',
            'authorization: Bearer ' . $token
        ];

        // return request
        return $this->SendRequest($url, $headers, null);
    }

    /*
     * SendRequest - Sends the request to the device
     *
     * If $request not null, we will send a POST request, else a GET request.
     * Over the $method parameter can we force a POST or GET request!
     *
     * @param string               $url     URL to call
     * @param array<string,string> $headers Header as key => value pairs
     * @param string|null          $request Request body or null for GET
     * @param string               $method  HTTP method ('GET' or 'POST')
     *
     * @return string|false Response data or false on failure
     * @phpstan-ignore missingType.iterableValue
     */
    private function SendRequest(string $url, array $headers, ?string $request, string $method = 'GET'): string|false
    {
        $this->LogDebug(__FUNCTION__, $url);
        $this->LogDebug(__FUNCTION__, $headers);

        // prepeare curl call
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($request != null) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            $this->LogDebug(__FUNCTION__, '@POST ' . $request);
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');

        if (!$response = curl_exec($curl)) {
            $error = sprintf('Request failed for URL: %s - Error: %s', $url, curl_error($curl));
            $this->LogDebug(__FUNCTION__, $error);
        }
        curl_close($curl);
        $this->LogDebug(__FUNCTION__, $response);
        return $response;
    }
}
