<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// Blink Home Configurator
class BlinkHomeConfigurator extends IPSModuleStrict
{
    // Helper Traits
    use DebugHelper;

    /**
     * @var array<string,string> Blink Device Types (up to now)
     */
    private const BLINK_DEVICE_TYPE = [
        'null'              => '<unknown>',
        'cameras'           => 'Camera',
        'sync_modules'      => 'Sync Modul',
        'sirens'            => 'Sirens',
        'doorbells'         => 'Doorbell',
        'doorbell_buttons'  => 'Doorbell',
        'owls'              => 'Mini Camera',
        'accessories'       => 'Accessorie',
    ];

    /**
     * @var array<string,string> Blink Device Models (up to now)
     */
    private const BLINK_DEVICE_MODEL = [
        'null'              => '<unknown>',
        'sm1'               => 'Blink Sync Module 1',
        'sm2'               => 'Blink Sync Module 2',
        'mini'              => 'Blink Mini',
        'white'             => 'Blink Indoor',
        'catalina_indoor'   => 'Blink Indoor',
        'catalina'          => 'Blink Outdoor',
        'sedona'            => 'Blink Outdoor 4',
        'owl'               => 'Blink Mini',
        'hawk'              => 'Blink Mini 2',
        'chickadee'         => 'Blink Mini 2K+',
        'xt'                => 'Blink XT1',
        'xt2'               => 'Blink XT2',
        'lotus'             => 'Blink Doorbell',
        'storm'             => 'Blink Floodlight Mount',
        'rosie'             => 'Blink Pan-Tilt Mount',
    ];

    /**
     * @var string ModulID (Blink Home Client)
     */
    private const BLINK_CLIENT_GUID = '{AF126D6D-83D1-44C2-6F61-96A4BB7A0E62}';

    /**
     * @var string ModulID (Blink Home Sync Modul)
     */
    private const BLINK_MODULE_GUID = '{3E3F3E1C-899C-2E17-E95E-6803DB5E95FE}';

    /**
     * @var string ModulID (Blink Home Device)
     */
    private const BLINK_DEVICE_GUID = '{7D2B8EFA-23D0-D29C-DBEE-E81F1FC2DBDC}';

    /**
     * @var string ModulID (Blink Home Accessory)
     */
    private const BLINK_ACCESSORY_GUID = '{1D064E05-B3D7-54C6-F37D-D0068AEF7B89}';

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
        // Required Parent (Blink Home Client)
        $this->ConnectParent(self::BLINK_CLIENT_GUID);
        // Properties
        $this->RegisterPropertyInteger('TargetCategory', 0);
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
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        // Return if parent is not confiured
        if (!$this->HasActiveParent()) {
            return json_encode($form);
        }

        // Version check
        $version = (float) IPS_GetKernelVersion();
        // Save location
        $location = $this->GetPathOfCategory($this->ReadPropertyInteger('TargetCategory'));
        // Enable or disable "TargetCategory" for 6.x
        if ($version < 7) {
            $form['elements'][2]['visible'] = true;
        }

        // All connected blink devices and blink modules
        $connected = [];
        // Get all the DEVICE instances that are connected to the configurators I/O
        foreach (IPS_GetInstanceListByModuleID(self::BLINK_DEVICE_GUID) as $instance) {
            if (IPS_GetInstance($instance)['ConnectionID'] === IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                $connected[IPS_GetProperty($instance, 'DeviceID')][] = $instance;
            }
        }
        // Get all the MODUL instances that are connected to the configurators I/O
        foreach (IPS_GetInstanceListByModuleID(self::BLINK_MODULE_GUID) as $instance) {
            if (IPS_GetInstance($instance)['ConnectionID'] === IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                $connected[IPS_GetProperty($instance, 'DeviceID')][] = $instance;
            }
        }
        // Get all the ACCESSORY instances that are connected to the configurators I/O
        foreach (IPS_GetInstanceListByModuleID(self::BLINK_ACCESSORY_GUID) as $instance) {
            if (IPS_GetInstance($instance)['ConnectionID'] === IPS_GetInstance($this->InstanceID)['ConnectionID']) {
                $connected[IPS_GetProperty($instance, 'DeviceID')][] = $instance;
            }
        }
        $this->LogDebug(__FUNCTION__, $connected);

