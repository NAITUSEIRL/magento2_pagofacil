<?php

namespace NaitusEirl\PagoFacil\Block\Checkout;

use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use NaitusEirl\PagoFacil\Model\PagoFacil;
use ctala\transaccion\classes\Transaccion;
use Magento\Payment\Helper\Data as PaymentHelper;

class Success extends Template {

    /**
     * @var PagoFacil
     */
    protected $method;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Onepage
     */
    protected $onepage;

    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;


    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $orderFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        PaymentHelper $paymentHelper,
        Onepage $onepage,
        array $data = []
    ) {
        $this->_orderFactory = $orderFactory;
        $this->onepage = $onepage;
        $this->method = $paymentHelper->getMethodInstance(PagoFacil::PAYMENT_METHOD_PAGOFACIL_CODE);
        $this->_encryptor = $encryptor;
        parent::__construct($context, $data);

        $this->initData();
    }

    /**
     * @return $this
     */
    protected function initData(){
        $paymentMethod = $this->getPaymentMethod();
        $order = $this->getLastOrder();

        $tokenSecret = $this->_encryptor->decrypt($paymentMethod->getConfigData("token_secret"));

        $this->addData([
            "amount"    =>  $order->getGrandTotal(),
            "order_id" => $order->getId(),
            "email" => $order->getCustomerEmail(),
            "token_service" => $paymentMethod->getConfigData("token_service"),
            "token_store" => $paymentMethod->getConfigData("token_store"),
            "submit_url" => $this->getSubmitUrl((boolean)$paymentMethod->getConfigData("sandbox")),
        ]);

        $transaction = new Transaccion($this->getOrderId(),$this->getTokenStore(),$this->getAmount(),$this->getTokenService(),$this->getEmail());
        $transaction->setCt_token_secret($tokenSecret);
        $paymentArgs = $transaction->getArrayResponse();
        $this->setSignature($paymentArgs["ct_firma"]);

        return $this;
    }

    /**
     * Get last order instance
     *
     * @return Order
     */
    protected function getLastOrder(){
        return $this->_orderFactory->create('Magento\Sales\Model\Order')->loadByIncrementId($this->onepage->getLastOrderId());
    }

    /**
     * @return mixed|PagoFacil
     */
    protected function getPaymentMethod(){
        return $this->method;
    }

    /**
     * Get one page checkout model
     *
     * @return \Magento\Checkout\Model\Type\Onepage
     * @codeCoverageIgnore
     */
    public function getOnepage()
    {
        return $this->_objectManager->get(\Magento\Checkout\Model\Type\Onepage::class);
    }

    /**
     * @param bool $sandbox
     * @return string
     */
    protected function getSubmitUrl($sandbox = false){
        return $sandbox ? PagoFacil::GATEWAY_SANDBOX_URL : PagoFacil::GATEWAY_URL;
    }

}