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

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

class TokenData extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'hps_heartland_storedcard';

    /**
     * Define resource model
     */
    public function _construct()
    {
        $this->_init('HPS\Heartland\Model\ResourceModel\TokenData');
    }
    
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
