<?php namespace Omnipay\PagSeguro\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * Pagarme Response
 *
 * This is the response class for all Pagarme requests.
 *
 * @see \Omnipay\Pagarme\Gateway
 */
class Response extends AbstractResponse
{
    /**
     * Is the transaction successful?
     *
     * @return bool
     */
    public function isSuccessful()
    {
        //$result = $this->data;
        if(isset($this->data['error']) || isset($this->data['error_messages']))
            return false;

        //if(isset($this->data['result']) && $this->data['result'] === 'OK')
        //    return true;

        if ((isset($this->data['created_at']) && isset($this->data['id'])) || (isset($this->data['code']) && isset($this->data['date'])) || (@reset($this->data) === 'OK')) {
            return true;
        }

        return false;
    }

    /**
     * Get the transaction reference.
     *
     * @return string|null
     */
    public function getTransactionID()
    {
        if(isset($this->data['id']))
            return @$this->data['id'];

        if(isset($this->data['txid']))
            return @$this->data['txid'];

        return @$this->data['code'];
    }

    public function getTransactionAuthorizationCode()
    {
        if(isset($this->data['id']))
            return @$this->data['id'];

        if(isset($this->data['txid']))
            return @$this->data['txid'];

        return @$this->data['code'];
    }

    public function getStatus()
    {
        $status = null;
        if(isset($this->data['status']))
            $status = @$this->data['status'];
        else
        {
            if(isset($this->data['charges']))
                $status = @$this->data['charges'][0]['status'];
        }

        return $status;
    }

    public function isPaid()
    {
        $status = $this->getStatus();
        return strcmp($status, "PAID")==0 || strcmp($status, "CONCLUIDA")==0;
    }

    public function isAuthorized()
    {
        $status = $this->getStatus();
        return strcmp($status, "AUTHORIZED")==0;
    }

    public function isPending()
    {
        $status = $this->getStatus();
        return strcmp($status, "WAITING")==0 || strcmp($status, "ATIVA")==0 || strcmp($status, "EM_PROCESSAMENTO")==0;
    }

    public function isVoided()
    {
        $status = $this->getStatus();
        return strcmp($status, "CANCELED")==0 || strcmp($status, "DEVOLVIDO")==0;
    }

    /**
     * Get the error message from the response.
     *
     * Returns null if the request was successful.
     *
     * @return string|null
     */
    public function getMessage()
    {
        //print_r($this->data);
        if(isset($this->data['error']))
            return "{$this->data['error']['code']} - {$this->data['error']['message']}";

        if(isset($this->data['error_messages'])) {
            $message = "";
            if(isset($this->data['error_messages'][0]['message']))
                $message = @$this->data['error_messages'][0]['message'];
            if(isset($this->data['error_messages'][0]['description']))
                $message = @$this->data['error_messages'][0]['description']." => ".@$this->data['error_messages'][0]['parameter_name'];

            return "{$this->data['error_messages'][0]['code']} - $message";
        }

        return null;
    }

    //https://documenter.getpostman.com/view/10863174/TVetc6HV
    //https://dev.pagseguro.uol.com.br/page/passo-a-passo-cobrando-com-boleto
    public function getBoleto() // TODO: refazer  https://dev.pagseguro.uol.com.br/reference/charge-boleto
    {
        $data = $this->getData();
        $boleto = array();
        $boleto['boleto_url'] = @$data['links'][1]['href'];
        $boleto['boleto_url_pdf'] = @$data['links'][1]['href'];
        $boleto['boleto_barcode'] = @$data['payment_method']['boleto']['barcode'];
        $boleto['boleto_expiration_date'] = @$data['payment_method']['boleto']['due_date'];
        $boleto['boleto_valor'] = (@$data['amount']['value']*1.0)/100.0;
        //$boleto['boleto_transaction_id'] = @$data['id'];
        $boleto['boleto_transaction_id'] = @$data['payment_method']['boleto']['id'];
        //@$this->setTransactionReference(@$data['transaction_id']);

        return $boleto;
    }

    //https://dype.freshdesk.com/support/solutions/articles/3000091775-obtendo-dados-da-api-no-pagseguro
    //https://app.pipefy.com/public/form/z7Uas1lI
    //https://documenter.getpostman.com/view/10863174/TVetc6HV
    //https://documenter.getpostman.com/view/10863174/TVetc6HV#049e1b5f-9686-4e92-81b3-fd850fe1e493
    public function getPix()//TODO: refazer   https://dev.pagseguro.uol.com.br/reference/pix-intro
    {
        $data = $this->getData();
        $pix = array();
        $pix['pix_qrcodebase64image'] = self::getBase64ImageFromUrl(@$data['urlImagemQrCode']);
        $pix['pix_qrcodestring'] = @$data['pixCopiaECola'];//@$data['location']
        $pix['pix_valor'] = (@$data['valor']['original']*1.0);
        $pix['pix_transaction_id'] = @$data['txid'];

        return $pix;
    }

    public function getBase64ImageFromUrl($url)
    {
        $type = pathinfo($url, PATHINFO_EXTENSION);
        if(strcmp($type, 'svg')==0)
            $type = 'svg+xml';
        $data = file_get_contents($url);
        if (!$data)
            return NULL;

        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }

}