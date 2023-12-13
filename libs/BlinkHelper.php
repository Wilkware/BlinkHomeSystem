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
    // State constants
    private static $BLINK_FAILURE = 0;
    private static $BLINK_SUCCESS = 1;
    private static $BLINK_WEAKNESS = 2;
    // App constants
    // private static $UNIQUE_UID = '056952C3-95C6-3B98-E400-D597B6141F74';
    private static $APP_VERSION = '6.30.2';             //  '6.30.2 (2310051512)'
    private static $APP_BUILD = 'IOS_2310051512';
    private static $CLIENT_NAME = 'IPSymconBlinkModul';
    private static $CLIENT_TYPE = 'ios';                // "android"
    private static $DEVICE_ID = 'IP-Symcon Modul';
    // User Agernt
    private static $USER_AGENT = 'Blink/2210311418 CFNetwork/1399 Darwin/22.1.0';
    //'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36';
    // Request constants
    private static $REQUEST_WAIT = 1000; // 1 Second

    /**
     * Client Login to Blink Account on Blink Servers
     *
     * POST /api/v5/account/login
     *
     * Headers
     *      content-type - application/json
     *
     * Body
     *      email - Account userid/email
     *      password - Account password
     *      unique_id - (optional) UUID generated and identifying the client.
     */
    private function doLogin($email, $password, $uuid)
    {
        // prepeare url
        $url = 'https://rest-prod.immedia-semi.com/api/v5/account/login';
        // prepeare header
        $headers = [
            'content-type: application/json',
        ];
        // prepeare body (Login v5)
        $body = [
            // 'app-build'         => self::$APP_BUILD,
            // 'app_version'       => self::$APP_VERSION,
            'client_name'       => self::$CLIENT_NAME,
            'client_type'       => self::$CLIENT_TYPE,
            'device_identifier' => self::$DEVICE_ID,
            'reauth'            => 'true',
            'unique_id'         => $uuid,
            'password'          => $password,
            'email'             => $email,
        ];
        $request = json_encode($body);
        // return request
        return $this->doRequest($url, $headers, $request);
    }

    /**
     * Verify client with PIN provided in an email or sms.
     * *
     * POST /api/v4/account/{account_id}/client/{client_id}/pin/verify
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Parameters
     *      account_id - Account ID
     *      client_id - Client ID
     *
     * Body
     *      pin - PIN provided in email
     */
    private function doVerify(string $token, string $code, string $region, string $account, string $client)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v4/account/$account/client/$client/pin/verify";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // prepeare body (Login v5)
        $body = [
            'pin' => $code,
        ];
        $request = json_encode($body);
        // return request
        return $this->doRequest($url, $headers, $request);
    }

    /**
     * Client Logout Blink Account on Blink Servers
     *
     * POST /api/v4/account/{account_id}/client/{client_id}/logout
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Parameters
     *      account_id - Account ID
     *      client_id - Client ID
     */
    private function doLogout(string $token, string $region, string $account, string $client)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v4/account/$account/client/$client/logout";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null, 'POST');
    }

    /**
     * Get Account Nofification Flags
     *
     * GET/POST /api/v1/accounts/{account_id}/notifications/configuration
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Response
     *      Flag status for various notifications, see example
     */
    private function doNotification(string $token, string $region, string $account)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/notifications/configuration";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // body
        $body = [
            'notifications' => [
                'camera_usage' => true,
            ],
        ];
        $request = json_encode($body);
        // return request
        return $this->doRequest($url, $headers, null);
    }

    /**
     * Arm the given network - that is, start recording/reporting motion events for enabled cameras.
     *
     * POST /api/v1/accounts/{account_id}/networks/{network_id}/state/arm
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Response
     *  A command object.
     *
     */
    private function doArm(string $token, string $region, string $account, string $network)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/state/arm";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null, 'POST');
    }

    /**
     * Disarm the given network - that is, stop recording/reporting motion events for enabled cameras.
     *
     * POST /api/v1/accounts/{account_id}/networks/{network_id}/state/arm
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Response
     *  A command object.
     *
     */
    private function doDisarm(string $token, string $region, string $account, string $network)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/state/disarm";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null, 'POST');
    }

    /**
     * Request the local storage state of a sync modul.
     *
     * GET  /api/v1/accounts/{account}/networks/{network}/sync_modules/{sync_module_id}/local_storage/status")
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Response
     *  A command object.
     *
     */
    private function doLocalStorageStatus(string $token, string $region, string $account, string $network, string $device)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/sync_modules/$device/local_storage/status";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null);
    }

    /**
     * Retrieve Client "home screen" data. Returns detailed information about the Account including Network, Synch Module, and Camera Info.
     *
     * GET /api/v3/accounts/{account_id}/homescreen
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Parameters
     *      account_id - Account ID
     *
     */
    private function doHomeScreen(string $token, string $region, string $account)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v3/accounts/$account/homescreen";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null);
    }

    /**
     * Get network info for sync-less module.
     *
     * GET /network/{network_id}
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Parameters
     *      account_id - Account ID
     *
     */
    private function doNetwork(string $token, string $region, string $network)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/network/$network";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null);
    }

    /**
     * Retrieve the JPEG thumbnail picture of the given camera. The URL path is specified in the thumbnail attribute of the camera,
     * for example from the HomeScreen call. Add the .jpg extension to the URL path.
     *
     * GET /media/production/account/{account_id}/network/{network_id}/camera/{camera_id}/{clip_file_name}.jpg
     *
     * Headers
     *      content-type: image/jpeg
     *      token-auth - session auth token
     *
     * Response
     *      JPEG formated image
     */
    private function doImage(string $token, string $region, string $path)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com" . $path;
        if (strpos($url, '.jpg') == false) {
            $url = $url . '.jpg';
        }
        // prepeare header
        $headers = [
            'content-type: image/jpeg',
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null);
    }

    /**
     * Set the thumbail by taking a snapshot of the current view of the camera.
     *
     * POST /network/{network_id}/camera/{camera_id}/thumbnail
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Response
     *      A command object.  This call is asynchronous and is monitored by the Command Status API call using the returned Command Id.
     *
     */
    private function doThumbnail(string $token, string $region, string $account, string $network, string $device, string $type)
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
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null, 'POST');
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
     *      token-auth - session auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API call using the returned Command Id.
     */
    private function doMotion(string $token, string $region, string $account, string $network, string $device, string $type, bool $detection)
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
            'token-auth: ' . $token,
            //            'user-agent: ' . self::$USER_AGENT,
        ];
        // return request
        return $this->doRequest($url, $headers, $request, 'POST');
    }

    /**
     * Return the status of the given command.
     *
     * GET /network/{network_id}/command/{command_id}
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Response
     *      An array of program objects
     */
    private function doCommand(string $token, string $region, string $network, string $command)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/network/$network/command/$command";
        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null);
    }

    /**
     * Ask for a live video stream of the given camera
     *
     * POST /api/v5/accounts/{account_id}/networks/{network_id}/cameras/{camera_id}/liveview
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Response
     *      An array of program objects
     */
    private function doLive(string $token, string $region, string $account, string $network, string $device, string $type)
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
            'token-auth: ' . $token,
        ];
        // prepeare body (v5)
        $body = [
            'intent: liveview',
            'motion_event_start_time: ',
        ];
        $request = json_encode($body);
        // return request
        return $this->doRequest($url, $headers, $request);
    }

    /**
     * Ask for a live video stream of the given camera
     *
     * GET /api/v1/accounts/{account_id}/media/changed?since={timestamp}&page={PageNumber}
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Parameters
     *      since - a timestamp to return events since. e.g. 2020-08-03T16:50:24+0000.
     *              The official mobile client seems to use the epoch to return all available events - i.e. 1970-01-01T00:00:00+0000
     *      page - page number for multiple pages of results.
     *
     * Response
     *      An array of media event objects
     */
    private function doEvents(string $token, string $region, string $account, int $timestamp = 0, int $page = 1)
    {
        //format timestamp
        $ts = date('c', $timestamp);
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/media/changed?since=$ts&page=$page";

        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null);
    }

    /**
     * Ask for local storage manifest, which lists all stored clips.
     *
     * POST /api/v1/accounts/{account_id}/networks/{network_id}/sync_modules/{sync_modul_id}/local_storage/manifest/request
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Response
     *      An array with the request manfest id
     *
     * GET /api/v1/accounts/{account}/networks/{network}/sync_modules/{sync_modul}/local_storage/manifest/request/{manifest_request_id}
     *
     * Headers
     *      content-type - application/json
     *      token-auth - session auth token
     *
     * Response
     *      An array with the manfest id and clip id's
     */
    private function doManifest(string $token, string $region, string $account, string $network, string $device)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/sync_modules/$device/local_storage/manifest/request";

        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // read request
        $response = $this->doRequest($url, $headers, null, 'POST');
        if ($response == false) {
            return $response;
        }
        // check result
        $params = json_decode($response, true);
        $this->SendDebug(__FUNCTION__, $params);
        if (!isset($params['id'])) {
            return $response;
        }
        $id = $params['id'];
        // wait a little bit
        IPS_Sleep(self::$REQUEST_WAIT);
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/sync_modules/$device/local_storage/manifest/request/$id";
        // return request
        return $this->doRequest($url, $headers, null);
    }

    /**
     * Retrieve the stored MP4 video clip.
     *
     * POST /api/v1/accounts/{account}/networks/{network}/sync_modules/{sync_modul}/local_storage/manifest/{manifest}/clip/request/{clip}
     *
     * Headers
     *      content-type: application/json
     *      token-auth - session auth token
     *
     * Response
     *      An array with id's
     *
     * GET /api/v1/accounts/{account}/networks/{network}/sync_modules/{sync_modul}/local_storage/manifest/{manifest}/clip/request/{clip}
     *
     * Headers
     *      content-type: video/mp4
     *      token-auth - session auth token
     *
     * Response
     *      MP4 formated video
     */
    private function doClip(string $token, string $region, string $account, string $network, string $device, string $manifest, string $clip)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/sync_modules/$device/local_storage/manifest/$manifest/clip/request/$clip";

        // prepeare header
        $headers = [
            'content-type: application/json',
            'token-auth: ' . $token,
        ];
        // read request
        $response = $this->doRequest($url, $headers, null, 'POST');
        if ($response == false) {
            return $response;
        }
        // check result
        $params = json_decode($response, true);
        $this->SendDebug(__FUNCTION__, $params);
        if (!isset($params['id'])) {
            return $response;
        }
        // wait a little bit
        IPS_Sleep(self::$REQUEST_WAIT);
        // return request
        return $this->doRequest($url, $headers, null);
    }

    /**
     * Retrieve the stored MP4 video event.
     *
     * GET /api/v3/media/accounts/{account}/networks/{network}/{type}/{device}/pir/{videoid}.mp4
     *
     * Headers
     *      content-type: video/mp4
     *      token-auth - session auth token
     *
     * Response
     *      MP4 formated video
     */
    private function doVideo(string $token, string $region, string $media)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com$media";

        // prepeare header
        $headers = [
            'content-type: video/mp4',
            'token-auth: ' . $token,
        ];

        // return request
        return $this->doRequest($url, $headers, null);
    }

    /*
     * doRequest - Sends the request to the device
     *
     * If $request not null, we will send a POST request, else a GET request.
     * Over the $method parameter can we force a POST or GET request!
     *
     * @param string $url Url to call
     * @param array $header Header information
     * @param string $request Request data
     * @param string $mehtod 'GET' od 'POST'
     * @return mixed response data or false.
     */
    private function doRequest(string $url, array $headers, ?string $request, string $method = 'GET')
    {
        $this->SendDebug(__FUNCTION__, $url, 0);
        $this->SendDebug(__FUNCTION__, $headers, 0);
        // prepeare curl call
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($request != null) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            $this->SendDebug(__FUNCTION__, '@POST ' . $request, 0);
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');

        if (!$response = curl_exec($curl)) {
            $error = sprintf('Request failed for URL: %s - Error: %s', $url, curl_error($curl));
            $this->SendDebug(__FUNCTION__, $error, 0);
        }
        curl_close($curl);
        $this->SendDebug(__FUNCTION__, $response, 0);
        return $response;
    }
}
