<?php
/**
 * Credit Card class
 */

namespace Omnipay\PagSeguro;

use Omnipay\Common\CreditCard as Card;

/**
 * Credit Card class
 *
 * This class extends the Omnipay's Credit Card
 * allowing the addition of a new attribute "holder_document_number".
 *
 * Example:
 *
 * <code>
 *   // Define credit card parameters, which should look like this
 *   $parameters = array(
 *       'firstName' => 'Bobby',
 *       'lastName' => 'Tables',
 *       'number' => '4444333322221111',
 *       'cvv' => '123',
 *       'expiryMonth' => '12',
 *       'expiryYear' => '2017',
 *       'email' => 'testcard@gmail.com',
 *       'holder_document_number' => '224.158.178-40' // CPF or CNPJ
 *   );
 *
 *   // Create a credit card object
 *   $card = new CreditCard($parameters);
 * </code>
 */

class CreditCard extends Card
{
    public function getNumberToken()
    {
        return $this->getParameter('number_token');
    }

    public function setNumberToken($value)
    {
        // strip non-numeric characters
        return $this->setParameter('number_token', $value);
    }

    public function getShippingNumber()
    {
        return $this->getParameter('shippingNumber');
    }

    public function setShippingNumber($value)
    {
        // strip non-numeric characters
        return $this->setParameter('shippingNumber', $value);
    }

    public function getShippingDistrict()
    {
        return $this->getParameter('shippingDistrict');
    }

    public function setShippingDistrict($value)
    {
        // strip non-numeric characters
        return $this->setParameter('shippingDistrict', $value);
    }

    public function getShippingAmount()
    {
        return $this->getParameter('shippingAmount');
    }

    public function setShippingAmount($value)
    {
        // strip non-numeric characters
        return $this->setParameter('shippingAmount', $value);
    }

    public function getBillingNumber()
    {
        return $this->getParameter('billingNumber');
    }

    public function setBillingNumber($value)
    {
        // strip non-numeric characters
        return $this->setParameter('billingNumber', $value);
    }

    public function getBillingDistrict()
    {
        return $this->getParameter('billingDistrict');
    }

    public function setBillingDistrict($value)
    {
        // strip non-numeric characters
        return $this->setParameter('billingDistrict', $value);
    }

    public function getHolderDocumentNumber()
    {
        return $this->getParameter('holder_document_number');
    }

    public function setHolderDocumentNumber($value)
    {
        // strip non-numeric characters
        return $this->setParameter('holder_document_number', $value);
    }

    public function getAreaCode()
    {
        $phone = $this->getPhone();
        return substr($phone, 0, 2);
    }
}
