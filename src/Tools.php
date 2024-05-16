<?php

namespace WFPay\Common;

use Exception;

/**
 * Classe Tools
 *
 * Classe responsável pela comunicação com a API do WFPay
 *
 * @category  WFPay
 * @package   WFPay\Common\Tools
 * @author    Diego Almeida <diego.feres82 at gmail dot com>
 * @copyright 2022 WFPay
 * @license   https://opensource.org/licenses/MIT MIT
 */
class Tools
{
    /**
     * URL base para comunicação com a API
     *
     * @var string
     */
    public static $API_URL = [
        1 => 'https://api.wfpay.com.br/api',
        2 => 'http://api.dev.wfpay.com.br/api',
        3 => 'https://api.sandbox.wfpay.com.br/api'
    ];

    /**
     * Variável responsável por armazenar os dados a serem utilizados para comunicação com a API
     * Dados como token, ambiente(produção ou homologação) e debug(true|false)
     *
     * @var array
     */
    private $config = [
        'token' => '',
        'environment' => '',
        'debug' => false,
        'upload' => false,
        'decode' => true
    ];

    /**
     * Metodo contrutor da classe
     *
     * @param string $token Token inicial da classe
     * @param boolean $environment Define o ambiente: 1 - Produção, 2 - Local ou 3 - Sandbox
     */
    public function __construct(string $token = '', int $environment = 1)
    {
        $this->setToken($token);
        $this->setEnvironment($environment);
    }

    /**
     * Define se a classe realizará um upload
     *
     * @param bool $isUpload Boleano para definir se é upload ou não
     *
     * @access public
     * @return void
     */
    public function setUpload(bool $isUpload) :void
    {
        $this->config['upload'] = $isUpload;
    }

    /**
     * Define se a classe realizará o decode do retorno
     *
     * @param bool $decode Boleano para definir se fa decode ou não
     *
     * @access public
     * @return void
     */
    public function setDecode(bool $decode) :void
    {
        $this->config['decode'] = $decode;
    }

    /**
     * Função responsável por definir se está em modo de debug ou não a comunicação com a API
     * Utilizado para pegar informações da requisição
     *
     * @param bool $isDebug Boleano para definir se é produção ou não
     *
     * @access public
     * @return void
     */
    public function setDebug(bool $isDebug) :void
    {
        $this->config['debug'] = $isDebug;
    }

    /**
     * Função responsável por definir o token a ser utilizado para comunicação com a API
     *
     * @param string $token Token para autenticação na API
     *
     * @access public
     * @return void
     */
    public function setToken(string $token) :void
    {
        $this->config['token'] = $token;
    }

    /**
     * Função responsável por setar o ambiente utilizado na API
     *
     * @param int $environment Ambiente API (1 - Produção | 2 - Local | 3 - Sandbox | 4 - Dusk)
     *
     * @access public
     * @return void
     */
    public function setEnvironment(int $environment) :void
    {
        if (in_array($environment, [1, 2, 3, 4])) {
            $this->config['environment'] = $environment;
        }
    }

    /**
     * Recupera se é upload ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getUpload() : bool
    {
        return $this->config['upload'];
    }

    /**
     * Recupera se faz decode ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getDecode() : bool
    {
        return $this->config['decode'];
    }

    /**
     * Retorna o token utilizado para comunicação com a API
     *
     * @access public
     * @return string
     */
    public function getToken() :string
    {
        return $this->config['token'];
    }

    /**
     * Recupera o ambiente setado para comunicação com a API
     *
     * @access public
     * @return int
     */
    public function getEnvironment() :int
    {
        return $this->config['environment'];
    }

    /**
     * Retorna os cabeçalhos padrão para comunicação com a API
     *
     * @access private
     * @return array
     */
    private function getDefaultHeaders() :array
    {
        $headers = [
            'Authorization: Bearer '.$this->config['token'],
            'Accept: application/json',
        ];

        if (!$this->config['upload']) {
            $headers[] = 'Content-Type: application/json';
        } else {
            $headers[] = 'Content-Type: multipart/form-data';
        }
        return $headers;
    }

