<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// Blink Home Device
class BlinkHomeDevice extends IPSModule
{
    // Helper Traits
    use DebugHelper;
    use EventHelper;
    use FormatHelper;
    use ProfileHelper;
    use VariableHelper;

    // Profiles
    private const BLINK_PROFILE_UPDATE = [
        [1, '►', '', 0xFF8000],
    ];
    private const BLINK_PROFILE_BATTERY = [
        [0, 'unknown', '', 0xFF00FF],
        [1, 'critical', 'Battery-0', 0xFF0000],
        [2, 'low', 'Battery-50', 0xFFFF00],
        [3, 'ok', 'Battery-100', 0x00FF00],
    ];

    // Echo Maps
    private const BLINK_MAP_LIVEVIEW = [
        ['command_id', 'Command ID', 1],
        ['join_available', 'Join available', 0],
        ['join_state', 'Join state', 3],
        ['server', 'Server', 3],
        ['duration', 'Duration', 1],
        ['extended_duration', 'Extended duration', 1],
        ['continue_interval', 'Continue interval', 1],
        ['continue_warning', 'Continue warning', 1],
        ['polling_interval', 'Polling interval', 1],
    ];
    private const BLINK_MAP_SIGNALS = [
        ['power', 'Power supply', 3],
        ['lfr', 'Sync signal strength', 1],
        ['wifi', 'WiFi strength', 1],
        ['temp', 'Temperature', 1],
        ['battery', 'Battery', 1],
    ];

    // Schedule constant
    private const BLINK_SCHEDULE_SNAPSHOT_OFF = 1;
    private const BLINK_SCHEDULE_SNAPSHOT_ON = 2;
    private const BLINK_SCHEDULE_SNAPSHOT_IDENT = 'circuit_snapshot';
    private const BLINK_SCHEDULE_SNAPSHOT_SWITCH = [
        self::BLINK_SCHEDULE_SNAPSHOT_OFF => ['Inaktive', 0xFF0000, "IPS_RequestAction(\$_IPS['TARGET'], 'schedule_snapshot', \$_IPS['ACTION']);"],
        self::BLINK_SCHEDULE_SNAPSHOT_ON  => ['Aktive', 0x00FF00, "IPS_RequestAction(\$_IPS['TARGET'], 'schedule_snapshot', \$_IPS['ACTION']);"],
    ];

    // Command Call Interval
    private const BLINK_COMMAND_TIMER_INTERVAL = 2000;

