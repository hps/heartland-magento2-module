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
namespace HPS\Heartland\Model\Source;

use \HPS\Heartland\Model\PaymentMethod;

/**
 * Class CaptureAction
 * @codeCoverageIgnore
 */
class CaptureAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Possible actions to capture
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => PaymentMethod::CAPTURE_ON_INVOICE,
                'label' => __('Invoice'),
            ],
            [
                'value' => PaymentMethod::CAPTURE_ON_SHIPMENT,
                'label' => __('Shipment'),
            ],
        ];
    }
}
