<?php

namespace Omnipay\PagSeguro\Message;

/**
 * PagSeguro Refund Request
 *
 * https://dev.pagseguro.uol.com.br/reference/charge-refund
 *
 * <code>
 *   // Do a refund transaction on the gateway
 *   $transaction = $gateway->void(array(
 *       'amount'                   => '10.00',
 *       'transactionId'     => $transactionCode,
 *   ));
 *
 *   $response = $transaction->send();
 *   if ($response->isSuccessful()) {
 *   }
 * </code>
 */

class VoidRequest extends AbstractRequest   // /cancels é utilizado em pagamentos om cartão com o status em AUTHORIZED, ou seja para transações authorized (2 etapas)
{
    protected $resource = 'charges';
    protected $requestMethod = 'POST';

    public function getData()
    {
        $this->validate('amount');
        //$data = parent::getData();
        $data['amount']['value'] = $this->getAmountInteger();

        return $data;
    }

    //TODO: prece que o PIX tem um endpoint diferente: https://dev.pagseguro.uol.com.br/reference/pix-refund-charge
    //https://documenter.getpostman.com/view/10863174/TVetc6HV#b1b25c1c-f4f0-41d7-9413-7ed67ba64582
    public function sendData($data)
    {
        $this->validate('transactionId');

        $url = sprintf(
            '%s/%s/cancel',
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
