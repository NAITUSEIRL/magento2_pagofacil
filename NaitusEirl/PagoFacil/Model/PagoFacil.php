<?php

namespace NaitusEirl\PagoFacil\Model;

use Magento\Sales\Model\Order;

class PagoFacil extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var string
     */
    const PAYMENT_METHOD_PAGOFACIL_CODE = 'pagofacil';

    /**
     * @var string
     */
    const GATEWAY_URL = "https://sv1.tbk.cristiantala.cl/tbk/v2/initTransaction";

    /**
     * @var string
     */
    const GATEWAY_SANDBOX_URL = "https://dev-env.sv1.tbk.cristiantala.cl/tbk/v2/initTransaction";

    /**
     * @var string
     */
    const RESPONSE_CODE_COMPLETED = "COMPLETADA";

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

    /**
     * @var Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * @var Order\Email\Sender\InvoiceSender
     */
    protected $invoiceSender;

    public function createTransaction(Order $order,Order\Invoice $invoice,$data = array()){
        $payment = $order->getPayment();
        $payment->setTransactionId($data["ct_firma"]);
        $payment->setCurrencyCode('USD');
        $payment->setIsTransactionClosed(true);
        $payment->registerCaptureNotification($order->getGrandTotal(), true);
        $order->save();

        $invoice = $payment->getCreatedInvoice();
        if ($order->getIsVirtual()) {
            $status = $order->getStatus();
        } else {
            $status = "processing";
        }

        if ($invoice && !$order->getEmailSent()) {
            //$this->orderSender->send($order);
            $message = __('New order email sent');
            $order->addStatusToHistory($status, $message, true)->save();
        }
        if ($invoice && !$invoice->getEmailSent()) {
            //$this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $order->addStatusToHistory($status, $message, true)->save();
        }
    }

}
