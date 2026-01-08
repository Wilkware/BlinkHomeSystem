<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// Blink Home Accessory
class BlinkHomeAccessory extends IPSModuleStrict
{
    // Helper Traits
    use DebugHelper;
    use VariableHelper;

    /**
     * @var string ModulID (Blink Home Client)
     */
    private const BLINK_CLIENT_GUID = '{AF126D6D-83D1-44C2-6F61-96A4BB7A0E62}';

    /**
     * @var array<string,mixed> Download Presentation (Switch)
     */
    private const BLINK_PRESENTATION_LIGHT = [
        'PRESENTATION'   => VARIABLE_PRESENTATION_SWITCH,
        'USE_ICON_FALSE' => true,
        'ICON_TRUE'      => 'lightbulb-on',
        'ICON_FALSE'     => 'lightbulb',
        'USAGE_TYPE'     => 0
    ];

    /**
     * @var array<string,mixed> Battery Presentation (Value)
     */
    private const BLINK_PRESENTATION_BATTERY = [
        'PRESENTATION'       => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
        'MIN'                => 0,
        'MAX'                => 3,
        'THOUSANDS_SEPARATOR'=> '',
        'DISPLAY_TYPE'       => 0,
        'ICON'               => 'battery-bolt',
        'INTERVALS_ACTIVE'   => true,
        'INTERVALS'          => '[
            {"ColorDisplay":16711935,"ContentColorDisplay":-1,"IntervalMinValue":0,"IntervalMaxValue":0,"ConstantActive":true,"ConstantValue":"Unbekannt","ConversionFactor":1,"IconActive":true,"IconValue":"battery-exclamation","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":16711935,"ContentColorActive":false,"ContentColorValue":-1},
            {"ColorDisplay":16711680,"ContentColorDisplay":-1,"IntervalMinValue":1,"IntervalMaxValue":1,"ConstantActive":true,"ConstantValue":"Niedrig","ConversionFactor":1,"IconActive":true,"IconValue":"battery-low","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":16711680,"ContentColorActive":false,"ContentColorValue":-1},
            {"ColorDisplay":16776960,"ContentColorDisplay":-1,"IntervalMinValue":2,"IntervalMaxValue":2,"ConstantActive":true,"ConstantValue":"Mittel","ConversionFactor":1,"IconActive":true,"IconValue":"battery-half","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":16776960,"ContentColorActive":false,"ContentColorValue":-1},
            {"ColorDisplay":65280,"ContentColorDisplay":-1,"IntervalMinValue":3,"IntervalMaxValue":3,"ConstantActive":true,"ConstantValue":"Gut","ConversionFactor":1,"IconActive":true,"IconValue":"battery-full","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":65280,"ContentColorActive":false,"ContentColorValue":-1}
        ]',
        'PERCENTAGE'   => false,
        'CONTENT_COLOR'=> -1,
        'PREFIX'       => '',
        'SUFFIX'       => '',
        'COLOR'        => -1,
        'USAGE_TYPE'   => 0,
        'SHOW_PREVIEW' => true,
        'PREVIEW_STYLE'=> 1
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
        if ((float) IPS_GetKernelVersion() < 8.2) {
            $this->ConnectParent(self::BLINK_CLIENT_GUID);
        }
        // CommandStatus
        $this->RegisterAttributeString('CommandID', '');
        // Device
        $this->RegisterPropertyString('DeviceType', 'null');
        $this->RegisterPropertyString('DeviceModel', 'null');
        $this->RegisterPropertyString('DeviceID', '');
        $this->RegisterPropertyString('NetworkID', '');
        $this->RegisterPropertyString('TargetID', '');

        // Variable
        $this->RegisterPropertyBoolean('UpdateBattery', true);
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

        // Extract Version
        $ins = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($ins['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);
        $form['actions'][2]['items'][2]['caption'] = sprintf('v%s.%d', $lib['Version'], $lib['Build']);

        // Return if parent is not confiured
        if (!$this->HasActiveParent()) {
            return json_encode($form);
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

        $battery = $this->ReadPropertyBoolean('UpdateBattery');

        $this->MaintainVariable('switch_light', $this->Translate('Light switch'), VARIABLETYPE_BOOLEAN, self::BLINK_PRESENTATION_LIGHT, 1, true);
        $this->EnableAction('switch_light');

        // Update battery
        $this->MaintainVariable('battery', $this->Translate('Battery'), VARIABLETYPE_INTEGER, self::BLINK_PRESENTATION_BATTERY, 2, $battery);
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
            case 'switch_light':
                $this->SwitchLight($value);
                break;
            default:
                break;
        }
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
        if (isset($data['Battery'])) {
            $device = $this->ReadPropertyString('DeviceID');
            foreach ($data['Battery'] as $entry) {
                if ($entry['device'] == $device) {
                    $this->MaintainVariable('battery', $this->Translate('Battery'), VARIABLETYPE_INTEGER, self::BLINK_PRESENTATION_BATTERY, 2, true);
                    $this->SetValueInteger('battery', ($entry['battery'] === 'ok') ? 3 : 1);
                }
            }
        }
        return '';
    }

    /**
     * Enable or disable light
     *
     * @param bool $value true for switch on, otherwise off..
     *
     * @return void
     */
    private function SwitchLight(bool $value): void
    {
        $network = $this->ReadPropertyString('NetworkID');
        $device = $this->ReadPropertyString('DeviceID');
        $target = $this->ReadPropertyString('TargetID');
        // Parameter
        $param = ['NetworkID' => $network, 'DeviceID' => $device, 'TargetID' => $target, 'Switch' => $value];
        // Request
        $response = $this->RequestDataFromParent('light', $param);
        $this->LogDebug(__FUNCTION__, $response);
        if ($response === '[]') {
            $this->LogDebug(__FUNCTION__, 'Error occurred for switching motion detection');
        } else {
            $this->SetValueBoolean('switch_light', $value);
        }
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
}