    /**
     * Overrides the internal IPSModule::Create($id) function
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        // Connect to client
        $this->ConnectParent('{AF126D6D-83D1-44C2-6F61-96A4BB7A0E62}');
        // CommandStatus
        $this->RegisterAttributeString('CommandID', '');
        // Device
        $this->RegisterPropertyString('DeviceID', '');
        $this->RegisterPropertyString('NetworkID', '');
        $this->RegisterPropertyString('DeviceType', 'null');
        $this->RegisterPropertyString('DeviceModel', 'null');
        // Image
        $this->RegisterPropertyBoolean('ImageVariable', false);
        $this->RegisterPropertyBoolean('ImageMemory', true);
        $this->RegisterPropertyBoolean('ImageOverlay', false);
        $this->RegisterPropertyInteger('OverlayTop', 10);
        $this->RegisterPropertyInteger('OverlayLeft', 10);
        $this->RegisterPropertyInteger('OverlaySize', 10);
        $this->RegisterPropertyInteger('OverlayColor', 16777215); // Weiß
        $this->RegisterPropertyInteger('OverlayBackground', -1); // Transparent
        $this->RegisterPropertyString('OverlayFormat', 'd.m.Y H:i:s'); // Weiß
        $this->RegisterPropertyString('OverlayFont', '/usr/share/fonts/truetype/lato/Lato-Bold.ttf');
        // Schedule
        $this->RegisterPropertyInteger('ImageInterval', 0);
        $this->RegisterPropertyInteger('ImageSchedule', 0);
        // Variable
        $this->RegisterPropertyBoolean('UpdateImage', false);
        $this->RegisterPropertyBoolean('UpdateBattery', false);
        $this->RegisterPropertyBoolean('ResetCommand', false);
        // Register update timer
        $this->RegisterTimer('TimerSnapshot', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "snapshot", "");');
        $this->RegisterTimer('TimerCommand', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "command", "");');
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
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        // Return if parent is not confiured
        if (!$this->HasActiveParent()) {
            return json_encode($form);
        }

        // Snapshots enabled?
        $snapshot = $this->ReadPropertyBoolean('ImageVariable');

        // Power supply is battery?
        $model = $this->ReadPropertyString('DeviceModel');
        if (!in_array($model, ['mini', 'owl', 'hack'])) {
            $form['elements'][5]['items'][1]['visible'] = true;
        }

        // Buttons?
        $form['actions'][1]['items'][0]['enabled'] = $snapshot;

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

        $device = $this->ReadPropertyString('DeviceID');
        $model = $this->ReadPropertyString('DeviceModel');
        $image = $this->ReadPropertyBoolean('ImageVariable');
        $interval = $this->ReadPropertyInteger('ImageInterval');
        $cache = $this->ReadPropertyBoolean('ImageMemory');
        $update = $this->ReadPropertyBoolean('UpdateImage');
        $battery = $this->ReadPropertyBoolean('UpdateBattery');
        $schedule = $this->ReadPropertyInteger('ImageSchedule');
        // Register Profile
        $this->RegisterProfileInteger('BHS.Update', 'Script', '', '', 0, 0, 0, self::BLINK_PROFILE_UPDATE);
        $this->RegisterProfileInteger('BHS.Battery', 'Battery', '', '', 0, 3, 1, self::BLINK_PROFILE_BATTERY);
        // Motion detection
        if ($model != 'null') {
            $this->MaintainVariable('motion_detection', $this->Translate('Motion detection'), VARIABLETYPE_BOOLEAN, '~Switch', 1, true);
            $this->EnableAction('motion_detection');
        }
        // Update image
        $this->MaintainVariable('snapshot', $this->Translate('Snapshot'), VARIABLETYPE_INTEGER, 'BHS.Update', 2, $update & $image);
        if ($update & $image) {
            $this->SetValueInteger('snapshot', 1);
            $this->EnableAction('snapshot');
        }
        // Record clip
        $this->MaintainVariable('record', $this->Translate('Record'), VARIABLETYPE_INTEGER, 'BHS.Update', 4, true);
        $this->SetValueInteger('snapshot', 1);
        $this->EnableAction('record');
        // Update battery
        $this->MaintainVariable('battery', $this->Translate('Battery'), VARIABLETYPE_INTEGER, 'BHS.Battery', 3, $battery);
        // Media Object
        if ($image) {
            $this->CreateMediaImage('thumbnail', $this->Translate('Image'), $device, 'jpg', $cache);
            // Timer active?
            if ($interval > 0) {
                $rand = rand(30, 180) * 1000;
                // Timer solo or schedule?
                if ($schedule == 0) {
                    $this->SetTimerInterval('TimerSnapshot', $rand + (60 * 1000 * $interval));
                } else {
                    $wsi = $this->GetWeeklyScheduleInfo($schedule, time(), true);
                    $this->SendDebug(__FUNCTION__, $wsi);
                    if ($wsi['ActionID'] == self::BLINK_SCHEDULE_SNAPSHOT_ON) {
                        $this->SetTimerInterval('TimerSnapshot', $rand + (60 * 1000 * $interval));
                    }
                }
            }
        } else {
            // Timer Reset
            $this->SetTimerInterval('TimerSnapshot', 0);
        }
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
            case 'create_schedule':
                $this->CreateScheduleSnapshot();
                break;
            case 'motion_detection':
                $this->MotionDetection($value);
                break;
            case 'schedule_snapshot':
                $this->ScheduleSnapshot($value);
                break;
            case 'snapshot':
                $this->Thumbnail();
                break;
            case 'liveview':
                $this->LiveView();
                break;
            case 'record':
                $this->Record();
                break;
            case 'signals':
                $this->Signals();
                break;
            case 'command':
                $this->Command();
                break;
            case 'reset_command':
                $this->WriteAttributeString('CommandID', '');
                break;
            default:
                break;
        }
        //return true;
    }

    /**
     * Weekly Schedule event
     *
     * @param integer $value Action value (ON=2, OFF=1)
     */
    private function ScheduleSnapshot(int $value)
    {
        $schedule = $this->ReadPropertyInteger('ImageSchedule');
        $this->SendDebug(__FUNCTION__, 'Value: ' . $value . ',Schedule: ' . $schedule);
        if ($schedule == 0) {
            // nothing todo
            return;
        }
        // Is Activate OFF
        if ($value == self::BLINK_SCHEDULE_SNAPSHOT_OFF) {
            $this->SendDebug(__FUNCTION__, 'OFF: Deactivate schedule timer!');
            // Reset Timer
            $this->SetTimerInterval('TimerSnapshot', 0);
            return;
        }
        // Image schedule is aktiv?
        $image = $this->ReadPropertyBoolean('ImageVariable');
        $this->SendDebug(__FUNCTION__, 'ON: Image is:' . $image);
        if ($image == true) {
            $interval = $this->ReadPropertyInteger('ImageInterval');
            $this->SendDebug(__FUNCTION__, 'ON: Interval is:' . $interval);
            if ($interval > 0) {
                $this->SendDebug(__FUNCTION__, 'ON: Activate schedule timer:' . $interval);
                $this->SetTimerInterval('TimerSnapshot', 60 * 1000 * $interval);
                // Start with Update and than wait for Timer
                $this->Thumbnail();
            }
        }
    }

