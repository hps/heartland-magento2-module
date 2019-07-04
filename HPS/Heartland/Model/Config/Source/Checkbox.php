<?php
/**
 *  Heartland Checkbox
 *
 * @category    HPS
 * @package     HPS_Heartland
 * @author      Heartland Developer Portal <EntApp_DevPortal@e-hps.com>
 * @copyright   Heartland (http://heartland.us)
 * @license     https://github.com/hps/heartland-magento2-module/blob/master/LICENSE.md
 */
namespace HPS\Heartland\Model\Config\Source;

/**
 * Used in creating options for getting product type value
 *
 */
class Checkbox
{
    /**
     * Options getter
     *
     * @return array
     */
    public static function toOptionArray($withEmpty = true, $defaultValues = false)
    {
        return [['value' => 'yes', 'label'=>__('')]];
    }
}