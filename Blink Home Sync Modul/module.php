<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// Blink Home Sync Modul
class BlinkHomeSyncModule extends IPSModule
{
    // Helper Traits
    use DebugHelper;
    use EventHelper;
    use FormatHelper;
    use ProfileHelper;
    use VariableHelper;

    // Sync state constant
    private const BLINK_SYNC_TIME = 60;
    private const BLINK_SYNC_TRIALS = 3;
    // Schedule constant
    private const BLINK_SCHEDULE_RECORDING_OFF = 1;
    private const BLINK_SCHEDULE_RECORDING_ON = 2;
    private const BLINK_SCHEDULE_RECORDING_IDENT = 'circuit_recording';
    private const BLINK_SCHEDULE_RECORDING_SWITCH = [
        self::BLINK_SCHEDULE_RECORDING_OFF => ['Inaktive', 0xFF0000, "IPS_RequestAction(\$_IPS['TARGET'], 'schedule_recording', \$_IPS['ACTION']);"],
        self::BLINK_SCHEDULE_RECORDING_ON  => ['Aktive', 0x00FF00, "IPS_RequestAction(\$_IPS['TARGET'], 'schedule_recording', \$_IPS['ACTION']);"],
    ];

    // Echo maps
    private const BLINK_MAP_STORAGE = [
        ['local_storage_enabled', 'Local save', 5],
        ['usb_state', 'USB status', 3],
        ['usb_storage_used', 'USB used', 1],
        ['usb_storage_full', 'USB full', 0],
        ['storage_warning', 'Storage warning', 1],
        ['sm_backup_enabled', 'Backup enabeld', 5],
        ['last_backup_completed', 'Last backup', 4],
        ['usb_format_compatible', 'USB format compatible', 0],
        ['sm_backup_in_progress', 'Backup in progress', 0],
        ['last_backup_result', 'Last backup result', 3],
    ];
    private const BLINK_MAP_NETWORK = [
        ['name', 'Name', 3],
        ['description', 'Description', 3],
        ['network_key', 'Network key', 3],
        ['network origin', 'Network origin', 3],
        ['locale', 'Locale', 3],
        ['time_zone', 'Timezone', 3],
        ['dst', 'Daylight savings time', 0],
        ['ping_interval', 'Ping interval', 1],
        ['armed', 'Armed', 5],
        ['autoarm_geo_enable', 'Auto arm geo', 5],
        ['autoarm_time_enable', 'Auto arm time', 5],
        ['lv_mode', 'Locale save mode', 3],
        ['video_destination', 'Video destination', 3],
        ['storage_used', 'Storage used', 1],
        ['storage_total', 'Storage total', 1],
        ['video_count', 'Video count', 1],
        ['video_history_count', 'Video history count', 1],
        ['sm_backup_enabled', 'Sync module backup', 0],
        ['busy', 'Busy', 0],
        ['camera_error', 'Camera error', 0],
        ['sync_module_error', 'Sync module error', 0],
        ['account_id', 'Accound ID', 1],
        ['status', 'Status', 3],
        ['created_at', 'Created at', 4],
        ['updated_at', 'Updated at', 4],
    ];

    private const BLINK_MAP_SYNCMODUL = [
        ['name', 'Name', 3],
        ['status', 'Status', 3],
        ['serial', 'Serial', 3],
        ['fw_version', 'Firmware', 3],
        ['type', 'Type', 3],
        ['subtype', 'Subtype', 3],
        ['wifi_strength', 'Wifi strength', 1],
        ['network_id', 'Network ID', 1],
        ['onboarded', 'Onboarded', 0],
        ['enable_temp_alerts', 'Enable temparature alerts', 0],
        ['local_storage_enabled', 'Local storage enabled', 0],
        ['local_storage_compatible', 'Local storage compatible', 0],
        ['local_storage_status', 'Local storage status', 3],
        ['last_hb', 'Last heartbeat at', 4],
        ['created_at', 'Created at', 4],
        ['updated_at', 'Updated at', 4],
    ];