    /**
     * Set the thumbail by taking a snapshot of the current view of the camera.
     *
     * BHS_Thumbnail();
     */
    private function Thumbnail()
    {
        $command = $this->ReadAttributeString('CommandID');
        if ($command != '') {
            $this->SendDebug(__FUNCTION__, 'Command still active!');
            $reset = $this->ReadPropertyBoolean('ResetCommand');
            if ($reset) {
                $this->LogMessage('[' . IPS_GetName($this->InstanceID) . '] ' . ' Commando was reset!', KL_WARNING);
                $this->WriteAttributeString('CommandID', '');
            } else {
                $this->LogMessage('[' . IPS_GetName($this->InstanceID) . '] ' . ' Command still active!', KL_ERROR);
            }
            return;
        }
        $network = $this->ReadPropertyString('NetworkID');
        $device = $this->ReadPropertyString('DeviceID');
        $type = $this->ReadPropertyString('DeviceType');
        // Parameter
        $param = ['NetworkID' => $network, 'DeviceID' => $device, 'DeviceType' => $type];
        // Request
        $response = $this->RequestDataFromParent('thumbnail', $param);
        $this->SendDebug(__FUNCTION__, $response);
        if ($response === '[]') {
            $this->LogMessage('[' . IPS_GetName($this->InstanceID) . '] ' . 'No command info for thumbnail', KL_ERROR);
            return;
        }
        // Command
        $command = json_decode($response, true);
        $this->SendDebug(__FUNCTION__, $command);
        if (isset($command['id'])) {
            $this->WriteAttributeString('CommandID', $command['id']);
            // Start Trigger
            $this->SetTimerInterval('TimerCommand', self::BLINK_COMMAND_TIMER_INTERVAL);
        } else {
            $this->LogMessage('[' . IPS_GetName($this->InstanceID) . '] ' . $command['message'], KL_ERROR);
        }
    }

