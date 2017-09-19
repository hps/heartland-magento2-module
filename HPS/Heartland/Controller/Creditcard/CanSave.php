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

namespace HPS\Heartland\Controller\Creditcard;

use HPS\Heartland\Model\StoredCard;

/**
 * Class CanSave
 * /heartland/hss/cansave/ URI segment
 * @package HPS\Heartland\Controller\Hss
 */
class CanSave extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\Raw
     */
    private $resultRawFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
    }

    /** \HPS\Heartland\Controller\Hss\CanSave::execute
     * checks if card saving is enabled and prints a 1 for enabled or 0 for disabled
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();

        // # \HPS\Heartland\Model\StoredCard::getCanStoreCards
        return $resultRaw->setContents((int) StoredCard::getCanStoreCards());
    }
}
