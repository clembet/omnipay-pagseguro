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

    protected function getCustomerData()
    {
        $card = $this->getCard();
        return ["name"=> $card->getName(),
            "email"=> $card->getEmail(),
            "tax_id"=> $card->getHolderDocumentNumber(),
            "phones"=> [
                [
                    "country"=> "55",
                    "area"=> @substr($card->getPhone(), 0, 2),
                    "number"=> @substr($card->getPhone(), 2, 9),
                    "type"=> "MOBILE"
                ]
            ]
        ];
    }

    protected function getItemsData()
    {
        $data = [];
        $items = $this->getItems();
        if ($items) {
            $i = 1;
            foreach ($items as $n => $item) {
                $item_array = [];
                $item_array['reference_id'] = $i;
                $item_array['name'] = $item->getName();
                $item_array['quantity'] = (int)$item->getQuantity();
                $item_array['unit_amount'] = (int)round(($item->getPrice()*100.0), 0);

                $data[] = $item_array;
                ++$i;
            }
        }

        return $data;
    }

    protected function getShippingData()
    {
        $card = $this->getCard();

        return [
            "address"=> [
                "street"=> $card->getShippingAddress1(),
                "number"=> $card->getShippingNumber(),
                "complement"=> $card->getShippingAddress2(),
                "locality"=> $card->getShippingDistrict(),
                "city"=> $card->getShippingCity(),
                "region_code"=>$card->getShippingState(),
                "country"=> $card->getShippingCountry(),//BRA
                "postal_code"=> $card->getShippingPostcode()
            ]
        ];
    }

    protected function getChargesData()
    {
        $card = $this->getCard();
        return [[
            "reference_id"=> $this->getOrderId(),
            "description"=> "Compra em ".$this->getSoftDescriptor(),
            "amount"=> [
                "value"=> $this->getAmountInteger(),
                "currency"=> $this->getCurrency()
            ],
            "payment_method"=> [
                "type"=> "CREDIT_CARD",
                "installments"=> $this->getInstallments(),
                "capture"=> true, // quando capture=false só faz a análise de limite sem aprovação da transação
                "soft_descriptor"=> $this->getSoftDescriptor(),
                "card"=> [
                    "number"=> $card->getNumber(),
                    "exp_month"=> str_pad($card->getExpiryMonth(), 2, 0, STR_PAD_LEFT),
                    "exp_year"=> $card->getExpiryYear(),
                    "security_code"=> $card->getCvv(),
                    "holder"=> [
                        "name"=> $card->getName()
                    ],
                    "store"=> false
                ]
            ],
            "notification_urls"=> [
                $this->getNotifyUrl()
            ]
        ]];
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
}