    /**
     * Enable / Disable motion detection
     */
    private function MotionDetection(bool $value)
    {
        $device = $this->ReadPropertyString('DeviceID');
        $network = $this->ReadPropertyString('NetworkID');
        $type = $this->ReadPropertyString('DeviceType');
        // Parameter
        $param = ['NetworkID' => $network, 'DeviceID' => $device, 'DeviceType' => $type, 'Detection' => $value];
        // Request
        $response = $this->RequestDataFromParent('motion', $param);
        if ($response === '[]') {
            $this->SendDebug(__FUNCTION__, 'Error occurred for switching motion detection');
        } else {
            $this->SetValueBoolean('motion_detection', $value);
        }
    }

    /**
     * Get Image from server
     */
    private function Image()
    {
        $response = $this->RequestDataFromParent('homescreen');
        if ($response === '[]') {
            $this->SendDebug(__FUNCTION__, 'No Result for HomeScreen!');
            return;
        }
        $device = $this->ReadPropertyString('DeviceID');
        $type = $this->ReadPropertyString('DeviceType');
        // find device
        $path = '';
        $devises = json_decode($response, true);
        if (isset($devises[$type])) {
            foreach ($devises[$type] as $dev) {
                if ($dev['id'] == $device) {
                    $path = $dev['thumbnail'];
                    // battery update
                    if (!isset($dev['battery'])) {
                        $this->SetValueInteger('battery', 4);
                    }
                    // if battery than signals
                    if (isset($dev['signals'])) {
                        if (isset($dev['signals']['battery'])) {
                            $this->SetValueInteger('battery', $dev['signals']['battery']);
                        }
                    }
                    break;
                }
            }
        }
        if ($path === '') {
            $this->SendDebug(__FUNCTION__, 'No Path for Thambnail!');
            return;
        }
        // get image
        $param = ['Path' => $path];
        // Request
        //FIXME: $response = utf8_decode($this->RequestDataFromParent('image', $param));
        $response = hex2bin($this->RequestDataFromParent('image', $param));
        if ($response === '[]') {
            $this->SendDebug(__FUNCTION__, 'No Image for Path!');
            return;
        }
        // Media object
        $mediaID = @$this->GetIDForIdent('thumbnail');
        if ($mediaID === false) {
            $this->SendDebug(__FUNCTION__, 'No Media for Content!');
            return;
        }
        // Create timestamp overlay?
        $overlay = $this->ReadPropertyBoolean('ImageOverlay');
        if ($overlay) {
            // Parameter
            $size = $this->ReadPropertyInteger('OverlaySize');
            $top = $this->ReadPropertyInteger('OverlayTop');
            $left = $this->ReadPropertyInteger('OverlayLeft');
            $rgb = $this->Int2RGB($this->ReadPropertyInteger('OverlayColor'));
            $bg = $this->ReadPropertyInteger('OverlayBackground');
            $font = $this->ReadPropertyString('OverlayFont');
            $format = $this->ReadPropertyString('OverlayFormat');
            // Text (Timestamp)
            $text = date($format, time());
            // GdImage
            $pic = imagecreatefromstring($response);
            $col = imagecolorallocate($pic, $rgb[0], $rgb[1], $rgb[2]);
            // Background
            if ($bg != -1) {
                $rgb = $this->Int2RGB($bg);
                $bgc = imagecolorallocate($pic, $rgb[0], $rgb[1], $rgb[2]);
                // BG Box
                $box = imagettfbbox($size, 0, $font, $text);
                $right = $box[4] - $box[6] + $left;
                $bottom = $box[3] - $box[5] + $top;
                // Add text background
                imagefilledrectangle($pic, $left, $top - 1, $right, $bottom, $bgc);
            }
            // Write Text
            imagettftext($pic, $size, 0, $left, $top + $size, $col, $font, $text);
            // Let's start output buffering.
            ob_start();
            // This will normally output the image, but because of ob_start(), it won't.
            imagejpeg($pic);
            // Instead, output above is saved to $contents
            $contents = ob_get_contents();
            // End the output buffer.
            ob_end_clean();
            // Copy to media variable
            IPS_SetMediaContent($mediaID, base64_encode($contents));
            IPS_SendMediaEvent($mediaID);
            // Free the memory and close the image
            imagedestroy($pic);
        } else {
            // No, write directly to media
            IPS_SetMediaContent($mediaID, base64_encode($response));
            IPS_SendMediaEvent($mediaID);
        }
    }

