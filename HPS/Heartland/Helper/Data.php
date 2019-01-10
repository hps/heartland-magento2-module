<?php

/**
 *  Heartland payment method model
 *
 * @category    HPS
 * @package     HPS_Heartland
 * @author      Heartland Developer Portal <EntApp_DevPortal@e-hps.com>
 * @copyright   Heartland (http://heartland.us)
 * @license     https://github.com/hps/heartland-magento2-module/blob/master/LICENSE.md
 */

namespace HPS\Heartland\Helper;

/**
 * Class Data
 *
 * @package HPS\Heartland\Helper
 */

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\Filesystem\Driver\File;

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
    private $publicKey = 'payment/hps_heartland/public_key';
    private $saveCards = 'payment/hps_heartland/save_cards';

    /**
     *
     */
    private $publicKeyPattern = '/^pkapi\_(cert|)[\w]{5,245}$/';
    
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;
    
    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    private $storeManagerInterface;
    
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $fileSystem;
  
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Filesystem\Driver\File $fileSystem
    ) {
        $this->directoryList = $directoryList;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->fileSystem = $fileSystem;
        parent::__construct($context);
    }

    /**
     * @param $config_path
     *
     * @return string
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            (string) $config_path,
            (string) ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     * @throws \Magento\Framework\Validator\Exception
     */
    public function getPublicKey()
    {
        $pubKey = (string) $this->getConfig((string)$this->publicKey);
        if (preg_match($this->publicKeyPattern, (string) $pubKey) !== (int) 1) {
            throw new \Magento\Framework\Validator\Exception(
                __((string)'Improperly configured public key found at core_config_data{ path = '.$this->publicKey.' }')
            );
        }
        return (string) $pubKey;
    }
    public function getCanSave()
    {
        return (int) $this->getConfig($this->saveCards);
    }

    /** Customer facing will generate JSON input while admin side will send post this function returns the relevent
     * payment data either way
     * @return array
     */
    public function jsonData()
    {
        $inputs = json_decode((string) $this->fileSystem->fileGetContents((string)'php://input'), (bool) true);
        $methods = $this->_request->getServer('REQUEST_METHOD');
        
        if (empty($inputs) === true && $methods === 'POST') {
            $post = $this->_request->getPostValue();
                       
            if (array_key_exists('payment', $post)) {
                $inputs['paymentMethod']['additional_data'] = $post['payment'];
            }

            if (array_key_exists('securesubmit_token', $post)) {
                $inputs['paymentMethod']['additional_data']['token_value'] = $post['securesubmit_token'];
            }
        }

        return (array) $inputs;
    }
    public function getRoot()
    {
        return (string) $this->directoryList->getRoot();
    }
    public function getBaseUrl()
    {
        return (string) $this->storeManagerInterface->getStore()->getBaseUrl();
    }

    public function getCurrencyCode()
    {
        return (string) $this->storeManagerInterface->getStore()->getCurrentCurrency()->getCode();
    }
}