    /**
     * Função responsável por listar as empresas existentes no WFPay
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaEmpresas(array $params = []) :array
    {
        try {
            $dados = $this->get("companies", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cadastrar uma empresa
     *
     * @param array $data Dados da empresa
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function cadastraEmpresa(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("companies", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por requisitar token de acesso
     *
     * @param array $data CPF/CNPJ da empresa
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function tokenEmpresa(string $cpfcnpj, array $params = []) :array
    {
        if(empty($cpfcnpj)) {
            throw new Exception("CPF/CNPJ não informado", 1);
        }

        $params[] = [
            'name' => 'cpfcnpj',
            'value' => $cpfcnpj
        ];

        try {
            $dados = $this->get("companies/token-api", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por retornar o saldo atual
     *
     * @access public
     * @return array
     */
    public function consultaSaldo(array $params = []): array
    {
        try {
            return $this->get('companies/balance', $params);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por retornar o status atual da empresa
     *
     * @access public
     * @return array
     */
    public function consultaStatusEmpresa(array $params = []): array
    {
        try {
            return $this->get('company_status', $params);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por verificar se o ID de uma determinada categoria está sendo usada
     * na tabela easy_tax_categories no campo easy_category_id
     *
     * @param integer $id ID da categoria
     *
     * @access public
     * @return boolean
     */
    public function buscaCategoria(int $id, array $params = []) :array
    {
        try {
            $dados = $this->get("categories/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar o PJBank no WFPay
     *
     * @param array $params Parametros adicionais para a requisição
     * @param string $cpfcnpj CPF/CNPJ
     * @param integer $charge_id ID do charge
     *
     * @access public
     * @return boolean
     */
    public function buscaPjbank(string $cpfcnpj, int $charge_id, array $params = []) :array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'company_id';
            }, ARRAY_FILTER_USE_BOTH);

            if (!empty($cpfcnpj)) {
                $params[] = [
                    'name' => 'cpfcnpj',
                    'value' => $cpfcnpj
                ];
            }

            $dados = $this->get("pjbank/$charge_id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar as transactions no WFPay
     *
     * @param array $params Parametros adicionais para a requisição
     * @param string $cpfcnpj CPF/CNPJ
     * @param integer $transaction_id ID do transaction
     * @access public
     * @return boolean
     */
    public function buscaTransactions(string $cpfcnpj, int $transaction_id, array $params = []) :array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'company_id';
            }, ARRAY_FILTER_USE_BOTH);

            if (!empty($cpfcnpj)) {
                $params[] = [
                    'name' => 'cpfcnpj',
                    'value' => $cpfcnpj
                ];
            }

            $dados = $this->get("transactions/$transaction_id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por retornar o resumo da empresa
     *
     * @access public
     * @return array
     */
    public function resumo(array $params = []): array
    {
        try {
            return $this->get('dashboard', $params);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar os usuários da empresa
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaUsuarios(array $params = []) :array
    {
        try {
            $dados = $this->get("users", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar as informações de um usuário específico
     *
     * @param int $id ID do usuário
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaUsuario(int $id, array $params = []) :array
    {
        try {
            $dados = $this->get("users/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cadastrar um novo usuário
     *
     * @param array $data Dados do usuário
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function cadastraUsuario(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("users", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por atualizar um usuário
     *
     * @param int $id ID do usuário
     * @param array $data Dados do usuário
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function atualizaUsuario(int $id, array $data, array $params = []) :array
    {
        try {
            $dados = $this->put("users/$id", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por excluir um usuário
     *
     * @param int $id ID do usuário
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function removeUsuario(int $id, array $params = []) :array
    {
        try {
            $dados = $this->delete("users/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar os clientes da empresa
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaClientes(array $params = []) :array
    {
        try {
            $dados = $this->get("customers", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar as informações de um cliente específico
     *
     * @param int $id ID do cliente
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaCliente(int $id, array $params = []) :array
    {
        try {
            $dados = $this->get("customers/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cadastrar um novo cliente
     *
     * @param array $data Dados do cliente
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function cadastraCliente(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("customers", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por atualizar um cliente
     *
     * @param int $id ID do cliente
     * @param array $data Dados do cliente
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function atualizaCliente(int $id, array $data, array $params = []) :array
    {
        try {
            $dados = $this->put("customers/$id", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por excluir um cliente
     *
     * @param int $id ID do cliente
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function removeCliente(int $id, array $params = []) :array
    {
        try {
            $dados = $this->delete("customers/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar as cobranças da empresa
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaCobrancas(array $params = []) :array
    {
        try {
            $dados = $this->get("charges", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar uma cobrança específica
     *
     * @param int $id ID da cobrança
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaCobranca(int $id, array $params = []) :array
    {
        try {
            $dados = $this->get("charges/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cadastrar uma nova cobrança
     *
     * @param array $data Dados da cobrança
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function cadastraCobranca(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("charges", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por atualizar uma cobranca
     *
     * @param int $id ID da cobranca
     * @param array $data Dados da cobrança
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function atualizaCobranca(int $id, array $data, array $params = []) :array
    {
        try {
            $dados = $this->put("charges/$id", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por excluir uma cobranca
     *
     * @param int $id
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function removeCobranca(int $id, array $params = []) :array
    {
        try {
            $dados = $this->delete("charges/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por confirmar um recebimento em dinheiro
     *
     * @param int $id ID da cobrança
     * @param array $data Dados para o recebimento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function confirmaRecebimentoDinheiro(int $id, array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("charges/$id/receive_in_cash", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar os parcelamentos da empresa
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaParcelamentos(array $params = []) :array
    {
        try {
            $dados = $this->get("installments", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar um parcelamento específico
     *
     * @param int $id ID da parcelamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaParcelamento(int $id, array $params = []) :array
    {
        try {
            $dados = $this->get("installments/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cadastrar um novo parcelamento
     *
     * @param array $data Dados da parcelamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function cadastraParcelamento(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("installments", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por atualizar um parcelamento
     *
     * @param int $id ID da cobranca
     * @param array $data Dados da parcelamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function atualizaParcelamento(int $id, array $data, array $params = []) :array
    {
        try {
            $dados = $this->put("installments/$id", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por excluir um parcelamento
     *
     * @param int $id
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function removeParcelamento(int $id, array $params = []) :array
    {
        try {
            $dados = $this->delete("installments/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar as assinaturas da empresa
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaAssinaturas(array $params = []) :array
    {
        try {
            $dados = $this->get("subscriptions", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar uma assinatura específica
     *
     * @param int $id ID da assinatura
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaAssinatura(int $id, array $params = []) :array
    {
        try {
            $dados = $this->get("subscriptions/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cadastrar uma nova assinatura
     *
     * @param array $data Dados da assinatura
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function cadastraAssinatura(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("subscriptions", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por atualizar uma assinatura
     *
     * @param int $id ID da cobranca
     * @param array $data Dados da assinatura
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function atualizaAssinatura(int $id, array $data, array $params = []) :array
    {
        try {
            $dados = $this->put("subscriptions/$id", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por excluir uma assinatura
     *
     * @param int $id
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function removeAssinatura(int $id, array $params = []) :array
    {
        try {
            $dados = $this->delete("subscriptions/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar os links de pagamentos da empresa
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaLinksPagamentos(array $params = []) :array
    {
        try {
            $dados = $this->get("payment_links", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar as informações de um link de pagamento específico
     *
     * @param int $id ID do link de pagamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaLinkPagamento(int $id, array $params = []) :array
    {
        try {
            $dados = $this->get("payment_links/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cadastrar um novo link de pagamento
     *
     * @param array $data Dados do link de pagamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function cadastraLinkPagamento(array $data, array $params = []) :array
    {
        //Pega a configuração atual de upload
        $upload = $this->getUpload();

        try {
            $this->setUpload(true);
            $dados = $this->post("payment_links", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        } finally {
            $this->setUpload($upload);
        }
    }

    /**
     * Função responsável por atualizar um link de pagamento
     *
     * @param int $id ID do link de pagamento
     * @param array $data Dados do link de pagamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function atualizaLinkPagamento(int $id, array $data, array $params = []) :array
    {
        try {
            $dados = $this->put("payment_links/$id", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por excluir um link de pagamento
     *
     * @param int $id ID do link de pagamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function removeLinkPagamento(int $id, array $params = []) :array
    {
        try {
            $dados = $this->delete("payment_links/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por adicionar uma imagem a um link de pagamento
     *
     * @param int $id ID do link de pagamento
     * @param \CURLFile $image Imagem carregada pelo CURLFile
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function uploadImgLinkPagamento(int $id, \CURLFile $image, array $params = []) :array
    {
        //Pega a configuração atual de upload
        $upload = $this->getUpload();

        try {
            $this->setUpload(true);
            $dados = $this->post("payment_links/$id/images", [ 'image' => $image ], $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        } finally {
            $this->setUpload($upload);
        }
    }

    /**
     * Função responsável por definir uma imagem de um link de pagamento como principal
     *
     * @param int $id ID do link de pagamento
     * @param int $imageId ID da imagem
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function definePrincipalImgLinkPagamento(int $id, int $imageId, array $params = []) :array
    {
        try {
            $dados = $this->post("payment_links/$id/images/$imageId/main", [], $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por excluir uma imagem de um link de pagamento
     *
     * @param int $id ID do link de pagamento
     * @param int $imageId ID da imagem
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function removeImgLinkPagamento(int $id, int $imageId, array $params = []) :array
    {
        try {
            $dados = $this->delete("payment_links/$id/images/$imageId", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por baixar uma imagem de um link de pagamento
     *
     * @param int $id ID do link de pagamento
     * @param int $imageId ID da imagem
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function downloadImgLinkPagamento(int $id, int $imageId, array $params = []) :array
    {
        try {
            $dados = $this->get("payment_links/$id/images/$imageId/download", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por alterar o status entre ativo e inativo de um link de pagamento
     *
     * @param int $id ID do link de pagamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function alteraStatusLinkPagamento(int $id, array $params = []) :array
    {
        try {
            $dados = $this->post("payment_links/$id/alter_status", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar as contas bancárias da empresa
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaContasBancarias(array $params = []) :array
    {
        try {
            $dados = $this->get("accounts", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar uma conta bancária específica
     *
     * @param int $id ID da conta bancária
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaContaBancaria(int $id, array $params = []) :array
    {
        try {
            $dados = $this->get("accounts/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cadastrar uma nova conta bancária
     *
     * @param array $data Dados da conta bancária
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function cadastraContaBancaria(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("accounts", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por atualizar uma cobranca
     *
     * @param int $id ID da cobranca
     * @param array $data Dados da conta bancária
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function atualizaContaBancaria(int $id, array $data, array $params = []) :array
    {
        try {
            $dados = $this->put("accounts/$id", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por excluir uma cobranca
     *
     * @param int $id
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function removeContaBancaria(int $id, array $params = []) :array
    {
        try {
            $dados = $this->delete("accounts/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar os bancos disponíveis
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaBancos(array $params = []) :array
    {
        try {
            $dados = $this->get("banks", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar os pagamentos da empresa
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaPagamentos(array $params = []) :array
    {
        try {
            $dados = $this->get("payments", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar as informações de um pagamento específico
     *
     * @param int $id ID do pagamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaPagamento(int $id, array $params = []) :array
    {
        try {
            $dados = $this->get("payments/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por solicitar um novo pagamento
     *
     * @param array $data Dados do pagamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function solicitaPagamento(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("payments", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cancelar um pagamento
     *
     * @param int $id ID do pagamento
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function cancelaPagamento(int $id, array $params = []) :array
    {
        try {
            $dados = $this->delete("payments/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por ler uma linha digitável
     *
     * @param array $$data Dados para leitura da linha digitável
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function lerLinhaDigitavel(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("payments/read-digitable-line", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar as transferências da empresa
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function listaTransferencias(array $params = []) :array
    {
        try {
            $dados = $this->get("transfers", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar as informações de uma transferência específica
     *
     * @param int $id ID do transferência
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaTransferencia(int $id, array $params = []) :array
    {
        try {
            $dados = $this->get("transfers/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por solicitar um novo transferência
     *
     * @param $data Dados do transferência
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function solicitaTransferencia(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("transfers", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cancelar uma transferência
     *
     * @param int $id ID do transferência
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function cancelaTransferencia(int $id, array $params = []) :array
    {
        try {
            $dados = $this->delete("transfers/$id", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por calcular as taxas e prazo de uma transferência
     *
     * @param $data Dados do transferência
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function calculaTransferencia(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("transfers/calcule", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por retornar os dados do extrato bancário
     *
     * @param array $params Parametros adicionais para a requisição
     * @param string $cpfcnpj Cpf ou Cnpj
     *
     * @access public
     * @return array
     */
    public function consultaExtrato(string $cpfcnpj, array $params = []) :array
    {
        try {
            if (!empty($cpfcnpj)) {
                $params[] = [
                    'name' => 'cpfcnpj',
                    'value' => $cpfcnpj
                ];
            }

            $dados = $this->get("extracts", $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por atualizar o webhook de cobranças
     *
     * @param $data Dados do webhook
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function webhookCobranca(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("webhooks/charges", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por atualizar o webhook de transferências
     *
     * @param $data Dados do webhook
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function webhookTransferencia(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("webhooks/transfers", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por atualizar o webhook de pagamentos
     *
     * @param $data Dados do webhook
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function webhookPagamento(array $data, array $params = []) :array
    {
        try {
            $dados = $this->post("webhooks/payments", $data, $params);

            if ($dados['httpCode'] >= 200 && $dados['httpCode'] <= 299) {
                return $dados;
            }

            if (isset($dados['body']->message)) {
                throw new Exception($dados['body']->message, 1);
            }

            if (isset($dados['body']->errors)) {
                throw new Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Execute a GET Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function get(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a POST Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function post(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => !$this->config['upload'] ? json_encode($body) : $this->convertToFormData($body),
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PUT Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function put(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($body)
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a DELETE Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function delete(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "DELETE"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a OPTION Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function options(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_CUSTOMREQUEST => "OPTIONS"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Função responsável por realizar a requisição e devolver os dados
     *
     * @param string $path Rota a ser acessada
     * @param array $opts Opções do CURL
     * @param array $params Parametros query a serem passados para requisição
     *
     * @access protected
     * @return array
     */
    protected function execute(string $path, array $opts = [], array $params = []) :array
    {
        if (!preg_match("/^\//", $path)) {
            $path = '/' . $path;
        }

        $url = self::$API_URL[$this->config['environment']].$path;

        $curlC = curl_init();

        if (!empty($opts)) {
            curl_setopt_array($curlC, $opts);
        }

        if (!empty($params)) {
            $paramsJoined = [];

            foreach ($params as $param) {
                if (isset($param['name']) && !empty($param['name']) && isset($param['value']) && (!empty($param['value']) || $param['value'] == 0)) {
                    $paramsJoined[] = urlencode($param['name'])."=".urlencode($param['value']);
                }
            }

            if (!empty($paramsJoined)) {
                $params = '?'.implode('&', $paramsJoined);
                $url = $url.$params;
            }
        }

        curl_setopt($curlC, CURLOPT_URL, $url);
        curl_setopt($curlC, CURLOPT_RETURNTRANSFER, true);
        if (!empty($dados)) {
            curl_setopt($curlC, CURLOPT_POSTFIELDS, json_encode($dados));
        }
        $retorno = curl_exec($curlC);
        $info = curl_getinfo($curlC);
        $return["body"] = ($this->config['decode'] || !$this->config['decode'] && $info['http_code'] != '200') ? json_decode($retorno) : $retorno;
        $return["httpCode"] = curl_getinfo($curlC, CURLINFO_HTTP_CODE);
        if ($this->config['debug']) {
            $return['info'] = curl_getinfo($curlC);
        }
        curl_close($curlC);

        return $return;
    }

    /**
     * Função responsável por montar o corpo de uma requisição no formato aceito pelo FormData
     */
    private function convertToFormData($data)
    {
        $dados = [];

        $recursive = false;
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $dados[$key] = $value;
            } else {
                foreach ($value as $subkey => $subvalue) {
                    $dados[$key.'['.$subkey.']'] = $subvalue;

                    if (is_array($subvalue)) {
                        $recursive = true;
                    }
                }
            }
        }

        if ($recursive) {
            return $this->convertToFormData($dados);
        }

        return $dados;
    }
}