    /**
     * Get Live Video URL from server
     */
    private function LiveView()
    {
        $network = $this->ReadPropertyString('NetworkID');
        $device = $this->ReadPropertyString('DeviceID');
        $type = $this->ReadPropertyString('DeviceType');
        // Parameter
        $param = ['NetworkID' => $network, 'DeviceID' => $device, 'DeviceType' => $type];
        // Request
        $response = $this->RequestDataFromParent('liveview', $param);
        $this->SendDebug(__FUNCTION__, $response);
        if ($response === '[]') {
            $this->SendDebug(__FUNCTION__, 'Error occurred for liveview');
        }
        else {
            // Echo message
            $this->EchoMessage($this->PrettyPrint(self::BLINK_MAP_LIVEVIEW, $response));
        }
    }

    /**
     * Start a live recording clip.
     */
    private function Record()
    {
        $network = $this->ReadPropertyString('NetworkID');
        $device = $this->ReadPropertyString('DeviceID');
        $type = $this->ReadPropertyString('DeviceType');
        // Parameter
        $param = ['NetworkID' => $network, 'DeviceID' => $device, 'DeviceType' => $type];
        // Request
        $response = $this->RequestDataFromParent('record', $param);
        $this->SendDebug(__FUNCTION__, $response);
        if ($response === '[]') {
            $this->SendDebug(__FUNCTION__, 'Error occurred for liveview');
        }
        else {
            // Echo message
            $this->EchoMessage($this->PrettyPrint(null, $response));
        }
    }

    /**
     * Get Signal information
     */
    private function Signals()
    {
        $response = $this->RequestDataFromParent('homescreen');
        if ($response === '[]') {
            $this->SendDebug(__FUNCTION__, 'No Result for HomeScreen!');
            return;
        }
        $device = $this->ReadPropertyString('DeviceID');
        $type = $this->ReadPropertyString('DeviceType');
        // find device
        $devises = json_decode($response, true);
        $signals = [];
        if (isset($devises[$type])) {
            foreach ($devises[$type] as $dev) {
                if ($dev['id'] == $device) {
                    // battery or usb
                    if (isset($dev['battery'])) {
                        $signals['power'] = $this->Translate('Battery');
                    } else {
                        $signals['power'] = 'USB';
                        $this->SetValueInteger('battery', 4);
                    }
                    // if battery than signals
                    if (isset($dev['signals'])) {
                        $signals = array_merge($signals, $dev['signals']);
                        if (isset($signals['battery'])) {
                            $this->SetValueInteger('battery', $signals['battery']);
                        }
                    }
                    // temperature in celsius
                    if (isset($signals['temp'])) {
                        $signals['temp'] = round(($signals['temp'] - 32) / 9.0 * 5.0, 1) . '°C';
                    }
                    break;
                }
            }
        }
        // Echo message
        $this->EchoMessage($this->PrettyPrint(self::BLINK_MAP_SIGNALS, $signals));
    }

