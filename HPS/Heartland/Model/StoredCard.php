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

/**
 * Class StoredCard
 *
 * @package HPS\Heartland\Model
 */
class StoredCard
{
    private $hpsTableName = 'hps_heartland_storedcard';
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $authSession;
    
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $backendSession;
    
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;
    
    /**
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Backend\Model\Auth\Session $backendSession
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Backend\Model\Auth\Session $backendSession,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->authSession = $authSession;
        $this->backendSession = $backendSession;
        $this->resourceConnection = $resourceConnection;
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
            $conn = $this->resourceConnection->getConnection();
            if ($conn->isTableExists($conn->getTableName($this->hpsTableName))) {
                $select = $conn->select()
                    ->from(
                        ['o' => $this->hpsTableName]
                    )
                    ->where('o.customer_id   = ?', (int)$custID)
                    ->where('o.heartland_storedcard_id = ?', (int)$id);
                $data = (array)$conn->fetchRow($select);
                if (!empty($data) && key_exists('token_value', $data)) {
                    $MuToken = $data['token_value'];
                }
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
            $conn = $this->resourceConnection->getConnection();
        if ($conn->isTableExists($conn->getTableName($this->hpsTableName))) {
            $conn->delete($this->hpsTableName, [
            'heartland_storedcard_id = ?' => (int)$id,
            ]);
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
            $conn = $this->resourceConnection->getConnection();
            if ($conn->isTableExists($conn->getTableName($this->hpsTableName))) {
                $select = $conn->select()
                    ->from(
                        ['o' => $this->hpsTableName]
                    )
                    ->where('o.customer_id = ?', (int)$this->authSession->getCustomerId());
                $data = (array)$conn->fetchAll($select);
            }
        }

        return (array)$data;
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
            $conn = $this->resourceConnection->getConnection();
            if ($conn->isTableExists($conn->getTableName($this->hpsTableName))) {
                $select = $conn->select()
                    ->from(
                        ['o'=>$this->hpsTableName]
                    )
                    ->where('o.customer_id = ?', (int)$custID);
                $data = (array)$conn->fetchAll($select);
            }
        }

        return (array)$data;
    }

    /** returns true or false if stored cards are enabled
     *
     * @return bool
     */
    public function getCanStoreCards()
    {
        $retVal = (int)0;
        if ($this->authSession->isLoggedIn() || $this->backendSession->isLoggedIn()) {
            $conn = $this->resourceConnection->getConnection();
            if ($conn->isTableExists($conn->getTableName($this->hpsTableName))) {
                $select = $conn->select()
                    ->from(
                        ['o' => 'core_config_data']
                    )
                    ->where('o.path = ?', (string)'payment/hps_heartland/save_cards');
                $data = (array)$conn->fetchAll($select);

                $retVal = (!empty($data[0])) ? (int)$data[0]['value'] : (int) 0;
            }
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
        $conn = $this->resourceConnection->getConnection();
        if ($customerID) {
            if ($conn->isTableExists($conn->getTableName($this->hpsTableName))) {
                // try to prevent duplicat records in the table
                $conn->delete($this->hpsTableName, [
                    'customer_id = ?'   => (int)$customerID,
                    'token_value = ?' => $token,
                ]);
                $conn->insert($this->hpsTableName, [
                        'heartland_storedcard_id' => '',
                        'dt'            => date("Y-m-d H:i:s"),
                        'customer_id'   => $customerID,
                        'token_value'   => (string)$token,
                        'cc_type'       => (string)$cc_type,
                        'cc_last4'      => (string)$last4,
                        'cc_exp_month'  => (string)$cc_exp_month,
                        'cc_exp_year'   => (string)$cc_exp_year,
                    ]);
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('No valid User Logged On!! Cannot save card.'));
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    private function validate($data)
    {
        if (!empty($data)) {
            // some very basic validation.
            //simply dont want invalid arrays of data
            foreach ($data as $item) {
                if (preg_match('/[\D]/', $item['heartland_storedcard_id']) === 1) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('heartland_storedcard_id does not have a valid value.'));
                }
                foreach ($item as $columnName => $columnValue) {
                    if ($columnValue === null || $columnValue === '' || preg_match('/[^\w\s\-\:]/', $columnValue) === 1) {
                        throw new \Magento\Framework\Exception\LocalizedException(__($columnName . ' Column does not have a valid value for ' . $item['heartland_storedcard_id']));
                    }
                }
            }
        }
        return true;
    }
}
