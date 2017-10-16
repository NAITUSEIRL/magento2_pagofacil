<?php

namespace NaitusEirl\PagoFacil\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

class PagoFacilConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string
     */
    protected $methodCode = PagoFacil::PAYMENT_METHOD_PAGOFACIL_CODE;

    /**
     * @var PagoFacil
     */
    protected $method;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        UrlInterface $urlBuilder
    ) {
        $this->escaper = $escaper;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'pagofacil' => [
                    'description' => $this->method->getConfigData("description"),
                    'redirectUrl' => $this->urlBuilder->getUrl("pagofacil/checkout/success",array("_secure"=>true))
                ],
            ],
        ] : [];
    }

    /**
     * Get mailing address from config
     *
     * @return string
     */
    protected function getMailingAddress()
    {
        return nl2br($this->escaper->escapeHtml($this->method->getMailingAddress()));
    }

    /**
     * Get payable to from config
     *
     * @return string
     */
    protected function getPayableTo()
    {
        return $this->method->getPayableTo();
    }
}
