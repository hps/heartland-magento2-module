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
     * @var \HPS\Heartland\Model\StoredCard
     */
    private $hpsStoredCard;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \HPS\Heartland\Model\StoredCard $hpsStoredCard
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->hpsStoredCard = $hpsStoredCard;
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
        return $resultRaw->setContents((int) $this->hpsStoredCard->getCanStoreCards());
    }
}
