<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class HTTP_Hooks extends Module
{
    private $apiKey;
    private $apiUrl;
    private $logFile;

    public function __construct()
    {
        $this->name = 'HTTP_Hooks';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'LDE Sistemas';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('HTTP Hooks');
        $this->description = $this->l('Faz requisições HTTP nos eventos do PrestaShop.');

        $this->apiKey = 'SUA_CHAVE_API'; // Defina sua chave de API aqui
        $this->apiUrl = 'https://api.exemplo.com'; // URL da API externa
        $this->logFile = _PS_MODULE_DIR_ . 'HTTP_Hooks/logs/hook_logs.txt';
    }

    public function install()
    {
        return parent::install() && $this->registerHook([
            'actionProductAdd',
            'actionProductUpdate',
            'actionProductDelete',
            'actionValidateOrder',
            'actionOrderStatusUpdate',
            'actionCartSave',
            'actionBeforeCartUpdateQty',
            'actionCustomerAccountAdd',
            'actionCustomerLoginAfter',
            'actionCustomerLogoutAfter',
            'actionPaymentConfirmation'
        ]);
    }

    public function hookActionProductAdd($params)
    {
        $this->sendRequestHTTP('produto/adicionado', $params);
    }

    public function hookActionProductUpdate($params)
    {
        $this->sendRequestHTTP('produto/atualizado', $params);
    }

    public function hookActionProductDelete($params)
    {
        $this->sendRequestHTTP('produto/excluido', $params);
    }

    public function hookActionValidateOrder($params)
    {
        $this->sendRequestHTTP('pedido/validado', $params);
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $this->sendRequestHTTP('pedido/status', $params);
    }

    public function hookActionCartSave($params)
    {
        $this->sendRequestHTTP('carrinho/salvo', $params);
    }

    public function hookActionBeforeCartUpdateQty($params)
    {
        $this->sendRequestHTTP('carrinho/atualizacao', $params);
    }

    public function hookActionCustomerAccountAdd($params)
    {
        $this->sendRequestHTTP('cliente/novo', $params);
    }

    public function hookActionCustomerLoginAfter($params)
    {
        $this->sendRequestHTTP('cliente/login', $params);
    }

    public function hookActionCustomerLogoutAfter($params)
    {
        $this->sendRequestHTTP('cliente/logout', $params);
    }

    public function hookActionPaymentConfirmation($params)
    {
        $this->sendRequestHTTP('pagamento/confirmado', $params);
    }

    private function sendRequestHTTP($path, $params)
    {
        if (empty($params)) {
            return;
        }

        $url = $this->apiUrl . '/' . $path;
        $payload = json_encode($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'API_KEY: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || ($httpCode < 200 || $httpCode >= 300)) {
            $this->logErroRequest($path, $payload, $response, $httpCode);
        }
        
        curl_close($ch);

        return $response;
    }

    private function logErroRequest($path, $payload, $response, $httpCode)
    {
        try {
            $logMessage = "[" . date('Y-m-d H:i:s') . "] PATH: $path\n" .
                          "Payload: $payload\n" .
                          "Response: $response\n" .
                          "HTTP Code: $httpCode\n---------------------------\n";

            if (!is_dir(dirname($this->logFile))) {
                mkdir(dirname($this->logFile), 0777, true);
            }
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        } catch (Exception $e) {
            // Caso ocorra erro ao registrar log, ele será ignorado.
        }
    }
}