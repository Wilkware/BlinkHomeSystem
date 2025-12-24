<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// Blink Home Device
class BlinkHomeDevice extends IPSModuleStrict
{
    // Helper Traits
    use DebugHelper;
    use EventHelper;
    use FormatHelper;
    use ProfileHelper;
    use VariableHelper;

    /**
     * @var array<int,array{0:int,1:string,2:string,3:int}> Profile UPDATE
     */
    private const BLINK_PROFILE_UPDATE = [
        [1, '►', '', 0xFF8000],
    ];

    /**
     * @var array<int,array{0:int,1:string,2:string,3:int}> Profile BATTERY
     */
    private const BLINK_PROFILE_BATTERY = [
        [0, 'unknown', '', 0xFF00FF],
        [1, 'critical', 'Battery-0', 0xFF0000],
        [2, 'low', 'Battery-50', 0xFFFF00],
        [3, 'ok', 'Battery-100', 0x00FF00],
    ];

    /**
     * @var array<int,array{0:string,1:string,2:int,3:?string}> Echo map LIVEVIEW
     */
    private const BLINK_MAP_LIVEVIEW = [
        ['command_id', 'Command ID', 1, null],
        ['join_available', 'Join available', 0, null],
        ['join_state', 'Join state', 3, null],
        ['server', 'Server', 3, null],
        ['duration', 'Duration', 1, null],
        ['extended_duration', 'Extended duration', 1, null],
        ['continue_interval', 'Continue interval', 1, null],
        ['continue_warning', 'Continue warning', 1, null],
        ['polling_interval', 'Polling interval', 1, null],
    ];

    /**
     * @var array<int,array{0:string,1:string,2:int,3:?string}> Echo map SIGNALS
     */
    private const BLINK_MAP_SIGNALS = [
        ['power', 'Power supply', 3, null],
        ['lfr', 'Sync signal strength', 1, null],
        ['wifi', 'WiFi strength', 1, null],
        ['temp', 'Temperature', 1, null],
        ['battery', 'Battery', 1, null],
    ];

    /**
     * @var int Schedule snapshot constant OFF
     */
    private const BLINK_SCHEDULE_SNAPSHOT_OFF = 1;
    /**
     * @var int Schedule snapshot constant ON
     */
    private const BLINK_SCHEDULE_SNAPSHOT_ON = 2;

    /**
     * @var string Schedule snapshot constant IDENT
     */
    private const BLINK_SCHEDULE_SNAPSHOT_IDENT = 'circuit_snapshot';

    /**
     * @var array<int,array{0:string,1:int,2:string}> Schedule snapshot constant ACTION (Switch)
     */
    private const BLINK_SCHEDULE_SNAPSHOT_SWITCH = [
        self::BLINK_SCHEDULE_SNAPSHOT_OFF => ['Inaktive', 0xFF0000, "IPS_RequestAction(\$_IPS['TARGET'], 'schedule_snapshot', \$_IPS['ACTION']);"],
        self::BLINK_SCHEDULE_SNAPSHOT_ON  => ['Aktive', 0x00FF00, "IPS_RequestAction(\$_IPS['TARGET'], 'schedule_snapshot', \$_IPS['ACTION']);"],
    ];

    /**
     * @var int Command Call Interval
     */
    private const BLINK_COMMAND_TIMER_INTERVAL = 2000;

    /**
     * @var string ModulID (Blink Home Client)
     */
    private const BLINK_CLIENT_GUID = '{AF126D6D-83D1-44C2-6F61-96A4BB7A0E62}';

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
        // Connect to client
        if ((float) IPS_GetKernelVersion() < 8.2) {
            $this->ConnectParent(self::BLINK_CLIENT_GUID);
        }
        // CommandStatus
        $this->RegisterAttributeString('CommandID', '');
        // Authdata for LiveView
        $this->RegisterAttributeString('AuthData', '');
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
        // Liveview
        $this->RegisterPropertyBoolean('LiveViewEnable', false);
        $this->RegisterPropertyString('LiveViewServer', '');
        // Variable
        $this->RegisterPropertyBoolean('UpdateImage', false);
        $this->RegisterPropertyBoolean('UpdateBattery', false);
        $this->RegisterPropertyBoolean('ResetCommand', false);
        // Register update timer
        $this->RegisterTimer('TimerSnapshot', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "snapshot", "");');
        $this->RegisterTimer('TimerCommand', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "command", "");');

        // Set visualization type to 1, as we want to offer HTML
        $this->SetVisualizationType(0);
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
        if (!in_array($model, ['mini', 'hawk', 'chickadee', 'owl', 'hack'])) {
            $form['elements'][5]['items'][1]['visible'] = true;
        }

        // Buttons?
        $form['actions'][1]['items'][0]['enabled'] = $snapshot;

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

