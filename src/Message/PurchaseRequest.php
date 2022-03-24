<?php namespace Omnipay\PagSeguro\Message;


class PurchaseRequest extends AuthorizeRequest
{
    public function getData()
    {
        $data = parent::getData();
        if(strcmp(strtolower($this->getPaymentType()), "creditcard")==0)
            $data["payment_method"]["capture"] = true; // quando capture=false só faz a análise de limite sem aprovação da transação

        return $data;
    }
}
