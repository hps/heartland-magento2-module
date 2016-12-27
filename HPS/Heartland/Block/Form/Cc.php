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
use Magento\Payment\Model\MethodInterface;

/**
 * Class Cc
 * @property \Magento\Framework\View\Element\Template\Context $context,
 * @property \Magento\Payment\Model\Config $paymentConfig,
 * @const \Magento\Payment\Block\Form parent
 * @package HPS\Heartland\Block\Form
 */

class Cc extends \Magento\Payment\Block\Form\Cc
{
    protected $_template = 'HPS_Heartland::form/cc.phtml';

    /** in context gets stored cards from database for the selected customer
     * @return array
     * @throws \Exception
     */
    public function getCcTokens(){

        return \HPS\Heartland\Model\StoredCard::getStoredCardsAdmin($this->getData('method')->getData('info_instance')->getQuote()->getOrigData('customer_id'));
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml() {
        return parent::_toHtml(); // TODO: Change the autogenerated stub
    }

    /**
     * Convert array of object data with to array with keys requested in $keys array
     *
     * @param array $keys array of required keys
     *
     * @return array
     */
    public function toArray(array $keys = []) {
        return parent::toArray($keys); // TODO: Change the autogenerated stub
    }

    /**
     * The "__" style wrapper for toArray method
     *
     * @param  array $keys
     *
     * @return array
     */
    public function convertToArray(array $keys = []) {
        return parent::convertToArray($keys); // TODO: Change the autogenerated stub
    }

    /**
     * Convert object data into XML string
     *
     * @param array  $keys       array of keys that must be represented
     * @param string $rootName   root node name
     * @param bool   $addOpenTag flag that allow to add initial xml node
     * @param bool   $addCdata   flag that require wrap all values in CDATA
     *
     * @return string
     */
    public function toXml(array $keys = [], $rootName = 'item', $addOpenTag = false, $addCdata = true) {
        return parent::toXml($keys, $rootName, $addOpenTag, $addCdata); // TODO: Change the autogenerated stub
    }

    /**
     * The "__" style wrapper for toXml method
     *
     * @param array  $arrAttributes array of keys that must be represented
     * @param string $rootName      root node name
     * @param bool   $addOpenTag    flag that allow to add initial xml node
     * @param bool   $addCdata      flag that require wrap all values in CDATA
     *
     * @return string
     */
    public function convertToXml(
        array $arrAttributes = [],
        $rootName = 'item',
        $addOpenTag = false,
        $addCdata = true
    ) {
        return parent::convertToXml($arrAttributes, $rootName, $addOpenTag, $addCdata); // TODO: Change the autogenerated stub
    }

    /**
     * Convert object data to JSON
     *
     * @param array $keys array of required keys
     *
     * @return string
     */
    public function toJson(array $keys = []) {
        return parent::toJson($keys); // TODO: Change the autogenerated stub
    }

    /**
     * The "__" style wrapper for toJson
     *
     * @param  array $keys
     *
     * @return string
     */
    public function convertToJson(array $keys = []) {
        return parent::convertToJson($keys); // TODO: Change the autogenerated stub
    }

    /**
     * Convert object data into string with predefined format
     *
     * Will use $format as an template and substitute {{key}} for attributes
     *
     * @param string $format
     *
     * @return string
     */
    public function toString($format = '') {
        return parent::toString($format); // TODO: Change the autogenerated stub
    }

    /**
     * Retrieve payment method model
     *
     * @return MethodInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMethod() {
        return parent::getMethod(); // TODO: Change the autogenerated stub
    }

    /**
     * Sets payment method instance to form
     *
     * @param MethodInterface $method
     *
     * @return $this
     */
    public function setMethod(MethodInterface $method) {
        return parent::setMethod($method); // TODO: Change the autogenerated stub
    }

    /**
     * Retrieve payment method code
     *
     * @return string
     */
    public function getMethodCode() {
        return parent::getMethodCode(); // TODO: Change the autogenerated stub
    }

    /**
     * Retrieve field value data from payment info object
     *
     * @param   string $field
     *
     * @return  mixed
     */
    public function getInfoData($field) {
        return parent::getInfoData($field); // TODO: Change the autogenerated stub
    }

    /**
     * Set template context. Sets the object that should represent $this in template
     *
     * @param \Magento\Framework\View\Element\BlockInterface $templateContext
     *
     * @return void
     */
    public function setTemplateContext($templateContext) {
        parent::setTemplateContext($templateContext); // TODO: Change the autogenerated stub
    }

    /**
     * Internal constructor, that is called from real constructor
     *
     * @return void
     */
    protected function _construct() {
        parent::_construct(); // TODO: Change the autogenerated stub
    }

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate() {
        return parent::getTemplate(); // TODO: Change the autogenerated stub
    }

    /**
     * Set path to template used for generating block's output.
     *
     * @param string $template
     *
     * @return $this
     */
    public function setTemplate($template) {
        return parent::setTemplate($template); // TODO: Change the autogenerated stub
    }

    /**
     * Get absolute path to template
     *
     * @param string|null $template
     *
     * @return string
     */
    public function getTemplateFile($template = null) {
        return parent::getTemplateFile($template); // TODO: Change the autogenerated stub
    }

    /**
     * Get design area
     *
     * @return string
     */
    public function getArea() {
        return parent::getArea(); // TODO: Change the autogenerated stub
    }

    /**
     * Assign variable
     *
     * @param   string|array $key
     * @param   mixed        $value
     *
     * @return  $this
     */
    public function assign($key, $value = null) {
        return parent::assign($key, $value); // TODO: Change the autogenerated stub
    }

    /**
     * Retrieve block view from file (template)
     *
     * @param string $fileName
     *
     * @return string
     */
    public function fetchView($fileName) {
        return parent::fetchView($fileName); // TODO: Change the autogenerated stub
    }

    /**
     * Get base url of the application
     *
     * @return string
     */
    public function getBaseUrl() {
        return parent::getBaseUrl(); // TODO: Change the autogenerated stub
    }

    /**
     * Get data from specified object
     *
     * @param \Magento\Framework\DataObject $object
     * @param string                        $key
     *
     * @return mixed
     */
    public function getObjectData(\Magento\Framework\DataObject $object, $key) {
        return parent::getObjectData($object, $key); // TODO: Change the autogenerated stub
    }

    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo() {
        return parent::getCacheKeyInfo(); // TODO: Change the autogenerated stub
    }

    /**
     * Instantiates filesystem directory
     *
     * @return \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected function getRootDirectory() {
        return parent::getRootDirectory(); // TODO: Change the autogenerated stub
    }

    /**
     * Get media directory
     *
     * @return \Magento\Framework\Filesystem\Directory\Read
     */
    protected function getMediaDirectory() {
        return parent::getMediaDirectory(); // TODO: Change the autogenerated stub
    }
}