    /**
     * Command status polling
     */
    private function Command()
    {
        // Stop Timer
        $this->SetTimerInterval('TimerCommand', 0);
        // Parameter
        $network = $this->ReadPropertyString('NetworkID');
        $command = $this->ReadAttributeString('CommandID');
        // Safty check
        if ($command == '') {
            $this->LogMessage('[' . IPS_GetName($this->InstanceID) . '] ' . ' Zombie Command!', KL_ERROR);
            $this->SendDebug(__FUNCTION__, 'Zombie Command!');
            return;
        }
        $param = ['NetworkID' => $network, 'CommandID' => $command];
        // Request
        $response = $this->RequestDataFromParent('command', $param);
        if ($response === '[]') {
            $this->SendDebug(__FUNCTION__, 'No Result for Command!');
            return;
        }
        $command = json_decode($response, true);
        if ($command['complete'] == true) {
            $this->SendDebug(__FUNCTION__, $command);
            //$this->SendDebug(__FUNCTION__, 'Command completed!');
            $this->WriteAttributeString('CommandID', '');
            if ($command['status'] == 0) {
                $this->Image();
            } else {
                $this->LogMessage('[' . IPS_GetName($this->InstanceID) . '] ' . $command['status_msg'] . ': ' . $command['status'], KL_WARNING);
            }
        } else {
            $this->SetTimerInterval('TimerCommand', self::BLINK_COMMAND_TIMER_INTERVAL);
        }
    }

    /**
     * Returns the ascending list of category names for a given category id
     *
     * @param string $endpoint API endpoint request.
     * @return string Result of the API call.
     */
    private function RequestDataFromParent(string $endpoint, array $params = [])
    {
        return $this->SendDataToParent(json_encode([
            'DataID'      => '{83027B09-C481-91E7-6D24-BF49AA871452}',
            'Endpoint'    => $endpoint,
            'Params'      => $params,
        ]));
    }

    /**
     * Create week schedule for snapshots
     *
     */
    private function CreateScheduleSnapshot()
    {
        $eid = $this->CreateWeeklySchedule($this->InstanceID, $this->Translate('Schedule snapshot'), self::BLINK_SCHEDULE_SNAPSHOT_IDENT, self::BLINK_SCHEDULE_SNAPSHOT_SWITCH, -1);
        if ($eid !== false) {
            $this->UpdateFormField('ImageSchedule', 'value', $eid);
        }
    }

    /**
     * Create a media variable to take over the snapshots.
     *
     *  @param string $ident Ident.
     *  @param string $name Name
     *  @param string $filename Image file name.
     *  @param string $fileext Image file extension.
     *  @param bool §cache Use In-memory cache
     */
    private function CreateMediaImage(string $ident, string $name, string $filename, string $fileext = 'jpg', bool $cache = true)
    {
        $file = IPS_GetKernelDir() . 'media' . DIRECTORY_SEPARATOR . $filename . '.' . $fileext;  // Image-Datei

        $mediaID = @$this->GetIDForIdent($ident);
        if ($mediaID === false) {
            $mediaID = IPS_CreateMedia(1);
            IPS_SetParent($mediaID, $this->InstanceID);
            IPS_SetIdent($mediaID, $ident);
            IPS_SetMediaCached($mediaID, $cache);
            // Connect to file
            IPS_SetMediaFile($mediaID, $file, false);
            IPS_SetName($mediaID, $name);
            // Transparent png 1x1 Base64
            // $content = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
            // Set Content
            //IPS_SetMediaContent($mediaID, $content);
            // Update
            //IPS_SendMediaEvent($mediaID);
        }
    }

    /**
     * Create a red, green, blue array from the color value
     *
     * @param int $num Color value.
     * @return array Color splitted in red, green and blue values.
     */
    private function Int2RGB(int $num)
    {
        $rgb[0] = ($num & 0xFF0000) >> 16;
        $rgb[1] = ($num & 0x00FF00) >> 8;
        $rgb[2] = ($num & 0x0000FF);
        return $rgb;
    }

    /**
     * Show message via popup
     *
     * @param string $caption echo message
     */
    private function EchoMessage(string $caption)
    {
        $this->UpdateFormField('EchoMessage', 'caption', $this->Translate($caption));
        $this->UpdateFormField('EchoPopup', 'visible', true);
    }
}
