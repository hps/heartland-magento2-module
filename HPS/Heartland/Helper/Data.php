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
    
    const AMOUNT_PRECISION = 2;


    /**
     * @param $config_path
     *
     * @return string
     */
    public static function getConfig($config_path)
    {
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
    public static function getPublicKey()
    {
        $pubKey = (string) self::getConfig((string)self::P_KEY);
        if (preg_match(self::P_KEY_PATTERN, (string) $pubKey) !== (int) 1) {
            throw new \Magento\Framework\Validator\Exception(__((string)'Improperly configured public key found at core_config_data{ path = '.self::P_KEY.' }'));
        }
        return (string) $pubKey;
    }
    public static function getCanSave()
    {
        return (int) self::getConfig(self::S_CARDS);
    }

    /** Customer facing will generate JSON input while admin side will send post this function returns the relevent
     * payment data either way
     * @return array
     */
    public static function jsonData()
    {

        $inputs = json_decode((string) file_get_contents((string)'php://input'), (bool) true);        

        if (empty($inputs) === true && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = HPS_OM::getObjectManager()->get('Magento\Framework\App\RequestInterface')->getPostValue();
                       
            if (array_key_exists('payment', $post)) {
                $inputs['paymentMethod']['additional_data'] = $post['payment'];
            }

            if (array_key_exists('securesubmit_token', $post)) {
                $inputs['paymentMethod']['additional_data']['token_value'] = $post['securesubmit_token'];
            }
        }

        return (array) $inputs;
    }
    public static function getRoot()
    {
        return (string) HPS_OM::getObjectManager()->get(self::CLASS_DIRECTORY_LIST)->getRoot();
    }
    public static function getBaseUrl()
    {
        return (string) HPS_OM::getObjectManager()->get(self::CLASS_STOREMANAGERINTERFACE)->getStore()->getBaseUrl();
    }

    public static function getCurrencyCode()
    {
        return (string) HPS_OM::getObjectManager()->get(self::CLASS_STOREMANAGERINTERFACE)->getStore()->getCurrentCurrency()->getCode();
    }
    
    public static function formatNumber2Precision($number){
        return number_format($number, self::AMOUNT_PRECISION);
    }
}
