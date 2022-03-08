<?php namespace Omnipay\PagSeguro\Message;


class CaptureRequest extends AbstractRequest
{
    protected $resource = 'charges';
    protected $requestMethod = 'POST';

    public function getData()
    {
        $this->validate('transactionId', 'amount');
        //$data = parent::getData();
        $data['amount']['value'] = $this->getAmountInteger();

        return $data;
    }

    public function sendData($data)
    {
        $this->validate('transactionId');

        $url = sprintf(
            '%s/%s/capture',
            $this->getEndpoint(),
            $this->getTransactionID()
        );

        $headers = [
            'Authorization' => $this->getToken(),
            'Content-Type' => 'application/json'
        ];

        $method = $this->requestMethod;

        //print_r([$method, $url, $headers, $data]);
        $response = $this->httpClient->request(
            $method,
            $url,
            $headers,
            json_encode($data)
        //http_build_query($data, '', '&')
        );

        //print_r($response->getBody()->getContents());
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

        return $this->response = $this->createResponse(@$array);
    }
}
