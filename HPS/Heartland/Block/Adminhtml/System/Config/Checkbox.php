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

namespace HPS\Heartland\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Checkbox extends \Magento\Config\Block\System\Config\Form\Field
{
    const CONFIG_PATH = 'payment/hps_heartland/require_exp_cvv';

    protected $_template = 'HPS_Heartland::system/config/checkbox.phtml';

    protected $_values = null;

    /**
     * Checkbox constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    /**
     * Retrieve element HTML markup.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());

        return $this->_toHtml();
    }

    public function getValues()
    {
        $values = [];
        $optionArray = \HPS\Heartland\Model\Config\Source\Checkbox::toOptionArray();
        foreach ($optionArray as $value) {
            $values[$value['value']] = $value['label'];
        }
        return $values;
    }

    /**
     * Get checked value.
     * @param  $name
     * @return boolean
     */
    public function getIsChecked($name)
    {
        return in_array($name, $this->getCheckedValues());
    }
    /**
     *
     * Retrieve the checked values from config
     */
    public function getCheckedValues()
    {
        if (is_null($this->_values)) {
            $data = $this->getConfigData();
            if (isset($data[self::CONFIG_PATH])) {
                $data = $data[self::CONFIG_PATH];
            } else {
                $data = '';
            }
            $this->_values = explode(',', $data);
        }
        return $this->_values;
    }
}

