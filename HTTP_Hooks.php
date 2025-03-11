<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class HTTP_Hooks extends Module
{

    private $apiKey;
    private $apiUrl;
    private $logFile;
    private $eventPaths;

    public function __construct()
    {
        $this->name = 'HTTP_Hooks';
        $this->tab = 'administration';
        $this->version = '1.0.1';
        $this->author = 'FelipeLm3g';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('HTTP Hooks');
        $this->description = $this->l('Faz requisições HTTP nos eventos do PrestaShop.');

        // Carrega as configurações armazenadas
        $this->apiKey = Configuration::get('HTTP_HOOKS_API_KEY', null, null, null);
        $this->apiUrl = Configuration::get('HTTP_HOOKS_API_URL', null, null, null);
        $this->eventPaths = json_decode(Configuration::get('HTTP_HOOKS_EVENT_PATHS'), true);

        $this->logFile = _PS_MODULE_DIR_ . 'HTTP_Hooks/logs/hook_logs.txt';
    }

    public function install()
    {

        // Limpa o cache antes de instalar
        $this->clearCache();

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
        ]) && $this->addConfiguration();
    }

    private function clearCache()
    {
        $cacheDirs = [
            _PS_ROOT_DIR_ . '/var/cache/prod/',
            _PS_ROOT_DIR_ . '/var/cache/dev/'
        ];

        foreach ($cacheDirs as $dir) {
            if (is_dir($dir)) {
                $this->deleteDir($dir);
            }
        }
    }

    private function deleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            return;
        }

        foreach (scandir($dirPath) as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $dirPath . DIRECTORY_SEPARATOR . $file;
                is_dir($filePath) ? $this->deleteDir($filePath) : unlink($filePath);
            }
        }

        rmdir($dirPath);
    }

    public function uninstall()
    {
        // Remove as configurações ao desinstalar
        Configuration::deleteByName('HTTP_HOOKS_API_KEY');
        Configuration::deleteByName('HTTP_HOOKS_API_URL');
        Configuration::deleteByName('HTTP_HOOKS_EVENT_PATHS');

        return parent::uninstall();
    }

    public function getContent()
    {
        // Exibe o formulário de configuração
        if (Tools::isSubmit('submit_' . $this->name)) {
            $this->saveConfig();
        }

        return $this->displayForm();
    }

    private function displayForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configurações do HTTP Hooks'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Chave da API'),
                        'name' => 'HTTP_HOOKS_API_KEY',
                        'size' => 64,
                        'required' => true,
                        'value' => $this->apiKey
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('URL Base da API'),
                        'name' => 'HTTP_HOOKS_API_URL',
                        'size' => 64,
                        'required' => true,
                        'value' => $this->apiUrl
                    ],
                    // Hooks de Produtos
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionProductAdd'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_product_add',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionProductAdd']) ? $this->eventPaths['actionProductAdd'] : 'product/add'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionProductUpdate'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_product_update',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionProductUpdate']) ? $this->eventPaths['actionProductUpdate'] : 'product/update'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionProductDelete'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_product_delete',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionProductDelete']) ? $this->eventPaths['actionProductDelete'] : 'product/delete'
                    ],
                    // Hooks de Pedidos
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionValidateOrder'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_order_validate',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionValidateOrder']) ? $this->eventPaths['actionValidateOrder'] : 'order/validate'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionOrderStatusUpdate'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_order_status_update',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionOrderStatusUpdate']) ? $this->eventPaths['actionOrderStatusUpdate'] : 'order/status/update'
                    ],
                    // Hooks de Carrinho
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionCartAdd'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_cart_add',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionCartAdd']) ? $this->eventPaths['actionCartAdd'] : 'cart/add'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionCartUpdate'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_cart_update',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionCartUpdate']) ? $this->eventPaths['actionCartUpdate'] : 'cart/update'
                    ],
                    // Hooks de Clientes
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionCustomerAdd'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_customer_add',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionCustomerAdd']) ? $this->eventPaths['actionCustomerAdd'] : 'customer/add'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionCustomerUpdate'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_customer_update',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionCustomerUpdate']) ? $this->eventPaths['actionCustomerUpdate'] : 'customer/update'
                    ],
                    // Hooks de Pagamento
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionPaymentSuccess'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_payment_success',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionPaymentSuccess']) ? $this->eventPaths['actionPaymentSuccess'] : 'payment/success'
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Path para Evento: actionPaymentFailure'),
                        'name' => 'HTTP_HOOKS_EVENT_PATH_payment_failure',
                        'size' => 64,
                        'required' => false,
                        'value' => isset($this->eventPaths['actionPaymentFailure']) ? $this->eventPaths['actionPaymentFailure'] : 'payment/failure'
                    ],
                    // Adicione mais campos para outros eventos conforme necessário
                ],
                'submit' => [
                    'title' => $this->l('Salvar'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->fields_value['HTTP_HOOKS_API_KEY'] = $this->apiKey;
        $helper->fields_value['HTTP_HOOKS_API_URL'] = $this->apiUrl;

        // Preenchendo os paths dos eventos
        foreach ($this->eventPaths as $event => $path) {
            $helper->fields_value['HTTP_HOOKS_EVENT_PATH_' . $event] = $path;
        }

        $helper->submit_action = 'submit_' . $this->name;

        return $helper->generateForm([$fields_form]);
    }

    private function saveConfig()
    {
        $apiKey = Tools::getValue('HTTP_HOOKS_API_KEY');
        $apiUrl = Tools::getValue('HTTP_HOOKS_API_URL');

        // Salva cada path de evento individualmente
        $eventPaths = [
            'actionProductAdd' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_product_add'),
            'actionProductUpdate' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_product_update'),
            'actionProductDelete' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_product_delete'),
            'actionValidateOrder' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_order_validate'),
            'actionOrderStatusUpdate' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_order_status_update'),
            // Hooks de Carrinho
            'actionCartAdd' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_cart_add'),
            'actionCartUpdate' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_cart_update'),
            // Hooks de Clientes
            'actionCustomerAdd' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_customer_add'),
            'actionCustomerUpdate' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_customer_update'),
            // Hooks de Pagamento
            'actionPaymentSuccess' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_payment_success'),
            'actionPaymentFailure' => Tools::getValue('HTTP_HOOKS_EVENT_PATH_payment_failure'),
        ];

        if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            $this->_errors[] = $this->l('A URL da API fornecida é inválida.');
        }

        if (empty($apiKey)) {
            $this->_errors[] = $this->l('A chave da API não pode estar vazia.');
        }


        // Salva as configurações no banco de dados
        Configuration::updateValue('HTTP_HOOKS_API_KEY', $apiKey);
        Configuration::updateValue('HTTP_HOOKS_API_URL', $apiUrl);
        Configuration::updateValue('HTTP_HOOKS_EVENT_PATHS', json_encode($eventPaths));
    }

    // Método para enviar requisições HTTP
    private function sendRequestHTTP($eventName, $params)
    {

        if (empty($this->apiKey) || empty($this->apiUrl)) {
            // Não faz a requisição se a URL ou chave da API estiverem vazias
            return;
        }

        if (!isset($this->eventPaths[$eventName])) {
            // Não faz a requisição se o path para o evento não estiver configurado
            return;
        }

        if (empty($params)) {
            return;
        }

        // Recupera o path do evento configurado
        $path = $this->eventPaths[$eventName];
        $url = $this->apiUrl . '/' . $path;
        $payload = json_encode($params);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        try {
            if ($response === false) {
                if ($httpCode < 200 || $httpCode >= 300) {
                    $this->logErroRequest($eventName, $payload, $response, $httpCode);
                } else {
                    $this->logErroRequest($eventName, $payload, $response, $httpCode);
                }
            }
        } catch (Exception $exc) {
            $this->logErroRequest($eventName, $payload, $exc->getTraceAsString(), $exc->getCode());
        }

        curl_close($ch);

        return $response;
    }

    // Hook de eventos para capturar ações de clientes, pedidos, produtos, etc.
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

    private function logErroRequest($eventName, $payload, $response, $httpCode)
    {
        $logMessage = sprintf(
            "[%s] - Evento: %s - Status: %d - Resposta: %s - Payload: %s\n",
            date('Y-m-d H:i:s'),
            $eventName,
            $httpCode,
            $response,
            $payload
        );
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    private function addConfiguration()
    {
        // Verifica se as configurações estão definidas, caso contrário, define valores padrões
        if (!Configuration::get('HTTP_HOOKS_API_KEY')) {
            Configuration::updateValue('HTTP_HOOKS_API_KEY', 'SUA_CHAVE_API');
        }

        if (!Configuration::get('HTTP_HOOKS_API_URL')) {
            Configuration::updateValue('HTTP_HOOKS_API_URL', 'https://api.exemplo.com');
        }

        if (!Configuration::get('HTTP_HOOKS_EVENT_PATHS')) {
            Configuration::updateValue('HTTP_HOOKS_EVENT_PATHS', json_encode([
                'actionProductAdd' => 'product/add',
                'actionProductUpdate' => 'product/update',
                'actionProductDelete' => 'product/delete',
                'actionValidateOrder' => 'order/validate',
                'actionOrderStatusUpdate' => 'order/status/update',
                // Hooks de Carrinho
                'actionCartAdd' => 'cart/add',
                'actionCartUpdate' => 'cart/update',
                // Hooks de Clientes
                'actionCustomerAdd' => 'customer/add',
                'actionCustomerUpdate' => 'customer/update',
                // Hooks de Pagamento
                'actionPaymentSuccess' => 'payment/success',
                'actionPaymentFailure' => 'payment/failure',
            ]));
        }
    }
}
