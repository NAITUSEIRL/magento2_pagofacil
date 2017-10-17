<?php

namespace NaitusEirl\PagoFacil\Controller\Gateway;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use ctala\transaccion\classes\Response;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Webapi\Exception;
use Magento\Sales\Model\OrderFactory;
use NaitusEirl\PagoFacil\Model\PagoFacil;
use Magento\Payment\Helper\Data as PaymentHelper;

class Callback extends \Magento\Framework\App\Action\Action
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

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        PaymentHelper $paymentHelper,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->_orderFactory = $orderFactory;
        $this->method = $paymentHelper->getMethodInstance(PagoFacil::PAYMENT_METHOD_PAGOFACIL_CODE);
        $this->_encryptor = $encryptor;
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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            /*
             * Error 405
             * Método no permitido.
             * Se finaliza
             */
            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_FORBIDDEN);

            return $result;
        }

        $ctOrderId = $params["ct_order_id"];
        $order = $this->_orderFactory->create('Magento\Sales\Model\Order')->loadByIncrementId($ctOrderId);

        if (!$order->getId()) {
            /*
             * Error 404
             * Orden no encontrada
             * Se finaliza
             */
            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_NOT_FOUND);

            return $result;
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

            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);

            return $result;
        }
        /*
         * Si los montos corresponden revisamos y actualizamos el estado
         */

        if ($signedResponse["ct_monto"] != $order->getGrandTotal()) {
            /*
             * Montos no corresponden. Posible inyección de datos.
             * Se termina el proceso.
             */

            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);

            return $result;
        }

        if ($signedResponse["ct_estado"] == PagoFacil::RESPONSE_CODE_COMPLETED) {
            $this->method->createTransaction($order);
        } else {
            /*
             * Acá la puedes marcar como pendiente o fallida.
             */
        }

        /*
         * Terminamos el proceso con resultado 200
         * TODO OK.
         */


        return $result;
    }
}
