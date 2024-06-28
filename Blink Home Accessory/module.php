<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// Blink Home Accessory
class BlinkHomeAccessory extends IPSModule
{
    // Helper Traits
    use DebugHelper;
    use VariableHelper;

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
        $this->RegisterPropertyString('DeviceType', 'null');
        $this->RegisterPropertyString('DeviceModel', 'null');
        $this->RegisterPropertyString('DeviceID', '');
        $this->RegisterPropertyString('NetworkID', '');
        $this->RegisterPropertyString('TargetID', '');
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

        return json_encode($form);
    }

    /**
     * Overrides the internal IPSModule::ApplyChanges($id) function
     */
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->MaintainVariable('switch_light', $this->Translate('Light switch'), VARIABLETYPE_BOOLEAN, '~Switch', 1, true);
        $this->EnableAction('switch_light');
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
            case 'switch_light':
                $this->SwitchLight($value);
                break;
            default:
                break;
        }
        //return true;
    }

    /**
     * Enable or disable light
     * 
     * @param bool $value true for switch on, otherwise off..
     */
    private function SwitchLight(bool $value)
    {
        $network = $this->ReadPropertyString('NetworkID');
        $device = $this->ReadPropertyString('DeviceID');
        $target = $this->ReadPropertyString('TargetID');
        // Parameter
        $param = ['NetworkID' => $network, 'DeviceID' => $device, 'TargetID' => $target, 'Switch' => $value];
        // Request
        $response = $this->RequestDataFromParent('light', $param);
        $this->SendDebug(__FUNCTION__, $response);
        if ($response === '[]') {
            $this->SendDebug(__FUNCTION__, 'Error occurred for switching motion detection');
        } else {
            $this->SetValueBoolean('switch_light', $value);
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
}
