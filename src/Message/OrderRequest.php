<?php namespace Omnipay\PagSeguro\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\ItemBag;


class OrderRequest extends AbstractRequest
{

    protected $resource = 'orders';
    protected $shippingType = '3'; // 3 é para metodo indefinido
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */

    public function getData()
    {
        $this->validate('currency', 'order_id');
        $card = $this->getCard();

        $data = [
            "reference_id"=> $this->getOrderId(),
            "customer" => $this->getCustomerData(),
            "items"=> $this->getItemsData(),
            //"qr_codes" => ["amount"=> ["value"=> $this->getAmountInteger()]],
            "shipping" => $this->getShippingData(),
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
            ],
            "charges"=> $this->getChargesData()
        ];

        //print_r($data);
        return $data;
    }
}
