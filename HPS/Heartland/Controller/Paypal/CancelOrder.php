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
 
use Magento\Framework\Controller\ResultFactory;
use \HPS\Heartland\Helper\Customer;
use \HPS\Heartland\Helper\Admin;
use \HPS\Heartland\Helper\Db;
use \HPS\Heartland\Helper\Data as HPS_DATA;
use Magento\Checkout\Model\Session;

class CancelOrder extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        array $data = [])
    {
        $this->resultPageFactory = $resultPageFactory;
        return parent::__construct($context,$data);
    }

    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $orderId = $this->getRequest()->getParam('orderid');
		$order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);
        if($order->canCancel()){
            $order->cancel();
            $order->save();
            $this->messageManager->addSuccess(__('Order has been canceled successfully.'));
        } else {
            $this->messageManager->addError(__('Order cannot be canceled.'));
        }
        $resultRedirect->setUrl(HPS_DATA::getBaseUrl() . 'customer/account');
        return $resultRedirect;
    }
}