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
namespace HPS\Heartland\Model;

use Magento\Customer\Model\Session as customerSession;
use \Magento\Backend\Model\Auth\Session as adminSession;
use HPS\Heartland\Model\TokenDataFactory;
use \Magento\Store\Model\ScopeInterface;

/**
 * Class StoredCard
 *
 * @package HPS\Heartland\Model
 */
class StoredCard
{

    /**
     * @var HPS\Heartland\Model\TokenDataFactory
     */
    private $modelTokenDataFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $authSession;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $backendSession;

    /**
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Backend\Model\Auth\Session $backendSession
     */
    public function __construct(
        customerSession $authSession,
        adminSession $backendSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        TokenDataFactory $modelTokenDataFactory
    ) {
    
        $this->authSession = $authSession;
        $this->backendSession = $backendSession;
        $this->scopeConfig = $scopeConfig;
        $this->modelTokenDataFactory = $modelTokenDataFactory;
    }

    /** performs a db lookup for the current customer within the db given a specific token ID
     * @param int $id
     *
     * @return bool|array
     * @throws \Exception
     */
    public function getToken($id, $custID = null)
    {
        $MuToken = false;
        if (empty($custID) && $this->authSession->isLoggedIn()) {
            $custID = $this->authSession->getCustomerId();
        }
        if (!empty($custID)) {
            $token = $this->modelTokenDataFactory->create();
            $collection = $token->getCollection()
                ->addFieldToFilter('customer_id', ['eq' => (int) $custID])
                ->addFieldToFilter('heartland_storedcard_id', ['eq' => (int) $id]);
            
            if ($collection->getSize() > 0) {
                $data = $collection->getFirstItem();
                $MuToken = (!empty($data['token_value'])) ? $data['token_value'] : false;
            }
        }
        return $MuToken;
    }

    /** deletes an existing stored card referenced by primary key and logged on user
     *
     * @param int $id
     *
     * @throws \Exception
     */
    public function deleteStoredCards($id)
    {
        try {
            $model = $this->modelTokenDataFactory->create();
            $model->load((int) $id);
            $model->delete();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                $e->getMessage()
            );
        }
    }

    /** looks up existing stored cards for the currently logged on user
     *
     * @return array
     *
     *
     * @throws \Exception
     */
    public function getStoredCards()
    {
        $data = [];
        if ($this->authSession->isLoggedIn()) {
            $token = $this->modelTokenDataFactory->create();
            $collection = $token->getCollection()
                ->addFieldToFilter('customer_id', ['eq' => (int) $this->authSession->getCustomerId()]);
            
            if ($collection->getSize() > 0) {
                $data = $collection->getData();
            }
        }

        return (array) $data;
    }

    /** looks up existing stored cards for the currently logged on user
     *
     * @return array
     *
     *
     */
    public function getStoredCardsAdmin($custID = null)
    {
        $data = [];
        if ($custID !== null && $custID > 0 && $this->getCanStoreCards()) {
            $token = $this->modelTokenDataFactory->create();
            $collection = $token->getCollection()
                ->addFieldToFilter('customer_id', ['eq' => (int) $custID]);

            if ($collection->getSize() > 0) {
                $data = $collection->getData();
            }
        }

        return (array) $data;
    }

    /** returns true or false if stored cards are enabled
     *
     * @return bool
     */
    public function getCanStoreCards()
    {
        $retVal = (int) 0;
        if ($this->authSession->isLoggedIn() || $this->backendSession->isLoggedIn()) {
            return $this->scopeConfig->getValue(
                (string) 'payment/hps_heartland/save_cards',
                (string) ScopeInterface::SCOPE_STORE
            );
        }

        return $retVal;
    }

    /** sets stored card data in a table for later use requires a logged on user
     *
     * @param string $token
     * @param string $cc_type
     * @param string $last4
     * @param string $cc_exp_month
     * @param string $cc_exp_year
     *
     * @throws \Exception
     */
    public function setStoredCards($token, $cc_type, $last4, $cc_exp_month, $cc_exp_year, $customerID)
    {
        if ($customerID) {
            //delete existing data
            $model = $this->modelTokenDataFactory->create();
            $model->load($token, 'token_value');
            $model->delete();

            $newToken = $this->modelTokenDataFactory->create();
            $newToken->setData('dt', date("Y-m-d H:i:s"))
                ->setData('customer_id', $customerID)
                ->setData('token_value', (string) $token)
                ->setData('cc_type', (string) $cc_type)
                ->setData('cc_last4', (string) $last4)
                ->setData('cc_exp_month', (string) $cc_exp_month)
                ->setData('cc_exp_year', (string) $cc_exp_year)
                ->save();
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('No valid User Logged On!! Cannot save card.')
            );
        }
    }
}
