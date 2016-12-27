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
 * Date: 2/22/2016
 * Time: 1:24 PM
 */

namespace HPS\Heartland\Helper;


/**
 * Class Data
 *
 * @package HPS\Heartland\Helper
 */

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\ScopeInterface;
use \HPS\Heartland\Helper\ObjectManager as HPS_OM;


/**
 * Class Data
 *
 * @package HPS\Heartland\Helper
 */
class Data extends AbstractHelper
{
    /**
     *
     */
    const P_KEY = 'payment/hps_heartland/public_key';
    const S_CARDS = 'payment/hps_heartland/save_cards';

    /**
     *
     */
    const CLASS_DIRECTORY_LIST = '\Magento\Framework\App\Filesystem\DirectoryList';
    /**
     *
     */
    const CLASS_STOREMANAGERINTERFACE = 'Magento\Store\Model\StoreManagerInterface';


    /**
     *
     */
    const P_KEY_PATTERN = '/^pkapi\_(cert|)[\w]{5,245}$/';

    /**
     * @param $config_path
     *
     * @return string
     */
    public static function getConfig($config_path){
        return HPS_OM::getObjectManager()
                ->get((string) self::class)
                ->scopeConfig
                ->getValue(
                            (string) $config_path,
                            (string) ScopeInterface::SCOPE_STORE
                    );
    }

    /**
     * @return string
     * @throws \Magento\Framework\Validator\Exception
     */
    public static function getPublicKey(){
        $pubKey = (string) self::getConfig((string)self::P_KEY);
        if (preg_match( self::P_KEY_PATTERN,(string) $pubKey) !== (int) 1){
            throw new \Magento\Framework\Validator\Exception( __( (string)'Improperly configured public key found at core_config_data{ path = '.self::P_KEY.' }' ) );
        }
        return (string) $pubKey;
    }
    public static function getCanSave(){
        return (int) self::getConfig(self::S_CARDS);
    }
    /**
     * @return array
     */
    public static function jsonData()    {

        $inputs = json_decode((string) file_get_contents((string)'php://input'),(bool) true);;
        if (empty($inputs) === true){
            $inputs['paymentMethod']['additional_data'] = $_POST['payment'];;
            $inputs['paymentMethod']['additional_data']['token_value'] = ‌‌$_POST['securesubmit_token'];;
        }


        return (array) $inputs;
    }
    public static function getRoot()    {
        return (string) HPS_OM::getObjectManager()->get(self::CLASS_DIRECTORY_LIST)->getRoot();
    }
    public static function getBaseUrl()    {
        return (string) HPS_OM::getObjectManager()->get(self::CLASS_STOREMANAGERINTERFACE)->getStore()->getBaseUrl();
    }

    public static function getCurrencyCode() {
        return (string) HPS_OM::getObjectManager()->get(self::CLASS_STOREMANAGERINTERFACE)->getStore()->getCurrentCurrency()->getCode();
    }
}



