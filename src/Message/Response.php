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
    public function getTransactionReference()
    {
        if(isset($this->data['id']))
            return @$this->data['id'];

        return @$this->data['code'];
    }

    public function getTransactionAuthorizationCode()
    {
        if(isset($this->data['id']))
            return @$this->data['id'];

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
        return strcmp($status, "PAID")==0;
    }

    public function isAuthorized()
    {
        $status = $this->getStatus();
        return strcmp($status, "AUTHORIZED")==0;
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
}