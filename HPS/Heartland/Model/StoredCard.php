<?php
/**
 * Created by PhpStorm.
 * User: charles.simmons
 * Date: 3/16/2016
 * Time: 12:43 PM
 */

namespace HPS\Heartland\Model;
use \HPS\Heartland\Helper\Customer;
use \HPS\Heartland\Helper\Db;


/**
 * Class StoredCard
 *
 * @package HPS\Heartland\Model
 */
class StoredCard
{
    /**
     * @param int $id
     *
     * @return bool
     * @throws \Exception
     */    
    public static function getToken($id){
        $MuToken = false;
        if (Customer::isLoggedIn()) {
            $conn = Db::db_connect();
            $select = $conn->select()
                ->from(
                    ['o' => 'hps_heartland_storedcard']
                )
                ->where('o.customer_id   = ?', (int)Customer::getCustID())
                ->where('o.storedcard_id = ?', (int)$id);
            $data = (array)$conn->fetchAll($select);
            self::validate($data);
            if (count($data) && key_exists('token_value', $data[0])) {
                $MuToken = $data[0]['token_value'];
            }
        }
        return $MuToken;
    }

    public static function deleteStoredCards($id){
        if (Customer::isLoggedIn()) {
            $conn = Db::db_connect();
            $conn->delete('hps_heartland_storedcard', array(
                'customer_id = ?' => (int) Customer::getCustID(),
                'storedcard_id = ?' => (int) $id
            ));
        }else{
            throw new \Exception(__( 'No valid User Logged On!! Cannot save card.' ));
        }
    }
    /**
     * @return array
     *
     *
     * @throws \Exception
     */
    public static function getStoredCards(){
        $data = [];

        if (Customer::isLoggedIn()) {
            $conn = Db::db_connect();
            $select = $conn->select()
                ->from (
                    ['o' => 'hps_heartland_storedcard']
                )
                ->where ('o.customer_id = ?', (int) Customer::getCustID());
            $data = (array) $conn->fetchAll($select);
            self::validate($data);
        }else{
            throw new \Exception(__( 'No valid User Logged On!! Cannot get saved cards.' ));
        }
        return (array) $data;
    }
    public static function getCanStoreCards(){
        $retVal = (int)0;
        if (Customer::isLoggedIn()) {
            $conn = Db::db_connect ();
            $select = $conn->select ()
                ->from (
                    ['o' => 'core_config_data']
                )
                ->where ('o.path = ?', (string)'payment/hps_heartland/save_cards');
            $data = (array)$conn->fetchAll ($select);

            $retVal = (int)$data[0]['value'];
        }
        return $retVal;
    }

    /**
     * @param string $token
     * @param string $cc_type
     * @param string $last4
     * @param string $cc_exp_month
     * @param string $cc_exp_year
     *
     * @throws \Exception
     */
    public static function setStoredCards($token, $cc_type, $last4, $cc_exp_month, $cc_exp_year){
        /*$args = func_get_args();
        foreach ($args as $argName=>$arg) {
            if ( preg_match('/[\W]/', $arg) !== 1){
                throw new \Exception(__( $argName . ' must contain a value that includes only word characters' ));
            }
        }*/
        if (Customer::isLoggedIn()) {
            Db::db_connect()->insert('hps_heartland_storedcard', Array(
                    'storedcard_id' => '',
                    'dt' => date("Y-m-d H:i:s"),
                    'customer_id' => Customer::getCustID(),
                    'token_value' => (string) $token,
                    'cc_type' => (string) $cc_type,
                    'cc_last4' => (string) $last4,
                    'cc_exp_month' => (string) $cc_exp_month,
                    'cc_exp_year' => (string) $cc_exp_year,
                )
            );
        }else{
            throw new \Exception(__( 'No valid User Logged On!! Cannot save card.' ));
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws \Exception
     */
    private static function validate($data){

        // some very basic validation.
        //simply dont want invalid arrays of data
        foreach ($data as $item) {
            if (preg_match ('/[\D]/', $item['storedcard_id']) === 1) {
                throw new \Exception(__ ('storedcard_id does not have a valid value.'));
            }
            foreach ($item as $columnName => $columnValue) {
                if ($columnValue === null || $columnValue === '' || preg_match ('/[^\w\s\-\:]/', $columnValue) === 1) {
                    throw new \Exception(__ ($columnName . ' Column does not have a valid value for ' . $item['storedcard_id']));
                }
            }
        }
        return true;
    }
}
