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

use \HPS\Heartland\Helper\Customer;
use \HPS\Heartland\Helper\Admin;
use \HPS\Heartland\Helper\Db;

/**
 * Class StoredCard
 *
 * @package HPS\Heartland\Model
 */
class StoredCard
{
    const TABLE_NAME = 'hps_heartland_storedcard';

    /** performs a db lookup for the current customer within the db given a specific token ID
     * @param int $id
     *
     * @return bool|array
     * @throws \Exception
     */
    public static function getToken($id, $custID = null)
    {
        $MuToken = false;
        if (empty($custID) && Customer::isLoggedIn()) {
            $custID = Customer::getCustID();
        }
        if (!empty($custID)) {
            $conn = Db::db_connect();
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
                $select = $conn->select()
                    ->from(
                        ['o' => self::TABLE_NAME]
                    )
                    ->where('o.customer_id   = ?', (int)$custID)
                    ->where('o.heartland_storedcard_id = ?', (int)$id);
                $data = (array)$conn->fetchRow($select);
                if (count($data) && key_exists('token_value', $data)) {
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
    public static function deleteStoredCards($id)
    {
        $conn = Db::db_connect();
        if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
            $conn->delete(self::TABLE_NAME, [
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
    public static function getStoredCards()
    {
        $data = [];
        if (Customer::isLoggedIn()) {
            $conn = Db::db_connect();
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
                $select = $conn->select()
                    ->from(
                        ['o' => self::TABLE_NAME]
                    )
                    ->where('o.customer_id = ?', (int)Customer::getCustID());
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
    public static function getStoredCardsAdmin($custID = null)
    {
        $data = [];
        if ($custID !== null && $custID > 0 && self::getCanStoreCards()) {
            $conn = Db::db_connect();
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
                $select = $conn->select()
                    ->from(
                        ['o'=>self::TABLE_NAME]
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
    public static function getCanStoreCards()
    {
        $retVal = (int)0;
        if (Customer::isLoggedIn() || Admin::isLoggedIn()) {
            $conn = Db::db_connect();
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
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
    public static function setStoredCards($token, $cc_type, $last4, $cc_exp_month, $cc_exp_year, $customerID)
    {
        $conn = Db::db_connect();
        if ($customerID) {
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
                // try to prevent duplicat records in the table
                $conn->delete(self::TABLE_NAME, [
                    'customer_id = ?'   => (int)$customerID,
                    'token_value = ?' => $token,
                ]);
                $conn->insert(self::TABLE_NAME, [
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
            throw new \Exception(__('No valid User Logged On!! Cannot save card.'));
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    private static function validate($data)
    {
        if (!empty($data)) {
            // some very basic validation.
            //simply dont want invalid arrays of data
            foreach ($data as $item) {
                if (preg_match('/[\D]/', $item['heartland_storedcard_id']) === 1) {
                    throw new \Exception(__('heartland_storedcard_id does not have a valid value.'));
                }
                foreach ($item as $columnName => $columnValue) {
                    if ($columnValue === null || $columnValue === '' || preg_match('/[^\w\s\-\:]/', $columnValue) === 1) {
                        throw new \Exception(__($columnName . ' Column does not have a valid value for ' . $item['heartland_storedcard_id']));
                    }
                }
            }
        }
        return true;
    }
}
