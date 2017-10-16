<?php

namespace NaitusEirl\PagoFacil\Model;

class PagoFacil extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_PAGOFACIL_CODE = 'pagofacil';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_PAGOFACIL_CODE;

    /**
     * @var string
     */
    protected $_formBlockType = \Magento\OfflinePayments\Block\Form\Checkmo::class;

    /**
     * @var string
     */
    protected $_infoBlockType = \NaitusEirl\PagoFacil\Block\Info::class;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;


}
