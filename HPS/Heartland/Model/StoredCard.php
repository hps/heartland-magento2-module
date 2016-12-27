<?php
/**
 *  Heartland payment method model
 *
 *  @category    HPS
 *  @package     HPS_Heartland
 *  @author      Charlie Simmons <charles.simmons@e-hps.com>
 *  @copyright   Heartland (http://heartland.us)
 *  @license     https://github.com/hps/heartland-magento2-extension/blob/master/LICENSE.md
 */

/**
 * Created by PhpStorm.
 * User: charles.simmons
 * Date: 3/16/2016
 * Time: 12:43 PM
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
class StoredCard {
    const TABLE_NAME = 'hps_heartland_storedcard';

    /**
     * @param int $id
     *
     * @return bool
     * @throws \Exception
     */
    public static function getToken($id,$custID = null) {
        $MuToken = false;
        if(empty($custID) && Customer::isLoggedIn() ){
            $custID = Customer::getCustID();
        }
        if (!empty($custID)) {
            $conn = Db::db_connect();
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
                $select = $conn->select()
                    ->from(
                        ['o' => self::TABLE_NAME]
                    )
                    ->where('o.customer_id   = ?', (int)Customer::getCustID())
                    ->where('o.heartland_storedcard_id = ?', (int)$id);
                $data = (array)$conn->fetchAll($select);
                self::validate($data);
                if (count($data) && key_exists('token_value', $data[0])) {
                    $MuToken = $data[0]['token_value'];
                }
            }
        }
        else {
            throw new \Exception(__('No valid User Logged On!! Cannot get saved card.'));
        }

        //}
        return $MuToken;
    }

    /** deletes an existing stored card referenced by primary key and logged on user
     *
     * @param int $id
     *
     * @throws \Exception
     */
    public static function deleteStoredCards($id) {
        if (Customer::isLoggedIn()) {
            $conn = Db::db_connect();
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
                $conn->delete(self::TABLE_NAME, array(
                    'customer_id = ?'   => (int)Customer::getCustID(),
                    'heartland_storedcard_id = ?' => (int)$id,
                ));
            }
        }
        else {
            throw new \Exception(__('No valid User Logged On!! Cannot save card.'));
        }
    }

    /** looks up existing stored cards for the currently logged on user
     *
     * @return array
     *
     *
     * @throws \Exception
     */
    public static function getStoredCards() {
        $data = [];
        if (Customer::isLoggedIn()) {
            $conn = Db::db_connect();
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
                $select = $conn->select()
                    ->from(
                        ['o' => self::TABLE_NAME],
                        ['heartland_storedcard_id' => 'max(heartland_storedcard_id)']
                    )
                    ->where('o.customer_id = ?', (int)Customer::getCustID())
                ->group('o.token_value')
                ;
                $tdata = (array)$conn->fetchAll($select);
                self::validate($tdata);
                foreach ($tdata as $item) {
                    $conn = Db::db_connect();
                        $select2 = $conn->select()
                            ->from(['o' => self::TABLE_NAME])
                            ->where('o.heartland_storedcard_id = ?', $item["heartland_storedcard_id"]);
                        $sdata = (array)$conn->fetchAll($select2);
                        self::validate($sdata);
                    $data[] = $sdata[0];
                }
                self::validate($data);/**/
            }
        }
        else {
            throw new \Exception(__('No valid User Logged On!! Cannot get saved cards.'));
        }

        return (array)$data;
    }/** looks up existing stored cards for the currently logged on user
     *
     * @return array
     *
     *
     * @throws \Exception
     */
    public static function getStoredCardsAdmin($custID = null) {
        $data = [];
        if ($custID !== null && $custID > 0) {
            $conn = Db::db_connect();
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
                $select = $conn->select()
                    ->from(
                        ['o' => self::TABLE_NAME],
                        ['heartland_storedcard_id' => 'max(heartland_storedcard_id)']
                    )
                    ->where('o.customer_id = ?', (int)$custID)
                ->group('o.token_value')
                ;
                $tdata = (array)$conn->fetchAll($select);
                self::validate($tdata);
                foreach ($tdata as $item) {
                    $conn = Db::db_connect();
                        $select2 = $conn->select()
                            ->from(['o' => self::TABLE_NAME])
                            ->where('o.heartland_storedcard_id = ?', $item["heartland_storedcard_id"]);
                        $sdata = (array)$conn->fetchAll($select2);
                        self::validate($sdata);
                    $data[] = $sdata[0];
                }
                self::validate($data);/**/
            }
        }
        else {
            throw new \Exception(__('No valid User Logged On!! Cannot get saved cards.'));
        }

        return (array)$data;
    }

    /** returns true or false if stored cards are enabled
     *
     * @return bool
     */
    public static function getCanStoreCards() {
        $retVal = (int)0;
        if (Customer::isLoggedIn()) {
            $conn = Db::db_connect();
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
                $select = $conn->select()
                    ->from(
                        ['o' => 'core_config_data']
                    )
                    ->where('o.path = ?', (string)'payment/hps_heartland/save_cards');
                $data = (array)$conn->fetchAll($select);

                $retVal = (int)$data[0]['value'];
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
    public static function setStoredCards($token, $cc_type, $last4, $cc_exp_month, $cc_exp_year) {
        /*$args = func_get_args();
        foreach ($args as $argName=>$arg) {
            if ( preg_match('/[\W]/', $arg) !== 1){
                throw new \Exception(__( $argName . ' must contain a value that includes only word characters' ));
            }
        }*/
        $conn = Db::db_connect();
        if (Customer::isLoggedIn()) {
            if ($conn->isTableExists($conn->getTableName(self::TABLE_NAME))) {
                $conn->insert(self::TABLE_NAME, Array(
                        'heartland_storedcard_id' => '',
                        'dt'            => date("Y-m-d H:i:s"),
                        'customer_id'   => Customer::getCustID(),
                        'token_value'   => (string)$token,
                        'cc_type'       => (string)$cc_type,
                        'cc_last4'      => (string)$last4,
                        'cc_exp_month'  => (string)$cc_exp_month,
                        'cc_exp_year'   => (string)$cc_exp_year,
                    )
                );
            }
        }
        else {
            throw new \Exception(__('No valid User Logged On!! Cannot save card.'));
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    private static function validate($data) {

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

        return true;
    }
}
