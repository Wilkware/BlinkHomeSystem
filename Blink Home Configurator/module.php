<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// Blink Home Configurator
class BlinkHomeConfigurator extends IPSModule
{
    // Helper Traits
    use DebugHelper;

    // Blink Device Models (up to now)
    private const BLINK_DEVICE_TYPE = [
        'null'              => '<unknown>',
        'cameras'           => 'Camera',
        'sync_modules'      => 'Sync Modul',
        'sirens'            => 'Sirens',
        'doorbells'         => 'Doorbell',
        'doorbell_buttons'  => 'Doorbell',
        'owls'              => 'Mini Camera',
    ];

    // Blink Device Models (up to now)
    private const BLINK_DEVICE_MODEL = [
        'null'              => '<unknown>',
        'sm1'               => 'Blink Sync Module 1',
        'sm2'               => 'Blink Sync Module 2',
        'mini'              => 'Blink Mini',
        'white'             => 'Blink Indoor',
        'catalina_indoor'   => 'Blink Indoor',
        'catalina'          => 'Blink Outdoor',
        'owl'               => 'Blink Mini',
        'xt'                => 'Blink XT1',
        'xt2'               => 'Blink XT2',
        'lotus'             => 'Blink Doorbell',
    ];

    // ModulID (Blink Home Client)
    private const BLINK_CLIENT_GUID = '{AF126D6D-83D1-44C2-6F61-96A4BB7A0E62}';
    // ModulID (Blink Home Device)
    private const BLINK_MODULE_GUID = '{3E3F3E1C-899C-2E17-E95E-6803DB5E95FE}';
    // ModulID (Blink Home Device)
    private const BLINK_DEVICE_GUID = '{7D2B8EFA-23D0-D29C-DBEE-E81F1FC2DBDC}';

    /**
     * Overrides the internal IPS_Create($id) function
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        // Required Parent (Blink Home Client)
        $this->ConnectParent(self::BLINK_CLIENT_GUID);
        // Properties
        $this->RegisterPropertyInteger('TargetCategory', 0);
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

        // Register reference to categorie
        $this->RegisterReference($this->ReadPropertyInteger('TargetCategory'));
    }

    /**
     * Configuration Form.
     *
     * @return JSON configuration string.
     */
    public function GetConfigurationForm()
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
        $this->SendDebug(__FUNCTION__, $connected);

        // All discovered devices/modules
        $devices = $this->DiscoveryBlinkDevices();

        // Collect all values
        $values = [];

        // Build configuration list values
        foreach ($devices as $device) {
            $value = [
                'id'            => $device['id'],
                'type'          => $this->Translate(self::BLINK_DEVICE_TYPE[$device['type']]),
                'model'         => $this->Translate(self::BLINK_DEVICE_MODEL[$device['model']]),
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
     * Delivers all found blink devices.
     *
     * @return array configuration list all devices
     */
    private function DiscoveryBlinkDevices()
    {
        // Collect all devices
        $data = [];
        $response = $this->RequestDataFromParent('homescreen');
        $devises = json_decode($response, true);
        if (isset($devises['sync_modules'])) {
            foreach ($devises['sync_modules'] as $dev) {
                $data[] = ['guid' => self::BLINK_MODULE_GUID, 'id'=> $dev['id'], 'name' => $dev['name'], 'type' => 'sync_modules', 'model' => $dev['type'], 'status' => $dev['status'], 'battery' => 'usb', 'serial' => $dev['serial'], 'firmware' => $dev['fw_version'], 'network' => $dev['network_id']];
            }
        }
        if (isset($devises['cameras'])) {
            foreach ($devises['cameras'] as $dev) {
                $data[] = ['guid' => self::BLINK_DEVICE_GUID, 'id'=> $dev['id'], 'name' => $dev['name'], 'type' => 'cameras', 'model' => $dev['type'], 'status' => $dev['status'], 'battery' => $dev['battery'], 'serial' => $dev['serial'], 'firmware' => $dev['fw_version'], 'network' => $dev['network_id']];
            }
        }
        if (isset($devises['owls'])) {
            foreach ($devises['owls'] as $dev) {
                $data[] = ['guid' => self::BLINK_DEVICE_GUID, 'id'=> $dev['id'], 'name' => $dev['name'], 'type' => 'owls', 'model' => $dev['type'], 'status' => $dev['status'], 'battery' => 'usb', 'serial' => $dev['serial'], 'firmware' => $dev['fw_version'], 'network' => $dev['network_id']];
            }
        }
        if (isset($devises['doorbells'])) {
            foreach ($devises['doorbells'] as $dev) {
                $data[] = ['guid' => self::BLINK_DEVICE_GUID, 'id'=> $dev['id'], 'name' => $dev['name'], 'type' => 'doorbells', 'model' => $dev['type'], 'status' => $dev['status'], 'battery' => $dev['battery'], 'serial' => $dev['serial'], 'firmware' => $dev['fw_version'], 'network' => $dev['network_id']];
            }
        }
        $this->SendDebug(__FUNCTION__, $response);
        return $data;
    }

    /**
     * Returns the instance ID for a given device.
     *
     * @param string $device Blink Device ID
     * @return array device data
     */
    private function GetBlinkHomeInstance($device)
    {
        $ids = IPS_GetInstanceListByModuleID(self::BLINK_DEVICE_GUID);
        foreach ($ids as $id) {
            if (IPS_GetProperty($id, 'DeviceID') == $device) {
                return $id;
            }
        }
        $ids = IPS_GetInstanceListByModuleID(self::BLINK_MODULE_GUID);
        foreach ($ids as $id) {
            if (IPS_GetProperty($id, 'DeviceID') == $device) {
                return $id;
            }
        }
        return 0;
    }

    /**
     * Returns the ascending list of category names for a given category id
     *
     * @param string $endpoint API endpoint request.
     * @return string Result of the API call.
     */
    private function RequestDataFromParent(string $endpoint)
    {
        return $this->SendDataToParent(json_encode([
            'DataID'      => '{83027B09-C481-91E7-6D24-BF49AA871452}',
            'Endpoint'    => $endpoint,
        ]));
    }

    /**
     * Returns the ascending list of category names for a given category id
     *
     * @param int $categoryId Category ID.
     * @return array List of reverse catergory names.
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
