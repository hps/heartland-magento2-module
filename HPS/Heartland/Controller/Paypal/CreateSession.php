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

use \Magento\Framework\App\Action\Action;
use \HPS\Heartland\Model\Paypal\CreateSession as HPS_Paypal_Session;
use \HPS\Heartland\Helper\ObjectManager as HPS_OM;
use \Magento\Framework\UrlInterface;

/**
 * Class StoredCard
 * @package HPS\Heartland\Controller\Paypal\CreateSession
 * \HPS\Heartland\Controller\Paypal\CreateSession
 */
class CreateSession extends Action {

    /**
     * @var \Magento\Framework\Controller\Result\Json
     */
    private $resultJsonFactory;
    private $createSessionModel;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
    \Magento\Framework\App\Action\Context $context, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \HPS\Heartland\Model\Paypal\CreateSession $createSessionModel
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->createSessionModel = $createSessionModel;
        parent::__construct($context);
    }

    /** \HPS\Heartland\Controller\Hss\StoredCard::execute
     * First checks if the caller has a valid user session
     *
     * @throws \Exception
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute() {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        //create the paypal session through model
        $response = $this->createSessionModel->createPaypalSession();
        return $resultJson->setData($response);
    }

}
