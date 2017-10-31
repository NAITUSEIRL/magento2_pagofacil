<?php

namespace NaitusEirl\PagoFacil\Controller\Gateway;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use ctala\transaccion\classes\Response;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Webapi\Exception;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderFactory;
use NaitusEirl\PagoFacil\Model\PagoFacil;
use Magento\Payment\Helper\Data as PaymentHelper;

class Payment extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $jsonResultFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var PagoFacil
     */
    protected $method;

    /**
     * @var EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     * @var InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $http;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        PaymentHelper $paymentHelper,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\ObjectManagerInterface $orderFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\App\Response\Http $http,
        InvoiceSender $invoiceSender
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->_orderFactory = $orderFactory;
        $this->method = $paymentHelper->getMethodInstance(PagoFacil::PAYMENT_METHOD_PAGOFACIL_CODE);
        $this->_encryptor = $encryptor;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->http = $http;
        $this->_invoiceSender = $invoiceSender;
    }

    /**
     * Default customer account page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $session = $this->getOnepage()->getCheckout();
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->jsonResultFactory->create();
        $params = $this->getRequest()->getParams();

        try{
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                /*
                 * Error 405
                 * Método no permitido.
                 * Se finaliza
                 */
                throw new \Exception();
            }

            $ctOrderId = $params["ct_order_id"];
            $order = $this->_orderFactory->create('Magento\Sales\Model\Order')->load($ctOrderId);

            if (!$order->getId()) {
                /*
                 * Error 404
                 * Orden no encontrada
                 * Se finaliza
                 */

                throw new \Exception();
            }

            /*
             * Si la oden existe
             * Corroboramos las firmas del mensaje
             * Para hacerlo debemos firmar el mensaje nuevamente y corroborar si la firma
             * es la misma
             */

            $ctTokenTienda = $params["ct_token_tienda"];
            $ctMonto = $params["ct_monto"];
            $ctTokenService = $params["ct_token_service"];
            $ctEstado = $params["ct_estado"];
            $ctAuthorizationCode = $params["ct_authorization_code"];
            $ctPaymentTypeCode = $params["ct_payment_type_code"];
            $ctCardNumber = $params["ct_card_number"];
            $ctCardExpirationDate = $params["ct_card_expiration_date"];
            $ctSharesNumber = $params["ct_shares_number"];
            $ctAccountingDate = $params["ct_accounting_date"];
            $ctTransactionDate = $params["ct_transaction_date"];
            $ctOrderIdMall = $params["ct_order_id_mall"];

            $response = new Response($ctOrderId, $ctTokenTienda, $ctMonto, $ctTokenService, $ctEstado, $ctAuthorizationCode, $ctPaymentTypeCode, $ctCardNumber, $ctCardExpirationDate, $ctSharesNumber, $ctAccountingDate, $ctTransactionDate, $ctOrderIdMall);


            /*
             * Si las firmas corresponden corroboramos los valores
             * o montos.
             */

            $ctFirma = $params["ct_firma"];
            $tokenSecret = $this->_encryptor->decrypt($this->method->getConfigData("token_secret"));
            $response->setCt_token_secret($tokenSecret);
            $signedResponse = $response->getArrayResponse();

            if ($signedResponse["ct_firma"] != $ctFirma) {
                /*
                 * Firmas no corresponden. POsible inyección de datos.
                 * Se termina el proceso.
                 */

                throw new \Exception();
            }
            /*
             * Si los montos corresponden revisamos y actualizamos el estado
             */

            if ($signedResponse["ct_monto"] != $order->getGrandTotal()) {
                /*
                 * Montos no corresponden. Posible inyección de datos.
                 * Se termina el proceso.
                 */

                throw new \Exception();
            }

            if ($signedResponse["ct_estado"] == PagoFacil::RESPONSE_CODE_COMPLETED) {
                if($order->canInvoice()) {
                    $invoice = $this->_invoiceService->prepareInvoice($order);
                    $invoice->register();
                    $invoice->setState(Invoice::STATE_OPEN);
                    $invoice->save();
                    $transactionSave = $this->_transaction->addObject($invoice)->addObject($invoice->getOrder());
                    $transactionSave->save();
                    $this->_invoiceSender->send($invoice);
                    //send notification code
                    $order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $invoice->getId()))
                        ->setIsCustomerNotified(true)
                        ->save();
                    $this->method->createTransaction($order,$invoice,$params);
                    $invoice->setState(Invoice::STATE_PAID);
                }
            } else {
                $order->addStatusHistoryComment(__('Pago Facil marked payment as %1',$signedResponse["ct_estado"]))
                    ->setIsCustomerNotified(false)
                    ->save();
                /*
                 * Acá la puedes marcar como pendiente o fallida.
                 */
                throw new \Exception();
            }

            /*
             * Terminamos el proceso con resultado 200
             * TODO OK.
             */
        }catch (\Exception $e){
            /*
             * Montos no corresponden. Posible inyección de datos.
             * Se termina el proceso.
             */
            $this->messageManager->addErrorMessage(__("There has been an error with your payment. Please contact the store administrator."));

        }

        $this->http->setRedirect($this->_url->getUrl("checkout/onepage/success"));
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
}
