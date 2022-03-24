<?php namespace Omnipay\PagSeguro\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\ItemBag;

class AuthorizeRequest extends AbstractRequest
{

    protected $resource = 'charges';
    protected $shippingType = '3'; // 3 é para metodo indefinido
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */

    public function getData()
    {
        $this->validate('customer', 'paymentType');
        $data = [];
        switch(strtolower($this->getPaymentType()))
        {
            case 'creditcard':
                $data = $this->getDataCreditCard();
                $data["payment_method"]["capture"] = false; // quando capture=false só faz a análise de limite sem aprovação da transação
                break;

            case 'boleto':
                $data = $this->getDataBoleto();
                break;

            case 'pix':
                $data = $this->getDataPix();

                //TODO: validar
                //https://secure.sandbox.api.pagseguro.com/instant-payments/cob/{txid} => order_id
                $this->liveEndpoint = 'https://secure.api.pagseguro.com/instant-payments';
                $this->testEndpoint = 'https://secure.sandbox.api.pagseguro.com/instant-payments';
                $this->requestMethod = 'PUT'; //https://dev.pagseguro.uol.com.br/reference/pix-charge
                $this->resource = 'cob';
                $this->version = '2.1.0';
                break;

            default:
                $data = $this->getDataCreditCard();
        }

        return $data;
    }
}
