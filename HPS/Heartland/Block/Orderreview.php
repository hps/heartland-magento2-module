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

namespace HPS\Heartland\Block;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\ObjectManager as HPS_OM;

class Orderreview extends \Magento\Framework\View\Element\Template {

    /**
     * Internal constructor, that is called from real constructor
     *
     * @return void
     */
    protected function _construct() {
        parent::_construct();
    }

    public function getOrderId() {
        $lid = filter_input(INPUT_GET, 'oid');
        $objectManager = HPS_OM::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->load($lid);
        return $order;
    }

   public function getProductId() {
        $order = $this->getOrderId();
        $items = $order->getAllItems();
        $objectManager = HPS_OM::getInstance();
        $product = array();
        if(!empty($items)){
            foreach ($items as $i){
                $product[] = $objectManager->create('Magento\Catalog\Model\Product')->load($i->getProductId());
            }
        }
        return $product;
    }

}
