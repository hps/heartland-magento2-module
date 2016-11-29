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
 * Date: 3/28/2016
 * Time: 11:47 PM
 */
namespace HPS\Heartland\Block\Form;
class Cc extends \Magento\Payment\Block\Form\Cc
{

    protected $_template = 'Magento_Payment::form/cc.phtml';

}