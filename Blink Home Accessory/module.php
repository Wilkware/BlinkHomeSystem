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
        // CommandStatus
        $this->RegisterAttributeString('CommandID', '');
        // Device
        $this->RegisterPropertyString('DeviceType', 'null');
        $this->RegisterPropertyString('DeviceModel', 'null');
        $this->RegisterPropertyString('DeviceID', '');
        $this->RegisterPropertyString('NetworkID', '');
        $this->RegisterPropertyString('TargetID', '');
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

        $this->MaintainVariable('switch_light', $this->Translate('Light switch'), VARIABLETYPE_BOOLEAN, '~Switch', 1, true);
        $this->EnableAction('switch_light');
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
                    $this->MaintainVariable('battery', $this->Translate('Battery'), VARIABLETYPE_INTEGER, 'BHS.Battery', 2, true);
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
