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
            //"qr_codes" => ["amount"=> ["value"=> $this->getAmount()]],
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
                    "value"=> $this->getAmount(),
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

    public function getShippingType()
    {
        return $this->getParameter('shippingType');
    }

    public function setShippingType($value)
    {
        return $this->setParameter('shippingType', $value);
    }

    public function getShippingCost()
    {
        return $this->getParameter('shippingCost');
    }

    public function setShippingCost($value)
    {
        return $this->setParameter('shippingCost', $value);
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

    public function getAmount()
    {
        return (int)round((parent::getAmount()*100.0), 0);
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
}
