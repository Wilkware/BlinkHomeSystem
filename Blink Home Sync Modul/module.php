<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// Blink Home Sync Modul
class BlinkHomeSyncModule extends IPSModuleStrict
{
    // Helper Traits
    use DebugHelper;
    use EventHelper;
    use FormatHelper;
    use ProfileHelper;
    use VariableHelper;

    /**
     * @var int Max clip size
     */
    private const BLINK_SYNC_SIZE = 1000;

    /**
     * @var int Schedule recording constant OFF
     */
    private const BLINK_SCHEDULE_RECORDING_OFF = 1;

    /**
     * @var int Schedule recording constant ON
     */
    private const BLINK_SCHEDULE_RECORDING_ON = 2;

    /**
     * @var string Schedule recording constant IDENT
     */
    private const BLINK_SCHEDULE_RECORDING_IDENT = 'circuit_recording';

    /**
     * @var array<int,array{0:string,1:int,2:string}> Schedule recording constant ACTION (Switch)
     */
    private const BLINK_SCHEDULE_RECORDING_SWITCH = [
        self::BLINK_SCHEDULE_RECORDING_OFF => ['Inaktive', 0xFF0000, "IPS_RequestAction(\$_IPS['TARGET'], 'schedule_recording', \$_IPS['ACTION']);"],
        self::BLINK_SCHEDULE_RECORDING_ON  => ['Aktive', 0x00FF00, "IPS_RequestAction(\$_IPS['TARGET'], 'schedule_recording', \$_IPS['ACTION']);"],
    ];

    /**
     * @var array<int,array{0:string,1:string,2:int,3:?string}> Storage information map
     */
    private const BLINK_MAP_STORAGE = [
        ['local_storage_enabled', 'Local save', 5, null],
        ['usb_state', 'USB status', 3, null],
        ['usb_storage_used', 'USB used', 1, null],
        ['usb_storage_full', 'USB full', 0, null],
        ['storage_warning', 'Storage warning', 1, null],
        ['sm_backup_enabled', 'Backup enabeld', 5, null],
        ['last_backup_completed', 'Last backup', 4, null],
        ['usb_format_compatible', 'USB format compatible', 0, null],
        ['sm_backup_in_progress', 'Backup in progress', 0, null],
        ['last_backup_result', 'Last backup result', 3, null],
    ];

    /**
     * @var array<int,array{0:string,1:string,2:int,3:?string}> Ntework information map
     */
    private const BLINK_MAP_NETWORK = [
        ['name', 'Name', 3, null],
        ['description', 'Description', 3, null],
        ['network_key', 'Network key', 3, null],
        ['network origin', 'Network origin', 3, null],
        ['locale', 'Locale', 3, null],
        ['time_zone', 'Timezone', 3, null],
        ['dst', 'Daylight savings time', 0, null],
        ['ping_interval', 'Ping interval', 1, null],
        ['armed', 'Armed', 5, null],
        ['autoarm_geo_enable', 'Auto arm geo', 5, null],
        ['autoarm_time_enable', 'Auto arm time', 5, null],
        ['lv_mode', 'Locale save mode', 3, null],
        ['video_destination', 'Video destination', 3, null],
        ['storage_used', 'Storage used', 1, null],
        ['storage_total', 'Storage total', 1, null],
        ['video_count', 'Video count', 1, null],
        ['video_history_count', 'Video history count', 1, null],
        ['sm_backup_enabled', 'Sync module backup', 0, null],
        ['busy', 'Busy', 0, null],
        ['camera_error', 'Camera error', 0, null],
        ['sync_module_error', 'Sync module error', 0, null],
        ['account_id', 'Accound ID', 1, null],
        ['status', 'Status', 3, null],
        ['created_at', 'Created at', 4, null],
        ['updated_at', 'Updated at', 4, null],
    ];

