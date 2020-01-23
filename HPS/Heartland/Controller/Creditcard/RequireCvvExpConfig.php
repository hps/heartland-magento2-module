<?php
/**
 *  Heartland payment method model
 *
 * @category    HPS
 * @package     HPS_Heartland
 * @author      Heartland Developer Portal <EntApp_DevPortal@e-hps.com>
 * @copyright   Heartland (http://heartland.us)
 * @license     https://github.com/hps/heartland-magento2-module/blob/master/LICENSE.md
 */

namespace HPS\Heartland\Controller\Creditcard;

use \HPS\Heartland\Helper\Data as HPS_DATA;

/**
 * Class RequireCvvExpConfig
 *
 * @package HPS\Heartland\Controller\Hss
 *
 */
// # \HPS\Heartland\Controller\CreditCard\RequireCvvExpConfig
class RequireCvvExpConfig extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\Raw
     */
    private $resultRawFactory;
    
    /**
     * @var \HPS\Heartland\Helper\Data
     */
    private $hpsData;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \HPS\Heartland\Helper\Data $hpsData
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->hpsData = $hpsData;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents((string) $this->hpsData->getReqExpCvv());
    }
}
