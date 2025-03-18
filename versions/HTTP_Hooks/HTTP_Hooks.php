<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class HTTP_Hooks extends Module {

    private $apiKey;
    private $apiUrl;
    private $logFile;
    private $eventPaths;

    public function __construct() {
        $this->name = 'HTTP_Hooks';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'FelipeLm3g';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('HTTP Hooks');
        $this->description = $this->l('Faz requisições HTTP nos eventos do PrestaShop.');

        // Carrega as configurações armazenadas
        $this->apiKey = Configuration::get('HTTP_HOOKS_API_KEY');
        $this->apiUrl = Configuration::get('HTTP_HOOKS_API_URL');
        $this->eventPaths = json_decode(Configuration::get('HTTP_HOOKS_EVENT_PATHS'), true) ?? [];

        // Define o arquivo de log na pasta padrão do PrestaShop (var/logs/)
        $this->logFile = _PS_ROOT_DIR_ . '/var/logs/http_hooks.log';

        // Cria o diretório de logs se não existir
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    public function install() {
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

    public function uninstall() {
        // Remove as configurações ao desinstalar
        Configuration::deleteByName('HTTP_HOOKS_API_KEY');
        Configuration::deleteByName('HTTP_HOOKS_API_URL');
        Configuration::deleteByName('HTTP_HOOKS_EVENT_PATHS');

        return parent::uninstall();
    }

    public function getContent() {
        // Exibe o formulário de configuração
        if (Tools::isSubmit('submit_' . $this->name)) {
            $this->saveConfig();
        }

        return $this->displayForm();
    }

    private function displayForm() {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configurações do HTTP Hooks'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'name' => 'description_html',
                        'html_content' => '<p class="help-block">&nbsp;&nbsp;' . $this->l('Aqui você pode configurar as opções relacionadas aos HTTP Hooks.') . '</p><br><a href="https://github.com/felipelm3g/HTTP_Hooks" target="_blank">https://github.com/felipelm3g/HTTP_Hooks</a>'
                    ],
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
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Salvar'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        // Preenchendo os paths dos eventos
        foreach ($this->eventPaths as $event => $path) {
            $fields_form['form']['input'][] = [
                'type' => 'text',
                'label' => $this->l($event),
                'name' => 'HTTP_HOOKS_EVENT_PATH_' . $event,
                'size' => 64,
                'required' => false,
                'value' => $path
            ];
        }

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

    private function saveConfig() {
        $apiKey = Tools::getValue('HTTP_HOOKS_API_KEY');
        $apiUrl = Tools::getValue('HTTP_HOOKS_API_URL');

        // Validação
        if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            $this->logInfo('URL da API inválida: ' . $apiUrl, __LINE__);
            $this->_errors[] = $this->l('A URL da API fornecida é inválida.');
        }

        if (empty($apiKey)) {
            $this->logInfo('Chave da API não pode estar vazia', __LINE__);
            $this->_errors[] = $this->l('A chave da API não pode estar vazia.');
        }

        if (empty($this->_errors)) {
            // Percorre os eventos e busca seus valores dinamicamente para salvar cada path de evento individualmente
            foreach ($this->eventPaths as $event => $path) {
                $this->eventPaths[$event] = Tools::getValue('HTTP_HOOKS_EVENT_PATH_' . $event, $path);
            }

            // Salva as configurações no banco de dados
            Configuration::updateValue('HTTP_HOOKS_API_KEY', $apiKey);
            Configuration::updateValue('HTTP_HOOKS_API_URL', $apiUrl);
            Configuration::updateValue('HTTP_HOOKS_EVENT_PATHS', json_encode($this->eventPaths));

            // Confirmação de sucesso
            $this->_confirmations[] = $this->l('Configurações salvas com sucesso.');
        }
    }

    private function montarURL($base, $path) {
        // Remove a barra final da URL base, se houver
        $base = rtrim($base, '/');

        // Remove a barra inicial do path, se houver
        $path = ltrim($path, '/');

        // Concatena a URL base com o path
        return $base . '/' . $path;
    }

    // Método para enviar requisições HTTP
    private function sendRequestHTTP($eventName, $params, $method = 'POST') {
        // Verifica se a API Key e a URL da API estão configuradas
        if (empty($this->apiKey) || empty($this->apiUrl)) {
            $this->logInfo("Chave da API ou URL da API não configuradas", __LINE__);
            return;
        }

        // Verifica se o path para o evento está configurado
        if (!isset($this->eventPaths[$eventName])) {
            $this->logInfo("Path para o evento '$eventName' não configurado", __LINE__);
            return;
        }

        // Verifica se os parâmetros estão vazios
        if (empty($params)) {
            $this->logInfo("Parâmetros vazios para o evento '$eventName'", __LINE__);
            return;
        }

        // Monta a URL completa
        $path = $this->eventPaths[$eventName];
        $url = $this->montarURL($this->apiUrl, $path);
        $payload = json_encode($params);
        $headers = [
            'Content-Type: application/json',
            'API-KEY: ' . $this->apiKey
        ];

        // Log da URL e do payload
        $this->logInfo("URL: $url");
        $this->logInfo("Payload: $payload");

        // Inicia a requisição cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Executa a requisição
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Verifica se houve erro na requisição
        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            $errorMessage = curl_error($ch);
            $this->logInfo("Erro ao enviar requisição: $errorMessage - HTTP Code: $httpCode", __LINE__);
            $this->logInfo("Payload de retorno: $response", __LINE__);
            $this->_errors[] = $this->l('Erro ao enviar requisição: ' . $errorMessage);
        }

        // Fecha a conexão cURL
        curl_close($ch);

        return $response;
    }

    // Método para gravar logs de informação
    private function logInfo($message, $line = null) {
        // Verifica se o modo de desenvolvedor está ativo
        if (!defined('_PS_MODE_DEV_') || !_PS_MODE_DEV_) {
            return;
        }

        // Formata a mensagem de log
        $logMessage = sprintf(
                "[%s] - %s\n",
                date('Y-m-d H:i:s'),
                $message
        );

        // Adiciona a linha apenas em caso de erros
        if ($line !== null) {
            $logMessage = sprintf(
                    "[%s] - %s - Linha: %d\n",
                    date('Y-m-d H:i:s'),
                    $message,
                    $line
            );
        }

        // Grava o log no arquivo
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    // Hook de eventos para capturar ações de clientes, pedidos, produtos, etc.
    public function hookActionProductAdd($params) {
        $this->logInfo("Hook actionProductAdd chamado");
        $this->sendRequestHTTP('actionProductAdd', $params);
    }

    public function hookActionProductUpdate($params) {
        $this->logInfo("Hook actionProductUpdate chamado");
        $this->sendRequestHTTP('actionProductUpdate', $params);
    }

    public function hookActionProductDelete($params) {
        $this->logInfo("Hook actionProductDelete chamado");
        $this->sendRequestHTTP('actionProductDelete', $params);
    }

    public function hookActionValidateOrder($params) {
        $this->logInfo("Hook actionValidateOrder chamado");
        $this->sendRequestHTTP('actionValidateOrder', $params);
    }

    public function hookActionOrderStatusUpdate($params) {
        $this->logInfo("Hook actionOrderStatusUpdate chamado");
        $this->sendRequestHTTP('actionOrderStatusUpdate', $params);
    }

    public function hookActionCartSave($params) {
        $this->logInfo("Hook actionCartSave chamado");
        $this->sendRequestHTTP('actionCartSave', $params);
    }

    public function hookActionBeforeCartUpdateQty($params) {
        $this->logInfo("Hook actionBeforeCartUpdateQty chamado");
        $this->sendRequestHTTP('actionBeforeCartUpdateQty', $params);
    }

    public function hookActionCustomerAccountAdd($params) {
        $this->logInfo("Hook actionCustomerAccountAdd chamado");
        $this->sendRequestHTTP('actionCustomerAccountAdd', $params);
    }

    public function hookActionCustomerLoginAfter($params) {
        $this->logInfo("Hook actionCustomerLoginAfter chamado");
        $this->sendRequestHTTP('actionCustomerLoginAfter', $params);
    }

    public function hookActionCustomerLogoutAfter($params) {
        $this->logInfo("Hook actionCustomerLogoutAfter chamado");
        $this->sendRequestHTTP('actionCustomerLogoutAfter', $params);
    }

    public function hookActionPaymentConfirmation($params) {
        $this->logInfo("Hook actionPaymentConfirmation chamado");
        $this->sendRequestHTTP('actionPaymentConfirmation', $params);
    }

    private function addConfiguration() {
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
                'actionCartSave' => 'cart/save',
                'actionBeforeCartUpdateQty' => 'cart/update-qty',
                'actionCustomerAccountAdd' => 'customer/add',
                'actionCustomerLoginAfter' => 'customer/login',
                'actionCustomerLogoutAfter' => 'customer/logout',
                'actionPaymentConfirmation' => 'payment/confirmation',
            ]));
        }
    }
}
