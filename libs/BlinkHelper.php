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
 *  - Logout : POST /api/v4/account/{AccountID}/client/{clientID}/logout
 *  * Verify Pin : POST /api/v4/account/{AccountID}/client/{ClientID}/pin/verify
 *
 * System
 *  - HomeScreen : GET /api/v3/accounts/{AccountID}/homescreen
 *  - Get Account Notification Flags : GET /api/v1/accounts/{AccountID}/notifications/configuration
 *  - Set Notification Flags : POST /api/v1/accounts/{AccountID}/notifications/configuration
 *  - Get Client Options : GET /api/v1/accounts/{AccountID}/clients/{ClientID}/options
 *  - Set Client Options : POST /client/{ClientID}/update
 *
 * Network
 *  - Command Status : GET /network/{NetworkID}/command/{CommandID}
 *  - Arm System : POST /api/v1/accounts/{AccountID}/networks/{NetworkID}/state/arm
 *  - Disarm System : POST api/v1/accounts/{AccountID}/networks/{NetworkID}/state/disarm
 *  - List Schedules : GET /api/v1/networks/{NetworkID}/programs
 *  - Enable Schedule : POST /api/v1/networks/{NetworkID}/programs/{ProgramID}/enable
 *  - Disable Schedule : POST /api/v1/networks/{NetworkID}/programs/{ProgramID}/disable
 *  - Update Schedule : POST /api/v1/networks/{NetworkID}/programs/{ProgramID/update
 *
 * Cameras
 *  - Enable Motion Detection : POST /network/{NetworkID}/camera/{CameraID}/enable
 *  - Disable Motion Detection : POST /network/{NetworkID}/camera/{CameraID}/disable
 *  - Get Current Thumbnail : GET /media/production/account/{AccountID}/network/{NetworkID}/camera/{CameraID}/{JPEG_File_Name}.jpg
 *  - Create New Thumbnail : POST /network/{NetworkID}/camera/{CameraID}/thumbnail
 *  - Liveview : POST /api/v5/accounts/{AccountID}/networks/{NetworkID}/cameras/{CameraID}/liveview
 *  - Record Video Clip from Camera : POST /network/{NetworkID}/camera/{CameraID}/clip
 *  - Get Camera Config : GET /network/{NetworkID}/camera/{CameraID}/config
 *  - Update Camera Config : POST /network/{NetworkID}/camera/{CameraID}/update
 *
 * Videos
 *  - Get Video Events : GET /api/v1/accounts/{AccountID}/media/changed?since={timestamp}&page={PageNumber}
 *  - Get Video : GET /api/v2/accounts/{AccountID}/media/clip/{mp4_Filename}
 *  - Get Video Thumbnail : GET /api/v2/accounts/{AccountID}/media/thumb/{jpg_filename}
 *  - Set Video Options : POST /api/v1/account/video_options
 *  - Delete Videos : POST /api/v1/accounts/{AccountID}/media/delete
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
    private static $APP_VERSION = '6.12.0';             // '6.7.0 (12379)', '6.6.1 (12346)', '6.2.7 (10212)'
    private static $CLIENT_NAME = 'IPSymconBlinkModul'; // BlinkApp
    private static $CLIENT_TYPE = 'ios';                // "android"
    private static $DEVICE_ID = 'IP-Symcon Modul';

    /**
     * Client Login to Blink Account on Blink Servers
     *
     * POST /api/v5/account/login
     *
     * Headers
     *      Content-Type - application/json
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
            'Content-Type: application/json',
        ];
        // prepeare body (Login v5)
        $body = [
            // 'app-build'         => 'IOS_12379',
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
     * POST /api/v4/account/{AccountID}/client/{ClientID}/pin/verify
     *
     * Headers
     *      Content-Type - application/json
     *      TOKEN-AUTH - session auth token
     *
     * Parameters
     *      AccountID - Account ID
     *      ClientID - Client ID
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
     * POST /api/v4/account/{AccountID}/client/{clientID}/logout
     *
     * Headers
     *      TOKEN-AUTH - session auth token
     *
     * Parameters
     *      AccountID - Account ID
     *      ClientID - Client ID
     */
    private function doLogout(string $token, string $region, string $account, string $client)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/api/v4/account/$account/client/$client/logout";
        // prepeare header
        $headers = [
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null, 'POST');
    }

    /**
     * Get Account Nofification Flags
     *
     * GET /api/v1/accounts/{AccountID}/notifications/configuration
     *
     * Headers
     *      TOKEN-AUTH - session auth token
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
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null);
    }

    /**
     * Arm the given network - that is, start recording/reporting motion events for enabled cameras.
     *
     * POST /api/v1/accounts/{AccountID}/networks/{NetworkID}/state/arm
     *
     * Headers
     *      TOKEN-AUTH - session auth token
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
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null, 'POST');
    }

    /**
     * Disarm the given network - that is, stop recording/reporting motion events for enabled cameras.
     *
     * POST /api/v1/accounts/{AccountID}/networks/{NetworkID}/state/arm
     *
     * Headers
     *      TOKEN-AUTH - session auth token
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
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null, 'POST');
    }

    /**
     * Retrieve Client "home screen" data. Returns detailed information about the Account including Network, Synch Module, and Camera Info.
     *
     * GET /api/v3/accounts/{AccountID}/homescreen
     *
     * Headers
     *      TOKEN-AUTH - session auth token
     *
     * Parameters
     *      AccountID - Account ID
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
     * Retrieve the JPEG thumbnail picture of the given camera. The URL path is specified in the thumbnail attribute of the camera,
     * for example from the HomeScreen call. Add the .jpg extension to the URL path.
     *
     * GET /media/production/account/{AccountId}/network/{NetworkID}/camera/{CameraId}/theClipFileName.jpg
     *
     * Headers
     *      TOKEN-AUTH - session auth token
     *
     * Response
     *      content-type: image/jpeg
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
     * POST /network/{NetworkID}/camera/{CameraID}/thumbnail
     *
     * Headers
     *      TOKEN-AUTH - session auth token
     *
     * Response
     *      A command object.  This call is asynchronous and is monitored by the Command Status API call using the returned Command Id.
     *
     */
    private function doThumbnail(string $token, string $region, string $account, string $network, string $device, string $type)
    {
        //$this->LogMessage('Region: ' . $region . ', Account: ' . $account . ', Network: ' . $network . ', Device: ' . $device . ', Type: ' . $type);
        // prepeare url
        if ($type == 'owls') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/owls/$device/thumbnail";
        } elseif ($type == 'doorbells') {
            $url = "https://rest-$region.immedia-semi.com/api/v1/accounts/$account/networks/$network/lotus/$device/thumbnail";
        } else {
            $url = "https://rest-$region.immedia-semi.com/network/$network/camera/$device/thumbnail";
        }
        // prepeare header
        $headers = [
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null, 'POST');
    }

    /**
     * Enable/Disable motion detection for the given Camera.
     * Note: No motion detection or video recording will take place unless the system is armed.
     *
     * POST /network/{NetworkID}/camera/{CameraID}/enable
     * POST /network/{NetworkID}/camera/{CameraID}/disable
     *
     * Headers
     *      TOKEN-AUTH - session auth token
     *
     * Response
     *      A command object. This call is asynchronous and is monitored by the Command Status API call using the returned Command Id.
     */
    private function doMotion(string $token, string $region, string $network, string $device, string $state)
    {
        // prepeare url
        $url = "https://rest-$region.immedia-semi.com/network/$network/camera/$device/$state";
        // prepeare header
        $headers = [
            'token-auth: ' . $token,
        ];
        // return request
        return $this->doRequest($url, $headers, null, 'POST');
    }

    /**
     * Return the status of the given command.
     *
     * GET /network/{NetworkID}/command/{CommandID}
     *
     * Headers
     *      TOKEN-AUTH - session auth token
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
        // prepeare curl call
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($request != null) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
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
        return $response;
    }
}
