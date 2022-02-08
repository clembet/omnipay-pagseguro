<?php

namespace Omnipay\PagSeguro\Message;

// https://dev.pagseguro.uol.com.br/reference/webhooks-order

class NotificationRequest extends AbstractRequest
{
    protected $resource = 'transactions/notifications';
    protected $version = '3';
    protected $requestMethod = 'GET';

    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        return parent::getData();
    }

    public function getNotificationType()
    {
        return $this->getParameter('notificationType');
    }

    public function setNotificationType($value)
    {
        return $this->setParameter('notificationType', $value);
    }

    public function setNotificationCode($value)
    {
        return $this->setParameter('notificationCode', $value);
    }

    public function getNotificationCode()
    {
        return $this->getParameter('notificationCode');
    }

    public function sendData($data)
    {
        $this->validate('notificationCode');

        $url = sprintf(
            '%s/%s?%s',
            $this->getEndpoint(),
            $this->getNotificationCode(),
            http_build_query($data, '', '&')
        );

        print $url."\n\n";
        $httpResponse = $this->httpClient->request($this->getMethod(), $url, ['Content-Type' => 'application/x-www-form-urlencoded']);
        $xml          = @simplexml_load_string($httpResponse->getBody()->getContents(), 'SimpleXMLElement', LIBXML_NOCDATA);

        return $this->createResponse(@$this->xml2array($xml));
    }
}