    /**
     * Overrides the internal IPS_Create($id) function
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        // Connect to client
        $this->ConnectParent('{AF126D6D-83D1-44C2-6F61-96A4BB7A0E62}');
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
        $this->RegisterPropertyInteger('AlertScript', 0);
        // Update Timer
        $this->RegisterTimer('TimerSyncUpdate', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "network", false);');
    }

    /**
     * Overrides the internal IPS_Destroy($id) function
     */
    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    /**
     * Overrides the internal IPS_ApplyChanges($id) function
     */
    public function ApplyChanges()
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
            //$this->SetValueBoolean('recording', false);
            $this->EnableAction('recording');
        }

        // Download variable
        $store = $this->ReadPropertyInteger('StorageCategory');
        $limit = $this->ReadPropertyInteger('StorageLimit');
        $download = (IPS_CategoryExists($store)) & ($limit != 0);
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

        // Sync Timer
        $syncUpdate = $this->ReadPropertyInteger('UpdateInterval');
        $this->SetTimerInterval('TimerSyncUpdate', 60 * 1000 * $syncUpdate);
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
            default:
                break;
        }
        return;
    }

    /**
     * Arm the given network - that is, start recording/reporting motion events for enabled cameras.
     *
     * BHS_Arm();
     */
    public function Arm()
    {
        // Params
        $param = ['NetworkID' => $this->ReadPropertyString('NetworkID')];
        // Request
        $response = $this->RequestDataFromParent('arm', $param);
        // Debug
        $this->SendDebug(__FUNCTION__, $response);
        // Result?
        if ($response !== false) {
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
     */
    public function Disarm()
    {
        // Params
        $param = ['NetworkID' => $this->ReadPropertyString('NetworkID')];
        // Request
        $response = $this->RequestDataFromParent('disarm', $param);
        // Debug
        $this->SendDebug(__FUNCTION__, $response);
        // Result?
        if ($response !== false) {
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
     */
    private function Alert(bool $value)
    {
        $this->SetValueBoolean('alert', $value);
        // Debug
        $this->SendDebug(__FUNCTION__, 'Alert ' . boolval($value), 0);
        // Execute script
        $script = $this->ReadPropertyInteger('AlertScript');
        if ($value && $script != 0) {
            if (IPS_ScriptExists($script)) {
                $rs = IPS_RunScriptEx($script, ['MODUL' => $this->InstanceID, 'ALERT' => $value, 'TIMESTAMP' => time()]);
                $this->SendDebug(__FUNCTION__, 'Script #' . $script . ' executed! Return Value: ' . $rs);
            } else {
                $this->SendDebug(__FUNCTION__, 'Script #' . $script . ' does not exist!');
            }
        }
        // $this->LogMessage(date('D, d.m.Y H:i:s'), KL_NOTIFY);
    }

    /**
     * Download (all) local stored media clips.
     *
     * @param int $mode Subsequense mode for download videos (cloud, local, both)
     */
    private function Download(int $mode)
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
     */
    private function Clips(bool $value)
    {
        $store = $this->ReadPropertyInteger('StorageCategory');
        $limit = $this->ReadPropertyInteger('StorageLimit');
        $cache = $this->ReadPropertyBoolean('OnlyCache');
        $media = [];
        if (IPS_CategoryExists($store)) {
            $this->SendDebug(__FUNCTION__, 'Download max ' . $limit . ' clips to: ' . IPS_GetName($store), 0);
        } else {
            $this->SendDebug(__FUNCTION__, 'No category set to store!', 0);
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
        $this->SendDebug(__FUNCTION__, $response);
        // Result?
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['manifest_id'])) {
                // newest clips at first
                $clips = $this->OrderData($data['clips'], 'created_at', 'DSC');
                // Limit result set
                $clips = array_slice($clips, 0, $limit); // echo count($clips) . PHP_EOL;
                // Exists and how much medias
                $medias = $this->ReadMediaWithAttributes($store, $limit, $clips);
                $param['ManifestID'] = $data['manifest_id'];
                foreach ($clips as $clip) {
                    if (array_search($clip['id'], $medias) != 0) continue; // still saved
                    $param['ClipID'] = $clip['id'];
                    // Request
                    $response = utf8_decode($this->RequestDataFromParent('clip', $param));
                    $this->SaveMediaWithAttributes($store, $limit, $cache, $medias, $clip, $response);
                    IPS_Sleep(1000);
                }
            }
            else {
                $this->SendDebug(__FUNCTION__, 'No monifest id in response!', 0);
                return false;
            }
        } else {
            if ($value) {
                echo $this->Translate('Call was not successfull!');
            } else {
                $this->SendDebug(__FUNCTION__, 'No Clip Information!');
            }
            return false;
        }
    }

    /**
     * Download the latest video event recordings (CLOUD).
     *
     * @param bool $value Debug switch
     */
    private function Events(bool $value)
    {
        $store = $this->ReadPropertyInteger('StorageCategory');
        $limit = $this->ReadPropertyInteger('StorageLimit');
        $cache = $this->ReadPropertyBoolean('OnlyCache');
        $media = [];
        if (IPS_CategoryExists($store)) {
            $this->SendDebug(__FUNCTION__, 'Download max ' . $limit . ' clips to: ' . IPS_GetName($store), 0);
        } else {
            $this->SendDebug(__FUNCTION__, 'No category set to store!', 0);
            return;
        }
        // Params
        $param = ['Timestamp' => 0, 'Page' => 1];
        // Request
        $response = $this->RequestDataFromParent('events', $param);
        // Debug
        $this->SendDebug(__FUNCTION__, $response);
        // Result?
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['media'])) {
                // newest clips at first
                $videos = $this->OrderData($data['media'], 'created_at', 'DSC');
                // Limit result set
                $videos = array_slice($videos, 0, $limit); // echo count($videos) . PHP_EOL;
                $this->SendDebug(__FUNCTION__, $videos);
                // Exists and how much medias
                $medias = $this->ReadMediaWithAttributes($store, $limit, $videos);
                foreach ($videos as $video) {
                    if (array_search($video['id'], $medias) != 0) continue; // still saved
                    $param['MediaID'] = $video['media'];
                    // Request
                    $response = utf8_decode($this->RequestDataFromParent('video', $param));
                    $this->SaveMediaWithAttributes($store, $limit, $cache, $medias, $video, $response);
                    IPS_Sleep(1000);
                }
            }
            else {
                $this->SendDebug(__FUNCTION__, 'No medias in response!', 0);
                return false;
            }
        } else {
            if ($value) {
                echo $this->Translate('Call was not successfull!');
            } else {
                $this->SendDebug(__FUNCTION__, 'No Event Information!');
            }
            return false;
        }
    }

    /**
     * Display Network Information
     *
     * @param bool $value Debug switch
     */
    private function Network(bool $value)
    {
        // Debug
        $this->SendDebug(__FUNCTION__, 'Obtain network information.');
        // Params
        $param = ['NetworkID' => $this->ReadPropertyString('NetworkID')];
        // Request data
        $response = $this->RequestDataFromParent('network', $param);
        $this->SendDebug(__FUNCTION__, $response);
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->SendDebug(__FUNCTION__, $params);
            if (isset($params['network'])) {
                // Print
                if ($value) {
                    echo $this->PrettyPrint(self::BLINK_MAP_NETWORK, $params['network']);
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
                $this->SendDebug(__FUNCTION__, 'No Network Information!');
            }
        }
    }

    /**
     * Display Sync Module Information
     *
     * @param bool $value Debug switch
     */
    private function SyncModule(bool $value)
    {
        // Debug
        $this->SendDebug(__FUNCTION__, 'Obtain sync module information.');
        // Sync Module Device
        $device = $this->ReadPropertyString('DeviceID');
        // Request data
        $response = $this->RequestDataFromParent('homescreen');
        // Result?
        if ($response !== false) {
            $params = json_decode($response, true);
            $this->SendDebug(__FUNCTION__, $params);
            if (isset($params['sync_modules'])) {
                foreach ($params['sync_modules'] as $param) {
                    if ($param['id'] == $device) {
                        echo $this->PrettyPrint(self::BLINK_MAP_SYNCMODUL, $param);
                        // Prepeare Info
                        break;
                    }
                }
            }
        } else {
            if ($value) {
                echo $this->Translate('Call was not successfull!');
            } else {
                $this->SendDebug(__FUNCTION__, 'No Sync Module Information!');
            }
        }
    }

    /**
     * Display Local Storage Status
     *
     * @param bool $value Debug switch
     */
    private function LocalStorageState(bool $value)
    {
        // Debug
        $this->SendDebug(__FUNCTION__, 'Obtain local storage status.');
        // Params
        $param = ['NetworkID' => $this->ReadPropertyString('NetworkID'), 'DeviceID' => $this->ReadPropertyString('DeviceID')];
        // Request data
        $response = $this->RequestDataFromParent('local_storage_status', $param);
        $this->SendDebug(__FUNCTION__, $response);
        if ($response !== false) {
            echo $this->PrettyPrint(self::BLINK_MAP_STORAGE, $response);
        } else {
            if ($value) {
                echo $this->Translate('Call was not successfull!');
            } else {
                $this->SendDebug(__FUNCTION__, 'No Local Storage Status Information!');
            }
        }
    }

    /**
     * Weekly Schedule event
     *
     * @param integer $value Action value (ON=2, OFF=1)
     */
    private function ScheduleRecording(int $value)
    {
        $schedule = $this->ReadPropertyInteger('RecordingSchedule');
        $this->SendDebug(__FUNCTION__, 'Value: ' . $value . ',Schedule: ' . $schedule);
        if ($schedule == 0) {
            $this->SendDebug(__FUNCTION__, 'Schedule not linked!');
            // nothing todo
            return;
        }
        // Is Activate OFF
        if ($value == self::BLINK_SCHEDULE_RECORDING_OFF) {
            $this->SendDebug(__FUNCTION__, 'OFF: Disarm recording!');
            // Stop Recording
            $this->Disarm();
        }
        if ($value == self::BLINK_SCHEDULE_RECORDING_ON) {
            $this->SendDebug(__FUNCTION__, 'ON: Arm recording!');
            // Start Recording
            $this->Arm();
        }
    }

    /**
     * Create week schedule for snapshots
     *
     */
    private function CreateScheduleRecording()
    {
        $eid = $this->CreateWeeklySchedule($this->InstanceID, $this->Translate('Schedule recording'), self::BLINK_SCHEDULE_RECORDING_IDENT, self::BLINK_SCHEDULE_RECORDING_SWITCH, -1);
        if ($eid !== false) {
            $this->UpdateFormField('RecordingSchedule', 'value', $eid);
        }
    }

    /**
     * Read all media attributes from the given category
     *
     * @param int $store
     * @param int $limit
     * @param array $source
     *
     * @return array collection of found medias
     */
    private function ReadMediaWithAttributes(int $store, int $limit, array $source)
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
     * SaveMediaWithAttributes
     *
     * @param int $store
     * @param int $limit
     * @param bool $cache
     * @param array $medias
     * @param array $source
     * @param string $response
     *
     * @return bool
     */
    private function SaveMediaWithAttributes(int $store, int $limit, bool $cache, array &$medias, array $source, string $response)
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
     * Order assoziate array data
     *
     * @param array $arr
     * @param string|null $key
     * @param string $direction
     */
    private function OrderData(array $arr, string $key = null, string $direction = 'ASC')
    {
        // Check "order by key"
        if (!is_string($key) && !is_array($key)) {
            throw new InvalidArgumentException('Order() expects the first parameter to be a valid key or array');
        }
        // Build order-by clausel
        $props = [];
        if (is_string($key)) {
            $props[$key] = strtolower($direction) == 'asc' ? 1 : -1;
        }else {
            $i = count($key);
            foreach ($key as $k => $dir) {
                $props[$k] = strtolower($dir) == 'asc' ? $i : -($i);
                $i--;
            }
        }
        // Sort by passed keys
        usort($arr, function ($a, $b) use ($props)
        {
            foreach ($props as $key => $val) {
                if ($a[$key] == $b[$key]) continue;
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
}