    /**
     * @var array<int,array{0:string,1:string,2:int,3:?string}> Symc modul information map
     */
    private const BLINK_MAP_SYNCMODUL = [
        ['name', 'Name', 3, null],
        ['status', 'Status', 3, null],
        ['serial', 'Serial', 3, null],
        ['fw_version', 'Firmware', 3, null],
        ['type', 'Type', 3, null],
        ['subtype', 'Subtype', 3, null],
        ['wifi_strength', 'Wifi strength', 1, null],
        ['network_id', 'Network ID', 1, null],
        ['onboarded', 'Onboarded', 0, null],
        ['enable_temp_alerts', 'Enable temparature alerts', 0, null],
        ['local_storage_enabled', 'Local storage enabled', 0, null],
        ['local_storage_compatible', 'Local storage compatible', 0, null],
        ['local_storage_status', 'Local storage status', 3, null],
        ['last_hb', 'Last heartbeat at', 4, null],
        ['created_at', 'Created at', 4, null],
        ['updated_at', 'Updated at', 4, null],
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
        // Connect to client
        if ((float)IPS_GetKernelVersion() < 8.2) {
            $this->ConnectParent('{AF126D6D-83D1-44C2-6F61-96A4BB7A0E62}');
        }
        // Device
        $this->RegisterPropertyString('DeviceID', '');
        $this->RegisterPropertyString('NetworkID', '');
        $this->RegisterPropertyString('DeviceType', 'null');
        $this->RegisterPropertyString('DeviceModel', 'null');
        // Motion detection
        $this->RegisterPropertyBoolean('CheckRecording', false);
        $this->RegisterPropertyInteger('RecordingSchedule', 0);
        $this->RegisterPropertyInteger('UpdateInterval', 0);
        // Recording
        $this->RegisterPropertyInteger('StorageCategory', 0);
        $this->RegisterPropertyInteger('StorageLimit', 10);
        $this->RegisterPropertyBoolean('OnlyCache', true);
        $this->RegisterPropertyInteger('DownloadMode', 0);
        // Alerts
        $this->RegisterPropertyBoolean('CheckAlert', false);
        $this->RegisterPropertyBoolean('CheckMotion', false);
        $this->RegisterPropertyString('ListMotion', '[{"Value": 10,"Name": "Blink 1"},{"Value": 20,"Name": "Blink 2"},{"Value": 30,"Name": "Blink 3"},{"Value": 40,"Name": "Blink 4"},{"Value": 50,"Name": "Blink 5"},{"Value": 60,"Name": "Blink 6"},{"Value": 70,"Name": "Blink 7"},{"Value": 80,"Name": "Blink 8"},{"Value": 90,"Name": "Blink 9"},{"Value": 100,"Name": "Blink 10"}]');
        $this->RegisterPropertyInteger('AlertScript', 0);
        // Update Timer
        $this->RegisterTimer('TimerSyncUpdate', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "network", false);');
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
     * Is executed when "Apply" is pressed on the configuration page and immediately after the instance has been created.
     *
     * @return void
     */
    public function ApplyChanges(): void
    {
        //Never delete this line!
        parent::ApplyChanges();

        //Delete all references in order to readd them
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }
        $script = $this->ReadPropertyInteger('AlertScript');
        if (IPS_ScriptExists($script)) {
            $this->RegisterReference($script);
        }

        // Register Profile
        $profile = [
            [1, '►', '', 0xFF8000],
        ];
        $this->RegisterProfileInteger('BHS.Download', 'Download', '', '', 0, 0, 0, $profile);

        // Recording variable
        $recording = $this->ReadPropertyBoolean('CheckRecording');
        $this->MaintainVariable('recording', $this->Translate('Recording'), VARIABLETYPE_BOOLEAN, '~Switch', 0, $recording);
        if ($recording) {
            $this->SetValueBoolean('recording', false);
            $this->EnableAction('recording');
        }

        // Download variable
        $store = $this->ReadPropertyInteger('StorageCategory');
        $limit = $this->ReadPropertyInteger('StorageLimit');
        $download = (IPS_CategoryExists($store) && ($limit != 0));
        $this->MaintainVariable('download', $this->Translate('Download'), VARIABLETYPE_INTEGER, 'BHS.Download', 2, $download);
        if ($download) {
            $this->SetValueInteger('download', 3);
            $this->EnableAction('download');
        }

        // Alert variable
        $alert = $this->ReadPropertyBoolean('CheckAlert');
        $this->MaintainVariable('alert', $this->Translate('Alert'), VARIABLETYPE_BOOLEAN, '~Alert', 1, $alert);
        if ($alert) {
            $this->EnableAction('alert');
        }
        $motion = $this->ReadPropertyBoolean('CheckMotion');
        if ($motion) {
            $list = json_decode($this->ReadPropertyString('ListMotion'), true);
            $asso = [];
            foreach ($list as $entry) {
                $asso[] = [$entry['Value'], $entry['Name'], '', -1];
            }
            $this->RegisterProfileInteger('BHS.Cameras', 'Motion', '', '', 1, 100, 1, $asso);
        }
        $this->MaintainVariable('last_motion', $this->Translate('Last movement'), VARIABLETYPE_INTEGER, 'BHS.Cameras', 3, $motion);
        if ($motion) {
            $this->EnableAction('last_motion');
        }

        // Sync Timer
        $syncUpdate = $this->ReadPropertyInteger('UpdateInterval');
        $this->SetTimerInterval('TimerSyncUpdate', 60 * 1000 * $syncUpdate);
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
            case 'create_schedule':
                $this->CreateScheduleRecording();
                break;
            case 'schedule_recording':
                $this->ScheduleRecording($value);
                break;
            case 'recording':
                if ($value) {
                    $this->Arm();
                } else {
                    $this->Disarm();
                }
                break;
            case 'network':
                $this->Network($value);
                break;
            case 'sync_module':
                $this->SyncModule($value);
                break;
            case 'local_storage_status':
                $this->LocalStorageState($value);
                break;
            case 'download':
                $value = $this->ReadPropertyInteger('DownloadMode');
                // Execute 'get_videos' via Button from Webfront!
                // No break. Add additional comment above this line if intentional!
            case 'get_videos':
                $this->Download($value);
                break;
            case 'get_video_clips':
                $this->Clips($value);
                break;
            case 'get_video_events':
                $this->Events($value);
                break;
            case 'alert':
                $this->Alert($value);
                break;
            case 'last_motion':
                $this->LastMotion($value);
                break;
            default:
                break;
        }
        return;
    }

    /**
     * Arm the given network - that is, start recording/reporting motion events for enabled cameras.
     *
     * BHS_Arm();
     *
     * @return bool True if successful, otherwise False.
     */
    public function Arm(): bool
    {
        // Params
        $param = ['NetworkID' => $this->ReadPropertyString('NetworkID')];
        // Request
        $response = $this->RequestDataFromParent('arm', $param);
        // Debug
        $this->LogDebug(__FUNCTION__, $response);
        // Result?
        if ($response !== '[]') {
            $this->SetValueBoolean('recording', true);
            //echo $this->Translate('Call was successfull!');
            return true;
        } else {
            echo $this->Translate('Call was not successfull!');
            return false;
        }
    }

    /**
     * Disarm the given network - that is, stop recording/reporting motion events for enabled cameras.
     *
     * BHS_Disarm();
     *
     * @return bool True if successful, otherwise False.
     */
    public function Disarm(): bool
    {
        // Params
        $param = ['NetworkID' => $this->ReadPropertyString('NetworkID')];
        // Request
        $response = $this->RequestDataFromParent('disarm', $param);
        // Debug
        $this->LogDebug(__FUNCTION__, $response);
        // Result?
        if ($response !== '[]') {
            $this->SetValueBoolean('recording', false);
            //echo $this->Translate('Call was successfull!');
            return true;
        } else {
            echo $this->Translate('Call was not successfull!');
            return false;
        }
    }

    /**
     * Alarm was triggered.
     *
     * @param bool $value Switch alert
     *
     * @return void
     */
    private function Alert(bool $value): void
    {
        $this->SetValueBoolean('alert', $value);
        // Debug
        $this->LogDebug(__FUNCTION__, 'Alert ' . boolval($value));
        // Execute script
        $script = $this->ReadPropertyInteger('AlertScript');
        if ($value && $script != 0) {
            if (IPS_ScriptExists($script)) {
                $rs = IPS_RunScriptEx($script, ['MODUL' => $this->InstanceID, 'ALERT' => $value, 'TIMESTAMP' => time()]);
                $this->LogDebug(__FUNCTION__, 'Script #' . $script . ' executed! Return Value: ' . $rs);
            } else {
                $this->LogDebug(__FUNCTION__, 'Script #' . $script . ' does not exist!');
            }
        }
        // $this->LogMessage(date('D, d.m.Y H:i:s'), KL_NOTIFY);
    }

    /**
     * Alarm was triggered.
     *
     * @param int $value Dim value for mapping
     *
     * @return void
     */
    private function LastMotion(int $value): void
    {
        $this->SetValueInteger('last_motion', $value);
        // Debug
        $this->LogDebug(__FUNCTION__, 'Last Motion: ' . $value);
        // Execute script
        $script = $this->ReadPropertyInteger('AlertScript');
        if ($value && $script != 0) {
            if (IPS_ScriptExists($script)) {
                $id = @$this->GetIDForIdent('last_motion');
                $ca = GetValueFormatted($id);
                $rs = IPS_RunScriptEx($script, ['MODUL' => $this->InstanceID, 'MOTION' => $ca, 'TIMESTAMP' => time()]);
                $this->LogDebug(__FUNCTION__, 'Script #' . $script . ' executed! Return Value: ' . $rs);
            } else {
                $this->LogDebug(__FUNCTION__, 'Script #' . $script . ' does not exist!');
            }
        }
    }

    /**
     * Download (all) local stored media clips.
     *
     * @param int $mode Subsequense mode for download videos (cloud, local, both)
     *
     * @return void
     */
    private function Download(int $mode): void
    {
        switch ($mode) {
            case 0:
                $this->Events(false);
                break;
            case 1:
                $this->Clips(false);
                break;
            case 2:
                $this->Events(false);
                $this->Clips(false);
                break;
        }
    }

    /**
     * Download the latest video clip recordings (LOCAL STORAGE).
     *
     * @param bool $value Debug switch
     *
     * @return void
     */
    private function Clips(bool $value): void
    {
        $store = $this->ReadPropertyInteger('StorageCategory');
        $limit = $this->ReadPropertyInteger('StorageLimit');
        $cache = $this->ReadPropertyBoolean('OnlyCache');
        $media = [];
        if (IPS_CategoryExists($store)) {
            $this->LogDebug(__FUNCTION__, 'Download max ' . $limit . ' clips to: ' . IPS_GetName($store));
        } else {
            $this->LogDebug(__FUNCTION__, 'No category set to store!');
            return;
        }
        // Params
        $param = [
            'NetworkID' => $this->ReadPropertyString('NetworkID'),
            'DeviceID'  => $this->ReadPropertyString('DeviceID'),
        ];
        // Request
        $response = $this->RequestDataFromParent('manifest', $param);
        // Debug
        $this->LogDebug(__FUNCTION__, $response);
        // Result?
        if ($response !== '[]') {
            $data = json_decode($response, true);
            if (isset($data['manifest_id'])) {
                // newest clips at first
                $clips = $this->OrderData($data['clips'], 'created_at', 'DSC');
                // Limit result set - $clips = array_slice($clips, 0, $limit);
                $filtered = [];
                foreach ($clips as $clip) {
                    if (isset($clip['size']) && $clip['size'] < self::BLINK_SYNC_SIZE) {
                        $filtered[] = $clip;
                        if (count($filtered) >= $limit) {
                            break;
                        }
                    }
                }
                $clips = $filtered;
                // Exists and how much medias
                $medias = $this->ReadMediaWithAttributes($store, $limit, $clips);
                $param['ManifestID'] = $data['manifest_id'];
                foreach ($clips as $clip) {
                    if (array_search($clip['id'], $medias) != 0) continue; // still saved
                    $param['ClipID'] = $clip['id'];
                    // Request
                    do {
                        $response = hex2bin($this->RequestDataFromParent('clip', $param));
                        // check for {"message":"Media not found","code":700}
                        $length = strlen($response);
                        $error = ($length === 40);
                        $this->LogDebug(__FUNCTION__, 'Length-Check: ' . $length . ' => ' . boolval($error));
                        if ($error) {
                            $this->LogMessage($response);
                            IPS_Sleep(1000);
                        }
                    } while ($error);
                    $this->SaveMediaWithAttributes($store, $limit, $cache, $medias, $clip, $response);
                    IPS_Sleep(1000);
                }
            }
            else {
                $this->LogDebug(__FUNCTION__, 'No monifest id in response!');
                return;
            }
        } else {
            if ($value) {
                echo $this->Translate('Call was not successfull!');
            } else {
                $this->LogDebug(__FUNCTION__, 'No Clip Information!');
            }
            return;
        }
    }

    /**
     * Download the latest video event recordings (CLOUD).
     *
     * @param bool $value Debug switch
     *
     * @return void
     */
    private function Events(bool $value): void
    {
        $store = $this->ReadPropertyInteger('StorageCategory');
        $limit = $this->ReadPropertyInteger('StorageLimit');
        $cache = $this->ReadPropertyBoolean('OnlyCache');
        $media = [];
        if (IPS_CategoryExists($store)) {
            $this->LogDebug(__FUNCTION__, 'Download max ' . $limit . ' clips to: ' . IPS_GetName($store));
        } else {
            $this->LogDebug(__FUNCTION__, 'No category set to store!');
            return;
        }
        // Params
        $param = ['Timestamp' => 0, 'Page' => 1];
        // Request
        $response = $this->RequestDataFromParent('events', $param);
        // Debug
        $this->LogDebug(__FUNCTION__, $response);
        // Result?
        if ($response !== '[]') {
            $data = json_decode($response, true);
            if (isset($data['media'])) {
                // newest clips at first
                $videos = $this->OrderData($data['media'], 'created_at', 'DSC');
                // Limit result set
                $videos = array_slice($videos, 0, $limit); // echo count($videos) . PHP_EOL;
                $this->LogDebug(__FUNCTION__, $videos);
                // Exists and how much medias
                $medias = $this->ReadMediaWithAttributes($store, $limit, $videos);
                foreach ($videos as $video) {
                    if (array_search($video['id'], $medias) != 0) continue; // still saved
                    $param['MediaID'] = $video['media'];
                    // Request
                    //$response = utf8_decode($this->RequestDataFromParent('video', $param));
                    $response = hex2bin($this->RequestDataFromParent('video', $param));
                    $this->SaveMediaWithAttributes($store, $limit, $cache, $medias, $video, $response);
                    IPS_Sleep(1000);
                }
            }
            else {
                $this->LogDebug(__FUNCTION__, 'No medias in response!');
                return;
            }
        } else {
            if ($value) {
                echo $this->Translate('Call was not successfull!');
            } else {
                $this->LogDebug(__FUNCTION__, 'No Event Information!');
            }
            return;
        }
    }

    /**
     * Display Network Information
     *
     * @param bool $value Debug switch
     *
     * @return void
     */
    private function Network(bool $value): void
    {
        // Debug
        $this->LogDebug(__FUNCTION__, 'Obtain network information.');
        // Params
        $param = ['NetworkID' => $this->ReadPropertyString('NetworkID')];
        // Request data
        $response = $this->RequestDataFromParent('network', $param);
        $this->LogDebug(__FUNCTION__, $response);
        // Result?
        if ($response !== '[]') {
            $params = json_decode($response, true);
            $this->LogDebug(__FUNCTION__, $params);
            if (isset($params['network'])) {
                // Print
                if ($value) {
                    // Echo message
                    $this->EchoMessage($this->PrettyPrint(self::BLINK_MAP_NETWORK, json_encode($params['network'])));
                } else {
                    $armed = $this->GetValue('recording');
                    // only if different
                    if ($params['network']['armed'] != $armed) {
                        $this->SetValueBoolean('recording', !$armed);
                    }
                }
            }
        } else {
            if ($value) {
                echo $this->Translate('Call was not successfull!');
            } else {
                $this->LogDebug(__FUNCTION__, 'No Network Information!');
            }
        }
    }

    /**
     * Display Sync Module Information
     *
     * @param bool $value Debug switch
     *
     * @return void
     */
    private function SyncModule(bool $value): void
    {
        // Debug
        $this->LogDebug(__FUNCTION__, 'Obtain sync module information.');
        // Sync Module Device
        $device = $this->ReadPropertyString('DeviceID');
        // Request data
        $response = $this->RequestDataFromParent('homescreen');
        // Result?
        if ($response !== '[]') {
            $params = json_decode($response, true);
            $this->LogDebug(__FUNCTION__, $params);
            if (isset($params['sync_modules'])) {
                foreach ($params['sync_modules'] as $param) {
                    if ($param['id'] == $device) {
                        // Echo message
                        $this->EchoMessage($this->PrettyPrint(self::BLINK_MAP_SYNCMODUL, json_encode($param)));
                        break;
                    }
                }
            }
        } else {
            if ($value) {
                echo $this->Translate('Call was not successfull!');
            } else {
                $this->LogDebug(__FUNCTION__, 'No Sync Module Information!');
            }
        }
    }

    /**
     * Display Local Storage Status
     *
     * @param bool $value Debug switch
     *
     * @return void
     */
    private function LocalStorageState(bool $value): void
    {
        // Debug
        $this->LogDebug(__FUNCTION__, 'Obtain local storage status.');
        // Params
        $param = ['NetworkID' => $this->ReadPropertyString('NetworkID'), 'DeviceID' => $this->ReadPropertyString('DeviceID')];
        // Request data
        $response = $this->RequestDataFromParent('local_storage_status', $param);
        $this->LogDebug(__FUNCTION__, $response);
        if ($response !== '[]') {
            // Echo message
            $this->EchoMessage($this->PrettyPrint(self::BLINK_MAP_STORAGE, $response));
        } else {
            if ($value) {
                echo $this->Translate('Call was not successfull!');
            } else {
                $this->LogDebug(__FUNCTION__, 'No Local Storage Status Information!');
            }
        }
    }

    /**
     * Weekly Schedule event
     *
     * @param integer $value Action value (ON=2, OFF=1)
     *
     * @return void
     */
    private function ScheduleRecording(int $value): void
    {
        $schedule = $this->ReadPropertyInteger('RecordingSchedule');
        $this->LogDebug(__FUNCTION__, 'Value: ' . $value . ',Schedule: ' . $schedule);
        if ($schedule == 0) {
            $this->LogDebug(__FUNCTION__, 'Schedule not linked!');
            // nothing todo
            return;
        }
        // Is Activate OFF
        if ($value == self::BLINK_SCHEDULE_RECORDING_OFF) {
            $this->LogDebug(__FUNCTION__, 'OFF: Disarm recording!');
            // Stop Recording
            $this->Disarm();
        }
        if ($value == self::BLINK_SCHEDULE_RECORDING_ON) {
            $this->LogDebug(__FUNCTION__, 'ON: Arm recording!');
            // Start Recording
            $this->Arm();
        }
    }

    /**
     * Create week schedule for snapshots
     *
     * @return void
     */
    private function CreateScheduleRecording(): void
    {
        $eid = $this->CreateWeeklySchedule($this->InstanceID, $this->Translate('Schedule recording'), self::BLINK_SCHEDULE_RECORDING_IDENT, self::BLINK_SCHEDULE_RECORDING_SWITCH, -1);
        if (IPS_EventExists($eid)) {
            $this->UpdateFormField('RecordingSchedule', 'value', $eid);
        }
    }

    /**
     * Read all media attributes from the given category
     *
     * @param int   $store  Store location ID
     * @param int   $limit  Store media limit (default:10)
     * @param array<int,array{id:int}>  $source List of media entries with at least an 'id'
     *
     * @return array<int,int> Collection of found media IDs mapped by object ID
     */
    private function ReadMediaWithAttributes(int $store, int $limit, array $source): array
    {
        $childs = IPS_GetChildrenIDs($store);
        $medias = [];
        $number = 0;
        // read exisitng maedia
        foreach ($childs as $child) {
            $object = IPS_GetObject($child);
            if ($object['ObjectType'] == 5) { // only medias
                $info = json_decode($object['ObjectInfo'], true);
                if ($info !== false) { // JSON is valid
                    $medias[$child] = 0;
                    foreach ($source as $entry) {
                        if ($info['id'] == $entry['id']) {
                            $medias[$child] = $info['id'];
                            break;
                        }
                    }
                }
                $number++;
                if ($number == $limit)break;
            }
        }
        return $medias;
    }

    /**
     * Save media with attributes
     *
     * @param int        $store    Store location ID
     * @param int        $limit    Store media limit
     * @param bool       $cache    Whether to cache the media file
     * @param array<int> $medias   Reference to array of media IDs mapped to their source IDs
     * @param array{id:int,created_at:string,device_name?:string,camera_name?:string} $source  Media source data
     * @param string     $response Raw media content
     *
     * @return bool TRUE if successful, otherwise FALSE
     */
    private function SaveMediaWithAttributes(int $store, int $limit, bool $cache, array &$medias, array $source, string $response): bool
    {
        $ident = 'media_' . $source['id'];
        $name = date('YmdHis_', strtotime($source['created_at']));
        if (isset($source['camera_name'])) {
            $name .= $source['camera_name'];
        } else {
            $name .= $source['device_name'];
        }

        foreach ($medias as $mediaID => $sourceID) {
            if ($sourceID === 0) {
                IPS_SetMediaContent($mediaID, base64_encode($response));
                IPS_SendMediaEvent($mediaID);
                IPS_SetIdent($mediaID, $ident);
                IPS_SetName($mediaID, $name);
                IPS_SetInfo($mediaID, json_encode($source));
                $medias[$mediaID] = $source['id'];
                return true;
            }
        }
        if (count($medias) != $limit) {
            $mediaID = IPS_CreateMedia(1);
            IPS_SetParent($mediaID, $store);
            IPS_SetIdent($mediaID, $ident);
            IPS_SetMediaCached($mediaID, true);
            if (!$cache) {
                IPS_SetMediaFile($mediaID, 'media' . DIRECTORY_SEPARATOR . $mediaID . '.mp4', false);  // Video-Datei
            }
            IPS_SetMediaContent($mediaID, base64_encode($response));
            IPS_SendMediaEvent($mediaID);
            IPS_SetIdent($mediaID, $ident);
            IPS_SetName($mediaID, $name);
            IPS_SetInfo($mediaID, json_encode($source));
            $medias[$mediaID] = $source['id'];
            return true;
        }
        return false;
    }

    /**
     * Sort an associative array by one or multiple keys.
     *
     * @param list<mixed>  $arr        The array to sort (list of associative arrays)
     * @param string       $key        Key to sort by or an array of key => direction
     * @param string       $direction  Sorting direction ('ASC' or 'DESC') when a single key is used
     *
     * @return list<mixed> Sorted array
     */
    private function OrderData(array $arr, string $key, string $direction = 'ASC'): array
    {
        // Build order-by clausel
        $props = [];
        $props[$key] = strtolower($direction) == 'asc' ? 1 : -1;
        // Sort by passed keys
        usort($arr, function ($a, $b) use ($props)
        {
            foreach ($props as $key => $val) {
                if ($a[$key] === $b[$key]) continue;
                return $a[$key] > $b[$key] ? $val : -($val);
            }
            return 0;
        });
        // Return sorted array
        return $arr;
    }

    /**
     * Returns the ascending list of category names for a given category id
     *
     * @param string $endpoint API endpoint request.
     * @param array<string,mixed>  $params   Optional parameters for the API request
     *
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