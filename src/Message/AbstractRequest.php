<?php namespace Omnipay\PagSeguro\Message;


use Omnipay\Common\Exception\InvalidRequestException;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    protected $liveEndpoint = 'https://api.pagseguro.com';
    protected $testEndpoint = 'https://sandbox.api.pagseguro.com';
    protected $requestMethod = 'POST';
    protected $resource = '';
    protected $version = '4';

    public function sendData($data)
    {
        $method = $this->requestMethod;
        $url = $this->getEndpoint();

        $headers = [
            'Authorization' => $this->getToken(),
            'Content-Type' => 'application/json',
            'x-idempotency-key'
        ];

        //print_r([$method, $url, $headers, json_encode($data)]);
        $response = $this->httpClient->request(
            $method,
            $url,
            $headers,
            $this->toJSON($data)
            //http_build_query($data, '', '&')
        );
        //print_r($response);
        //print_r($data);

        if ($response->getStatusCode() != 200 && $response->getStatusCode() != 201 && $response->getStatusCode() != 400) {
            $array = [
                'error' => [
                    'code' => $response->getStatusCode(),
                    'message' => $response->getReasonPhrase()
                ]
            ];

            return $this->response = $this->createResponse($array);
        }

        $json = $response->getBody()->getContents();
        $array = @json_decode($json, true);
        //print_r($array);

        return $this->response = $this->createResponse(@$array);
    }

    protected function setBaseEndpoint($value)
    {
        $this->baseEndpoint = $value;
    }

    public function __get($name)
    {
        return $this->getParameter($name);
    }

    protected function setRequestMethod($value)
    {
        return $this->requestMethod = $value;
    }

    protected function decode($data)
    {
        return json_decode($data, true);
    }

    public function getEmail()
    {
        return $this->getParameter('email');
    }

    public function setEmail($value)
    {
        return $this->setParameter('email', $value);
    }

    public function getToken()
    {
        return $this->getParameter('token');
    }

    public function setToken($value)
    {
        return $this->setParameter('token', $value);
    }

    public function setOrderId($value)
    {
        return $this->setParameter('order_id', $value);
    }
    public function getOrderId()
    {
        return $this->getParameter('order_id');
    }

    public function setInstallments($value)
    {
        return $this->setParameter('installments', $value);
    }
    public function getInstallments()
    {
        return $this->getParameter('installments');
    }

    public function setSoftDescriptor($value)
    {
        return $this->setParameter('soft_descriptor', $value);
    }
    public function getSoftDescriptor()
    {
        return $this->getParameter('soft_descriptor');
    }

    public function getCustomerName()
    {
        return $this->getParameter('customer_name');
    }

    public function setCustomerName($value)
    {
        $this->setParameter('customer_name', $value);
    }

    public function getCustomer()
    {
        return $this->getParameter('customer');
    }

    public function setCustomer($value)
    {
        return $this->setParameter('customer', $value);
    }

    public function setExtraAmount($value)
    {
        return $this->setParameter('extraAmount', $value);
    }

    public function getTransactionID()
    {
        return $this->getParameter('transactionId');
    }

    public function setTransactionID($value)
    {
        return $this->setParameter('transactionId', $value);
    }

    public function getExtraAmount()//TODO: refazer
    {
        $extraAmount = $this->getParameter('extraAmount');

        if ($extraAmount !== null && $extraAmount !== 0) {
            if ($this->getCurrencyDecimalPlaces() > 0) {
                if (is_int($extraAmount) || (is_string($extraAmount) && strpos((string)$extraAmount, '.') === false)) {
                    throw new InvalidRequestException(
                        'Please specify extra amount as a string or float, with decimal places.'
                    );
                }
            }

            // Check for rounding that may occur if too many significant decimal digits are supplied.
            $decimal_count = strlen(substr(strrchr(sprintf('%.8g', $extraAmount), '.'), 1));
            if ($decimal_count > $this->getCurrencyDecimalPlaces()) {
                throw new InvalidRequestException('Amount precision is too high for currency.');
            }

            return $this->formatCurrency($extraAmount);
        }
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getMethod()
    {
        return $this->requestMethod;
    }

    public function getPaymentType()
    {
        return $this->getParameter('paymentType');
    }

    public function setPaymentType($value)
    {
        $this->setParameter('paymentType', $value);
    }

    public function getChavePix()
    {
        return $this->getParameter('chavePix');
    }

    public function setChavePix($value)
    {
        $this->setParameter('chavePix', $value);
    }

    public function getDueDate()
    {
        $dueDate = $this->getParameter('dueDate');
        if($dueDate)
            return $dueDate;

        $time = localtime(time());
        $ano = $time[5]+1900;
        $mes = $time[4]+1+1;
        $dia = 1;// $time[3];
        if($mes>12)
        {
            $mes=1;
            ++$ano;
        }

        $dueDate = sprintf("%04d-%02d-%02d", $ano, $mes, $dia);
        $this->setDueDate($dueDate);

        return $dueDate;
    }

    public function setDueDate($value)
    {
        return $this->setParameter('dueDate', $value);
    }

    protected function createResponse($data)
    {
        return $this->response = new Response($this, $data);
    }

    protected function getEndpoint()
    {
        $endPoint = ($this->getTestMode()?$this->testEndpoint:$this->liveEndpoint);
        return  "{$endPoint}/{$this->getResource()}";
    }

    public function getData()
    {
        $this->validate('email', 'token');

        return [
            'email' => $this->getEmail(),
            'token' => $this->getToken(),
        ];
    }

    public function toJSON($data, $options = 0)
    {
        if (version_compare(phpversion(), '5.4.0', '>=') === true) {
            return json_encode($data, $options | 64);
        }
        return str_replace('\\/', '/', json_encode($data, $options));
    }

    public function getDataCreditCard()
    {
        $this->validate('currency', 'order_id');
        $card = $this->getCard();

        $data = [
            "reference_id"=> $this->getOrderId(),
            "description"=> "Compra em ".$this->getSoftDescriptor(),
            "amount"=> [
                "value"=> $this->getAmountInteger(),
                "currency"=> $this->getCurrency()
            ],
            "payment_method"=> [
                "type"=> "CREDIT_CARD",
                "installments"=> $this->getInstallments(),
                "capture"=> false, // quando capture=false só faz a análise de limite sem aprovação da transação
                "soft_descriptor"=> $this->getSoftDescriptor(),
                "card"=> [
                    "number"=> $card->getNumber(),
                    "exp_month"=> str_pad($card->getExpiryMonth(), 2, 0, STR_PAD_LEFT),
                    "exp_year"=> $card->getExpiryYear(),
                    "security_code"=> $card->getCvv(),
                    "holder"=> [
                        "name"=> $card->getName()
                    ]
                ]
            ],
            "notification_urls" => [
                $this->getNotifyUrl()
            ]
        ];

        return $data;
    }

    //https://dev.pagseguro.uol.com.br/v1.0/reference/api-boleto-ambientes-disponiveis
    public function getDataBoleto() // https://dev.pagseguro.uol.com.br/reference/charge-boleto
    {
        $customer = $this->getCustomer();

        $data = [
            "reference_id"=> $this->getOrderId(),
            "description"=> "Compra em ".$this->getSoftDescriptor(),
            "amount"=> [
                "value"=> $this->getAmountInteger(),
                "currency"=> $this->getCurrency()
            ],
            "payment_method"=> [
                "type"=> "BOLETO",
                "boleto"=> [
                    "due_date"=> $this->getDueDate(),
                    "instruction_lines"=> [
                        "line_1"=> "Não receber após o vencimento.",
                        "line_2"=> "Via PagSeguro"
                    ],
                    "holder"=> [
                        "name"=> $customer->getName(),
                        "tax_id"=> $customer->getDocumentNumber(),
                        "email"=> $customer->getEmail(),
                        "address"=> [
                            "street"=> $customer->getBillingAddress1(),
                            "number"=> $customer->getBillingNumber(),
                            "locality"=> $customer->getBillingDistrict(),
                            "city"=> $customer->getBillingCity(),
                            "region"=> $customer->getBillingState(),//"Sao Paulo",
                            "region_code"=> $customer->getBillingState(),//"SP"
                            "country"=> "Brasil",
                            "postal_code"=> $customer->getBillingPostcode()
                        ]
                      ]
                ]
            ],
            "notification_urls" => [
                $this->getNotifyUrl()
            ]
        ];



        return $data;
    }

    // TODO: https://dev.pagseguro.uol.com.br/reference/pix-charge-pay-sandbox
    public function getDataPix() // https://dev.pagseguro.uol.com.br/reference/pix-create-charge
    {
        $this->validate('chavePix');
        $customer = $this->getCustomer();

        $data = [
            "txid"=> $this->getOrderId(),
            "calendario"=> [
                    "expiracao"=> "86400" // em segundos
                ],
            "devedor"=> [
                    "cpf"=> $customer->getDocumentNumber(),
                    "nome"=> $customer->getName()
                ],
            "valor"=> [
                    "original"=> $this->getAmount()
                ],
            "chave"=> $this->getChavePix(), // O campo chave, determina a chave Pix registrada no DICT que será utilizada para endereçar a cobrança. Para fins de teste, em ambiente de Sandbox, qualquer chave é válida. A chave (CPF, CPNPJ, eMail, telefone, chave aleatória) pode ser cadastrada na área logada da sua conta PagSeguro, app ou web.
            "solicitacaoPagador"=> "Compra em ".$this->getSoftDescriptor(),
            "infoAdicionais"=> [
                    /*[
                        "nome"=> "Campo 1",
                        "valor"=> "Informação Adicional1 do PSP-Recebedor"
                    ],
                    [
                        "nome"=> "Campo 2",
                        "valor"=> "Informação Adicional2 do PSP-Recebedor"
                    ]*/
                ]
        ];

        return $data;
    }
}
