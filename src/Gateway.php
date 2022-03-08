<?php

namespace Omnipay\PagSeguro;

use Omnipay\Common\AbstractGateway;

/*
 * https://www.youtube.com/watch?v=T4MRhfO4DM4
 * https://dev.pagseguro.uol.com.br/reference/pagseguro-reference-intro
 * https://documenter.getpostman.com/view/10863174/TVetc6HV?_ga=2.30773242.478029196.1644772874-1592393499.1644547730#6135e4aa-4706-4107-a11a-bc300602a7fa
 * https://documenter.getpostman.com/view/10863174/TVetc6HV?_ga=2.30773242.478029196.1644772874-1592393499.1644547730#7584bfe6-29b8-44f1-8af0-bd6fb3308599
 */

/**
 * https://dev.pagseguro.uol.com.br/v1/reference/checkout-pagseguro-criacao-checkout-pagseguro
 * curl -X GET 'https://ws.pagseguro.uol.com.br/v3/transactions/16FE3415D3D31FCCC4BF3F873D265179?email=clembet@gmail.com&token=8F5DD394F5C748B0A8F80E72B96BD8E5'
 * https://dev.pagseguro.uol.com.br/v1.0/reference/transparente-cartao-de-credito
 * https://dev.pagseguro.uol.com.br/v1.0/page/quickstart-api-checkout-transparente
 * https://documenter.getpostman.com/view/13365079/TVYQ1DfW#43ea825b-b676-44b6-8513-38f38293a57c
 * http://download.uol.com.br/pagseguro/docs/pagseguro-checkout-transparente.pdf
 * https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/notifications/69EE73F4B374B3745D9CC4BA5F9BD00A1555?email=diogo@diogocezar.com&token=9C16049D5E124FF6B818BB75B3BACBF7
 * https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/notifications/16FE3415D3D31FCCC4BF3F873D265179?email=clembet@gmail.com&token=8F5DD394F5C748B0A8F80E72B96BD8E5
 * @method \Omnipay\Common\Message\RequestInterface completeAuthorize(array $options = array())
 * @method \Omnipay\Common\Message\RequestInterface completePurchase(array $options = array())
 * @method \Omnipay\Common\Message\RequestInterface refund(array $options = array())
 * @method \Omnipay\Common\Message\RequestInterface createCard(array $options = array())
 * @method \Omnipay\Common\Message\RequestInterface updateCard(array $options = array())
 * @method \Omnipay\Common\Message\RequestInterface deleteCard(array $options = array())
 */
class Gateway extends AbstractGateway
{
    /**
     * Get gateway display name
     *
     * This can be used by carts to get the display name for each gateway.
     * @return string
     */
    public function getName()
    {
        return 'PagSeguro';
    }

    /**
     * Define gateway parameters, in the following format:
     *
     * [
     *     'merchant_id' => '', // string The Merchant Id
     *     'merchant_key' => '', // string The Merchant Key
     * ];
     * @return array
     */
    public function getDefaultParameters()
    {
        return [
            'email'  => '',
            'token' => '',
            'testMode' => false,
        ];
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

    public function getPubKey()
    {
        return $this->getParameter('pubKey');
    }

    public function setPubKey($value)
    {
        return $this->setParameter('pubKey', $value);
    }

    public function parseResponse($data)
    {
        $request = $this->createRequest('\Omnipay\PagSeguro\Message\PurchaseRequest', []);
        return new \Omnipay\PagSeguro\Message\Response($request, (array)$data);
    }

    /**
     * Authorize Request
     *
     * An Authorize request is similar to a purchase request but the
     * charge issues an authorization (or pre-authorization), and no money
     * is transferred.  The transaction will need to be captured later
     * in order to effect payment. Uncaptured charges expire in 5 days.
     *
     * Either a card object or card_id is required by default. Otherwise,
     * you must provide a card_hash, like the ones returned by PagSeguro
     *
     * PagSeguro gateway supports only two types of "payment_method":
     *
     * * credit_card
     *
     * Optionally, you can provide the customer details to use the antifraude
     * feature. These details is passed using the following attributes available
     * on credit card object:
     *
     * * firstName
     * * lastName
     * * address1 (must be in the format "street, street_number and neighborhood")
     * * address2 (used to specify the optional parameter "street_complementary")
     * * postcode
     * * phone (must be in the format "DDD PhoneNumber" e.g. "19 98888 5555")
     *
     * @param array $parameters
     * @return \Omnipay\PagSeguro\Message\AuthorizeRequest
     */
    /*public function authorize(array $parameters = [])//ok
    {
        return $this->createRequest('\Omnipay\PagSeguro\Message\AuthorizeRequest', $parameters);
    }*/

    public function acceptNotification(array $parameters = [])
    {
        return $this->createRequest('\Omnipay\PagSeguro\Message\NotificationRequest', $parameters);
    }

    /**
     * Capture Request
     *
     * Use this request to capture and process a previously created authorization.
     *
     * @param array $parameters
     * @return \Omnipay\PagSeguro\Message\CaptureRequest
     */
    /*public function capture(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\PagSeguro\Message\CaptureRequest', $parameters);
    }*/

    /**
     * Purchase request.
     *
     * To charge a credit card  you create a new transaction
     * object. If your MerchantID is in test mode, the supplied card won't actually
     * be charged, though everything else will occur as if in live mode.
     *
     * Either a card object or card_id is required by default. Otherwise,
     * you must provide a card_hash, like the ones returned by PagSeguro
     *
     * PagSeguro gateway supports only one type of "payment_method":
     *
     * * credit_card
     *
     *
     * Optionally, you can provide the customer details to use the antifraude
     * feature. These details is passed using the following attributes available
     * on credit card object:
     *
     * * firstName
     * * lastName
     * * address1 (must be in the format "street, street_number and neighborhood")
     * * address2 (used to specify the optional parameter "street_complementary")
     * * postcode
     * * phone (must be in the format "DDD PhoneNumber" e.g. "19 98888 5555")
     *
     * @param array $parameters
     * @return \Omnipay\PagSeguro\Message\PurchaseRequest
     */
    public function purchase(array $parameters = [])
    {
        return $this->createRequest('\Omnipay\PagSeguro\Message\PurchaseRequest', $parameters);
    }

    public function authorize(array $parameters = [])
    {
        return $this->createRequest('\Omnipay\PagSeguro\Message\AuthorizeRequest', $parameters);
    }
    public function capture(array $parameters = [])
    {
        return $this->createRequest('\Omnipay\PagSeguro\Message\CaptureRequest', $parameters);
    }

    public function orderPurchase(array $parameters = [])
    {
        return $this->createRequest('\Omnipay\PagSeguro\Message\OrderRequest', $parameters);
    }

    /**
     * Void Transaction Request
     *
     *
     *
     * @param array $parameters
     * @return \Omnipay\PagSeguro\Message\VoidRequest
     */
    public function void(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\PagSeguro\Message\VoidRequest', $parameters);
    }

    public function fetchTransaction(array $parameters = [])
    {
        return $this->createRequest('\Omnipay\PagSeguro\Message\FetchTransactionRequest', $parameters);
    }
}