        // All discovered devices/modules
        $devices = $this->DiscoveryBlinkDevices();

        // Collect all values
        $values = [];

        // Build configuration list values
        foreach ($devices as $device) {
            if ($device['guid'] == self::BLINK_ACCESSORY_GUID) {
                $value = [
                    'id'            => $device['id'],
                    'type'          => $this->Translate(self::BLINK_DEVICE_TYPE[$device['type']]),
                    'model'         => $this->Translate(self::BLINK_DEVICE_MODEL[$device['model']]),
                    'power'         => $device['power'],
                    'battery'       => $device['battery'],
                    'firmware'      => ' - ',
                    'network'       => $device['network'],
                    'create'        => [
                        [
                            'moduleID'      => $device['guid'],
                            'configuration' => ['DeviceID' => strval($device['id']), 'NetworkID' => strval($device['network']), 'DeviceType' => $device['type'], 'DeviceModel' => $device['model'], 'TargetID' => strval($device['target'])],
                            'location'      => ($version < 7) ? $location : [],
                        ],
                    ],
                ];
            } else {
                $value = [
                    'id'            => $device['id'],
                    'type'          => $this->Translate(self::BLINK_DEVICE_TYPE[$device['type']]),
                    'model'         => $this->Translate(self::BLINK_DEVICE_MODEL[$device['model']]),
                    'power'         => $device['power'],
                    'battery'       => $device['battery'],
                    'firmware'      => $device['firmware'],
                    'network'       => $device['network'],
                    'create'        => [
                        [
                            'moduleID'      => $device['guid'],
                            'configuration' => ['DeviceID' => strval($device['id']), 'NetworkID' => strval($device['network']), 'DeviceType' => $device['type'], 'DeviceModel' => $device['model']],
                            'location'      => ($version < 7) ? $location : [],
                        ],
                    ],
                ];
            }
            if (isset($connected[$device['id']])) {
                $value['name'] = IPS_GetName($connected[$device['id']][0]);
                $value['instanceID'] = $connected[$device['id']][0];
                // remove it from the list
                unset($connected[$device['id']][0]);
            } else {
                $value['name'] = $device['name'];
                $value['instanceID'] = 0;
            }
            $values[] = $value;
        }

        foreach ($connected as $device => $instances) {
            foreach ($instances as $index => $instance) {
                // However, if an device is not a discovered device or an device has multiple instances, they are incorrect
                $values[] = [
                    'id'            => empty($device) ? $this->Translate('<unknown>') : $device,
                    'name'          => IPS_GetName($instance),
                    'type'          => $this->Translate(self::BLINK_DEVICE_TYPE[IPS_GetProperty($instance, 'DeviceType')]),
                    'model'         => $this->Translate(self::BLINK_DEVICE_MODEL[IPS_GetProperty($instance, 'DeviceModel')]),
                    'power'         => ' - ',
                    'battery'       => ' - ',
                    'firmware'      => ' - ',
                    'network'       => empty(IPS_GetProperty($instance, 'NetworkID')) ? $this->Translate('<unknown>') : IPS_GetProperty($instance, 'NetworkID'),
                    'instanceID'    => $instance,
                ];
            }
        }

        // Set available values
        if (!empty($values)) {
            $form['actions'][0]['values'] = $values;
        }

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

