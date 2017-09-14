<?php

/**
 *  Heartland payment method model
 *
 * @category    HPS
 * @package     HPS_Heartland
 * @author      Heartland Developer Portal <EntApp_DevPortal@e-hps.com>
 * @copyright   Heartland (http://heartland.us)
 * @license     https://github.com/hps/heartland-magento2-extension/blob/master/LICENSE.md
 */

namespace HPS\Heartland\Controller\Paypal;

use \Magento\Framework\Controller\ResultFactory;
use \HPS\Heartland\Helper\Customer;
use \HPS\Heartland\Helper\Admin;
use \HPS\Heartland\Helper\Db;
use \HPS\Heartland\Helper\Data as HPS_DATA;
use \Magento\Checkout\Model\Session;
use \Magento\Sales\Model\Order;

class CompleteOrder extends \Magento\Framework\App\Action\Action {

    protected $resultPageFactory;

    public function __construct(
    \Magento\Framework\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, array $data = []) {
        $this->resultPageFactory = $resultPageFactory;
        return parent::__construct($context, $data);
    }

    public function execute() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $orderId = $this->getRequest()->getParam('orderid');
        $order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);
        $orderState = Order::STATE_COMPLETE;
        $order->setState($orderState)->setStatus(Order::STATE_COMPLETE);
        $order->save();
        $resultRedirect->setUrl(HPS_DATA::getBaseUrl() . 'checkout/onepage/success');
        return $resultRedirect;
    }

}
