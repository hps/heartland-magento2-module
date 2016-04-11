<?php
/**
 * Payment CC Types Source Model
 *
 * @category	Inchoo
 * @package		Inchoo_Stripe
 * @author		Ivan Weiler <ivan.weiler@inchoo.net> & Stjepan Udovičić <stjepan.udovicic@inchoo.net>
 * @copyright	Inchoo (http://inchoo.net)
 * @license		https://github.com/hps/heartland-magento2-extension/blob/master/LICENSE.md
 */

namespace HPS\Heartland\Model\Source;

class CcType extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * @return array
     */
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DI', 'JCB', 'OT');
    }
}