        // Register reference to categorie
        $this->RegisterReference($this->ReadPropertyInteger('TargetCategory'));
    }

    /**
     * Delivers all found blink devices.
     *
     * @return array<int, array<string,mixed>> configuration list of all devices
     */
    private function DiscoveryBlinkDevices(): array
    {
        // Collect all devices
        $data = [];
        $response = $this->RequestDataFromParent('homescreen');
        $devises = json_decode($response, true);
        if (isset($devises['sync_modules'])) {
            foreach ($devises['sync_modules'] as $dev) {
                $data[] = ['guid' => self::BLINK_MODULE_GUID, 'id'=> $dev['id'], 'name' => $dev['name'], 'type' => 'sync_modules', 'model' => $dev['type'], 'status' => $dev['status'], 'battery' => ' - ', 'power' => 'USB', 'serial' => $dev['serial'], 'firmware' => $dev['fw_version'], 'network' => $dev['network_id']];
            }
        }
        if (isset($devises['cameras'])) {
            foreach ($devises['cameras'] as $dev) {
                $data[] = ['guid' => self::BLINK_DEVICE_GUID, 'id'=> $dev['id'], 'name' => $dev['name'], 'type' => 'cameras', 'model' => $dev['type'], 'status' => $dev['status'], 'battery' => $dev['battery'], 'power' => $this->Translate('Battery'), 'serial' => $dev['serial'], 'firmware' => $dev['fw_version'], 'network' => $dev['network_id']];
            }
        }
        if (isset($devises['owls'])) {
            foreach ($devises['owls'] as $dev) {
                $data[] = ['guid' => self::BLINK_DEVICE_GUID, 'id'=> $dev['id'], 'name' => $dev['name'], 'type' => 'owls', 'model' => $dev['type'], 'status' => $dev['status'], 'battery' => ' - ', 'power' => 'USB', 'serial' => $dev['serial'], 'firmware' => $dev['fw_version'], 'network' => $dev['network_id']];
            }
        }
        if (isset($devises['doorbells'])) {
            foreach ($devises['doorbells'] as $dev) {
                $data[] = ['guid' => self::BLINK_DEVICE_GUID, 'id'=> $dev['id'], 'name' => $dev['name'], 'type' => 'doorbells', 'model' => $dev['type'], 'status' => $dev['status'], 'battery' => $dev['battery'], 'power' => $this->Translate('Battery'), 'serial' => $dev['serial'], 'firmware' => $dev['fw_version'], 'network' => $dev['network_id']];
            }
        }
        if (isset($devises['accessories'])) {
            // Floodlight Mount
            if (isset($devises['accessories']['storm'])) {
                foreach ($devises['accessories']['storm'] as $dev) {
                    $this->LogDebug(__FUNCTION__, $dev);
                    $nid = $this->GetBlinkTargetNetwork($dev['target_id'], $data);
                    if ($nid == -1) {
                        $nid = $this->Translate('<unknown>');
                    }
                    $data[] = ['guid' => self::BLINK_ACCESSORY_GUID, 'id'=> $dev['id'],  'name' => $this->Translate('Floodlight'), 'type' => 'accessories', 'model' => $dev['type'], 'target' => $dev['target_id'], 'battery' => $dev['battery'], 'serial' => $dev['serial'], 'power' => $this->Translate('Battery'), 'network' => $nid];
                }
            }
            // Pan-Tilt
            if (isset($devises['accessories']['rosie'])) {
                foreach ($devises['accessories']['rosie'] as $dev) {
                    $this->LogDebug(__FUNCTION__, $dev);
                }
            }
        }

        $this->LogDebug(__FUNCTION__, $response);
        return $data;
    }

    /**
     * Returns the network ID for a given device.
     *
     * @param int $target  Target device ID
     * @param array<int,array<string,mixed>> $devices Array of devices
     * @return int Network ID
     */
    private function GetBlinkTargetNetwork(int $target, array $devices): int
    {
        foreach ($devices as $dev) {
            if ($dev['id'] == $target) {
                return $dev['network'];
            }
        }
        return -1;
    }

    /**
     * Returns the ascending list of category names for a given category id
     *
     * @param string $endpoint API endpoint request.
     * @return string Result of the API call.
     */
    private function RequestDataFromParent(string $endpoint): string
    {
        return $this->SendDataToParent(json_encode([
            'DataID'      => '{83027B09-C481-91E7-6D24-BF49AA871452}',
            'Endpoint'    => $endpoint,
        ]));
    }

    /**
     * Returns the ascending list of category names for a given category id.
     *
     * @param int $categoryId Category ID.
     * @return array<int,string> List of category names from root to leaf
     */
    private function GetPathOfCategory(int $categoryId): array
    {
        if ($categoryId === 0) {
            return [];
        }

        $path[] = IPS_GetName($categoryId);
        $parentId = IPS_GetObject($categoryId)['ParentID'];

        while ($parentId > 0) {
            $path[] = IPS_GetName($parentId);
            $parentId = IPS_GetObject($parentId)['ParentID'];
        }

        return array_reverse($path);
    }
}