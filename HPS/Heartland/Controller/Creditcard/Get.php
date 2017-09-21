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

use \Magento\Framework\App\Action\Action;
use \HPS\Heartland\Model\StoredCard as HPS_STORED_CARDS;
use \HPS\Heartland\Helper\ObjectManager as HPS_OM;
use \Magento\Framework\UrlInterface;

/**
 * Class StoredCard
 * /heartland/hss/storedcard/ URI segment
 * @package HPS\Heartland\Controller\Hss
 * \HPS\Heartland\Controller\Creditcard\Get
 */
class Get extends Action
{
    /**
     * @const string
     */
    const IMAGE_STATIC_PATH = 'frontend/Magento/blank/en_US/HPS_Heartland/images/';

    /**
     * @const string
     */
    const STORE_INTERFACE = '\Magento\Store\Model\StoreManagerInterface';

    /**
     * @var bool|string
     */
    private $baseImageUri = false;

    /**
     * @var \Magento\Framework\Controller\Result\Json
     */
    private $resultJsonFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /** \HPS\Heartland\Controller\Hss\StoredCard::execute
     * First checks if the caller has a valid user session
     *
     * @throws \Exception
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        // # \HPS\Heartland\Model\StoredCard::getCanStoreCards
        $response = [];
        if (HPS_STORED_CARDS::getCanStoreCards()) {
            // # \HPS\Heartland\Model\StoredCard::getStoredCards
            $data = HPS_STORED_CARDS::getStoredCards(); /**/
            if (!empty($data)) {
                foreach ($data as $row) {
                    $response[] = [
                        'token_value' => $row["heartland_storedcard_id"],
                        'cc_last4' => $row["cc_last4"],
                        'cc_type' => $row["cc_type"],
                        'cc_exp_month' => $row["cc_exp_month"],
                        'cc_exp_year' => $row["cc_exp_year"],
                    ];
                }
            }
        }

        return $resultJson->setData($response);
    }

    /**
     * @return string
     */
    private function getStaticURL()
    {
        if ($this->baseImageUri === false) {
            $this->baseImageUri = HPS_OM::getObjectManager()
                                        ->get(self::STORE_INTERFACE)
                                        ->getStore()
                                        ->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
        }
        return $this->baseImageUri;
    }

    /**
     * @param null|string $cardType
     *
     * @return string
     * @throws \Exception
     */
    private function getImageLink($cardType = null)
    {
        if ($cardType === null || $cardType === '' || preg_match('/[\W]/', $cardType) === 1) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Card type not configured for saved token.'));
        }
        return  $this->getStaticURL() . self::IMAGE_STATIC_PATH . 'ss-inputcard-' . strtolower($cardType) . '@2x.png';
    }
}