        $device = $this->ReadPropertyString('DeviceID');
        $model = $this->ReadPropertyString('DeviceModel');
        $image = $this->ReadPropertyBoolean('ImageVariable');
        $interval = $this->ReadPropertyInteger('ImageInterval');
        $cache = $this->ReadPropertyBoolean('ImageMemory');
        $update = $this->ReadPropertyBoolean('UpdateImage');
        $battery = $this->ReadPropertyBoolean('UpdateBattery');
        $schedule = $this->ReadPropertyInteger('ImageSchedule');
        $liveview = $this->ReadPropertyBoolean('LiveViewEnable');
        // Register Profile
        $this->RegisterProfileInteger('BHS.Update', 'Script', '', '', 0, 0, 0, self::BLINK_PROFILE_UPDATE);
        $this->RegisterProfileInteger('BHS.Battery', 'Battery', '', '', 0, 3, 1, self::BLINK_PROFILE_BATTERY);
        // Motion detection
        if ($model != 'null') {
            $this->MaintainVariable('motion_detection', $this->Translate('Motion detection'), VARIABLETYPE_BOOLEAN, '~Switch', 1, true);
            $this->EnableAction('motion_detection');
        }
        // Update image
        $this->MaintainVariable('snapshot', $this->Translate('Snapshot'), VARIABLETYPE_INTEGER, 'BHS.Update', 2, $update && $image);
        if ($update & $image) {
            $this->SetValueInteger('snapshot', 1);
            $this->EnableAction('snapshot');
        }
        // Record clip
        $this->MaintainVariable('record', $this->Translate('Record'), VARIABLETYPE_INTEGER, 'BHS.Update', 3, true);
        $this->SetValueInteger('record', 1);
        $this->EnableAction('record');
        // Update battery
        $this->MaintainVariable('battery', $this->Translate('Battery'), VARIABLETYPE_INTEGER, 'BHS.Battery', 4, $battery);
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
                    $this->LogDebug(__FUNCTION__, $wsi);
                    if ($wsi['ActionID'] == self::BLINK_SCHEDULE_SNAPSHOT_ON) {
                        $this->SetTimerInterval('TimerSnapshot', $rand + (60 * 1000 * $interval));
                    }
                }
            }
        }
        // Timer Reset
        if (!$image || ($interval <= 0)) {
            $this->SetTimerInterval('TimerSnapshot', 0);
        }
        // Enable Visu for Liveview or Image
        $this->SetVisualizationType(($liveview || $image) ? 1 : 0);
    }

    /**
     * Is called when, for example, a button is clicked in the visualization.
     *
     * @param string $ident Ident of the variable
     * @param mixed $value The value to be set
     * @return void
     */
    public function RequestAction(string $ident, mixed $value): void
    {
        // Debug output
        $this->LogDebug(__FUNCTION__, $ident . ' => ' . $value);
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
            case 'config':
                $this->Configuration();
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
     * This function is called by IP-Symcon and processes sent data and, if necessary, forwards it to
     * all child instances. Data can be sent using the SendDataToChildren function.
     *
     * @param string $json Data package in JSON format
     *
     * @return string Optional response to the parent instance
     */
    public function ReceiveData(string $json): string
    {
        $this->LogDebug(__FUNCTION__, $json);
        $data = json_decode($json, true);
        if (isset($data['AuthData'])) {
            $this->WriteAttributeString('AuthData', serialize($data['AuthData']));
        }
        if (isset($data['Battery'])) {
            $device = $this->ReadPropertyString('DeviceID');
            foreach ($data['Battery'] as $entry) {
                if ($entry['device'] == $device) {
                    $this->SetValueInteger('battery', $entry['battery']);
                }
            }
        }
        return '';
    }

    /**
     * If the HTML-SDK is to be used, this function must be overwritten in order to return the HTML content.
     *
     * @return string Initial display of a representation via HTML SDK
     */
    public function GetVisualizationTile(): string
    {
        // Add a script to set the values when loading, analogous to changes at runtime
        // Although the return from GetFullUpdateMessage is already JSON-encoded, json_encode is still executed a second time
        // This adds quotation marks to the string and any quotation marks within it are escaped correctly
        $initialHandling = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ');</script>';
        // Add static HTML from file
        $module = file_get_contents(__DIR__ . '/module.html');
        // Important: $initialHandling at the end, as the handleMessage function is only defined in the HTML
        return $module . $initialHandling;
    }

    /**
     * Generate a message that updates all elements in the HTML display.
     *
     * @return String JSON encoded message information
     */
    private function GetFullUpdateMessage()
    {
        // dataset variable
        $result = [
            'client' => array_merge(
                unserialize($this->ReadAttributeString('AuthData')),
                [
                    'device'  => $this->ReadPropertyString('DeviceID'),
                    'network' => $this->ReadPropertyString('NetworkID'),
                    'type'    => $this->ReadPropertyString('DeviceType'),
                    'model'   => $this->ReadPropertyString('DeviceModel')
                ]
            ),
            'server'    => $this->ReadPropertyString('LiveViewServer'),
            'motion'    => $this->GetValue('motion_detection'),
            'poster'    => $this->GetBase64Image(@$this->GetIDForIdent('thumbnail')),
            'live'      => $this->ReadPropertyBoolean('LiveViewEnable'),
            'snapshot'  => $this->ReadPropertyBoolean('ImageVariable'),
        ];
        $this->LogDebug(__FUNCTION__, $result);
        return json_encode($result);
    }

    /**
     * Weekly Schedule event
     *
     * @param integer $value Action value (ON=2, OFF=1)
     *
     * @return void
     */
    private function ScheduleSnapshot(int $value): void
    {
        $schedule = $this->ReadPropertyInteger('ImageSchedule');
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value . ',Schedule: ' . $schedule);
        if ($schedule == 0) {
            // nothing todo
            return;
        }
        // Is Activate OFF
        if ($value == self::BLINK_SCHEDULE_SNAPSHOT_OFF) {
            $this->LogDebug(__FUNCTION__, 'OFF: Deactivate schedule timer!');
            // Reset Timer
            $this->SetTimerInterval('TimerSnapshot', 0);
            return;
        }
        // Image schedule is aktiv?
        $image = $this->ReadPropertyBoolean('ImageVariable');
        $this->LogDebug(__FUNCTION__, 'ON: Image is:' . $image);
        if ($image == true) {
            $interval = $this->ReadPropertyInteger('ImageInterval');
            $this->LogDebug(__FUNCTION__, 'ON: Interval is:' . $interval);
            if ($interval > 0) {
                $this->LogDebug(__FUNCTION__, 'ON: Activate schedule timer:' . $interval);
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
     *
     * @return void
     */
    private function Thumbnail(): void
    {
        $command = $this->ReadAttributeString('CommandID');
        if ($command != '') {
            $this->LogDebug(__FUNCTION__, 'Command still active!');
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
        $this->LogDebug(__FUNCTION__, $response);
        if ($response === '[]') {
            $this->LogMessage('[' . IPS_GetName($this->InstanceID) . '] ' . 'No command info for thumbnail', KL_ERROR);
            return;
        }
        // Command
        $command = json_decode($response, true);
        $this->LogDebug(__FUNCTION__, $command);
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
     *
     * @return void
     */
    private function MotionDetection(bool $value): void
    {
        $device = $this->ReadPropertyString('DeviceID');
        $network = $this->ReadPropertyString('NetworkID');
        $type = $this->ReadPropertyString('DeviceType');
        // Parameter
        $param = ['NetworkID' => $network, 'DeviceID' => $device, 'DeviceType' => $type, 'Detection' => $value];
        // Request
        $response = $this->RequestDataFromParent('motion', $param);
        if ($response === '[]') {
            $this->LogDebug(__FUNCTION__, 'Error occurred for switching motion detection');
        } else {
            $this->SetValueBoolean('motion_detection', $value);
            $this->UpdateVisualizationValue(json_encode(['motion' => $value]));
        }
    }

    /**
     * Get Image from server
     *
     * @return void
     */
    private function Image(): void
    {
        $response = $this->RequestDataFromParent('homescreen');
        if ($response === '[]') {
            $this->LogDebug(__FUNCTION__, 'No Result for HomeScreen!');
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
            $this->LogDebug(__FUNCTION__, 'No Path for Thambnail!');
            return;
        }
        // get image
        $param = ['Path' => $path];
        // Request
        $response = hex2bin($this->RequestDataFromParent('image', $param));
        if ($response === '[]') {
            $this->LogDebug(__FUNCTION__, 'No Image for Path!');
            return;
        }
        // Media object
        $mediaID = @$this->GetIDForIdent('thumbnail');
        if (!IPS_MediaExists($mediaID)) {
            $this->LogDebug(__FUNCTION__, 'No Media for Content!');
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
        $this->UpdateVisualizationValue(json_encode(['poster' => $this->GetBase64Image($mediaID)]));
    }

    /**
     * Get device config data
     *
     * @return void
     */
    private function Configuration(): void
    {
        $network = $this->ReadPropertyString('NetworkID');
        $device = $this->ReadPropertyString('DeviceID');
        $type = $this->ReadPropertyString('DeviceType');
        // Parameter
        $param = ['NetworkID' => $network, 'DeviceID' => $device, 'DeviceType' => $type];
        // Request
        $response = $this->RequestDataFromParent('config', $param);
        $this->LogDebug(__FUNCTION__, $response);
        if ($response === '[]') {
            $this->LogDebug(__FUNCTION__, 'Error occurred for config');
        }
        else {
            // Echo message
            $this->EchoMessage($this->PrettyPrint(null, $response, true));
        }
    }

    /**
     * Get Live Video URL from server
     *
     * @return void
     */
    private function LiveView(): void
    {
        $network = $this->ReadPropertyString('NetworkID');
        $device = $this->ReadPropertyString('DeviceID');
        $type = $this->ReadPropertyString('DeviceType');
        // Parameter
        $param = ['NetworkID' => $network, 'DeviceID' => $device, 'DeviceType' => $type];
        // Request
        $response = $this->RequestDataFromParent('liveview', $param);
        $this->LogDebug(__FUNCTION__, $response);
        if ($response === '[]') {
            $this->LogDebug(__FUNCTION__, 'Error occurred for liveview');
        }
        else {
            // Echo message
            $this->EchoMessage($this->PrettyPrint(self::BLINK_MAP_LIVEVIEW, $response));
        }
    }

    /**
     * Start a live recording clip.
     *
     * @return void
     */
    private function Record(): void
    {
        $network = $this->ReadPropertyString('NetworkID');
        $device = $this->ReadPropertyString('DeviceID');
        $type = $this->ReadPropertyString('DeviceType');
        // Parameter
        $param = ['NetworkID' => $network, 'DeviceID' => $device, 'DeviceType' => $type];
        // Request
        $response = $this->RequestDataFromParent('record', $param);
        $this->LogDebug(__FUNCTION__, $response);
        if ($response === '[]') {
            $this->LogDebug(__FUNCTION__, 'Error occurred for liveview');
        }
        else {
            // Echo message
            $this->EchoMessage($this->PrettyPrint(null, $response));
        }
    }

    /**
     * Get Signal information
     *
     * @return void
     */
    private function Signals(): void
    {
        $response = $this->RequestDataFromParent('homescreen');
        if ($response === '[]') {
            $this->LogDebug(__FUNCTION__, 'No Result for HomeScreen!');
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
        $this->EchoMessage($this->PrettyPrint(self::BLINK_MAP_SIGNALS, json_encode($signals)));
    }

    /**
     * Command status polling
     *
     * @return void
     */
    private function Command(): void
    {
        // Stop Timer
        $this->SetTimerInterval('TimerCommand', 0);
        // Parameter
        $network = $this->ReadPropertyString('NetworkID');
        $command = $this->ReadAttributeString('CommandID');
        // Safty check
        if ($command == '') {
            $this->LogMessage('[' . IPS_GetName($this->InstanceID) . '] ' . ' Zombie Command!', KL_ERROR);
            $this->LogDebug(__FUNCTION__, 'Zombie Command!');
            return;
        }
        $param = ['NetworkID' => $network, 'CommandID' => $command];
        // Request
        $response = $this->RequestDataFromParent('command', $param);
        if ($response === '[]') {
            $this->LogDebug(__FUNCTION__, 'No Result for Command!');
            return;
        }
        $command = json_decode($response, true);
        if ($command['complete'] == true) {
            $this->LogDebug(__FUNCTION__, $command);
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
     * @param array<string,mixed>  $params   Optional parameters for the API request
     * @return string Result of the API call.
     */
    private function RequestDataFromParent(string $endpoint, array $params = []): string
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
     * @return void
     */
    private function CreateScheduleSnapshot(): void
    {
        $eid = $this->CreateWeeklySchedule($this->InstanceID, $this->Translate('Schedule snapshot'), self::BLINK_SCHEDULE_SNAPSHOT_IDENT, self::BLINK_SCHEDULE_SNAPSHOT_SWITCH, -1);
        if (IPS_EventExists($eid)) {
            $this->UpdateFormField('ImageSchedule', 'value', $eid);
        }
    }

    /**
     * Create a media variable to take over the snapshots.
     *
     * @param string   $ident      Ident.
     * @param string   $name       Name
     * @param string   $filename   Image file name.
     * @param string   $fileext    Image file extension.
     * @param bool     $cache      Use In-memory cache
     *
     * @return void
     */
    private function CreateMediaImage(string $ident, string $name, string $filename, string $fileext = 'jpg', bool $cache = true): void
    {
        $file = IPS_GetKernelDir() . 'media' . DIRECTORY_SEPARATOR . $filename . '.' . $fileext;  // Image-Datei

        $mediaID = @$this->GetIDForIdent($ident);
        if (!IPS_MediaExists($mediaID)) {
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
     *
     * @return array{0:int,1:int,2:int} [Red, Green, Blue] values
     */
    private function Int2RGB(int $num): array
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
     *
     * @return void
     */
    private function EchoMessage(string $caption): void
    {
        $this->UpdateFormField('EchoMessage', 'caption', $this->Translate($caption));
        $this->UpdateFormField('EchoPopup', 'visible', true);
    }
}