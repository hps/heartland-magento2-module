<?php

namespace HPS\Heartland\Model\Helper;

use Magento\Framework\Model\AbstractModel;
use Magento\Checkout\Model\Session;
use Magento\Framework\Model\Context as Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

class FraudManagementHelper extends AbstractModel
{
    const FRAUD_TEXT_DEFAULT              = '%s';
    const FRAUD_VELOCITY_ATTEMPTS_DEFAULT = 3;
    const FRAUD_VELOCITY_TIMEOUT_DEFAULT  = 10;
    const DEFAULT_PATH_PATTERN = 'payment/%s/%s';
    const METHOD_CODE = 'hps_heartland';
    
    protected $_fraud_velocity_attempts = null;
    protected $_fraud_velocity_timeout  = null;
    protected $_enable_anti_fraud       = null;
    protected $_fraud_text              = null;
    protected $_helper                  = null;
    
    /**
     * @var GlobalPayments\PaymentGateway\Gateway\Config
     */
    protected $config;
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;
    
    
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource     
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \HPS\Heartland\Helper\Data|null $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Session $session, 
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        \HPS\Heartland\Helper\Data $helper = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        
        $this->_helper = $helper;
        $this->_checkoutSession = $session;
    }
    
    public function getFraudSettings()
    {
        if ($this->_enable_anti_fraud === null) {            
            $this->_enable_anti_fraud       = (bool) $this->getConfig('enable_anti_fraud');
            $this->_fraud_text              = $this->getConfig('fraud_text');
            $this->_fraud_velocity_attempts = (int) $this->getConfig('fraud_velocity_attempts');
            $this->_fraud_velocity_timeout  = (int) $this->getConfig('fraud_velocity_timeout');
            
            if ($this->_fraud_text === null) {
                $this->_fraud_text = self::FRAUD_TEXT_DEFAULT;
            }
            
            if ($this->_fraud_velocity_attempts === null
                || !is_numeric($this->_fraud_velocity_attempts)
                ) {
                    $this->_fraud_velocity_attempts = self::FRAUD_VELOCITY_ATTEMPTS_DEFAULT;
                }
                
                if ($this->_fraud_velocity_timeout === null
                    || !is_numeric($this->_fraud_velocity_timeout)
                    ) {
                        $this->_fraud_velocity_timeout = self::FRAUD_VELOCITY_TIMEOUT_DEFAULT;
                    }
        }
    }
    
    protected function maybeResetVelocityTimeout()
    {
        $timeoutExpiration = (int)$this->getVelocityVar('TimeoutExpiration');
                
        if (time() < $timeoutExpiration) {
            return;
        }
        
        $this->unsVelocityVar('Count');
        $this->unsVelocityVar('IssuerResponse');
        $this->unsVelocityVar('TimeoutExpiration');
    }
    
    public function checkVelocity()
    {
        if ($this->_enable_anti_fraud !== true) {
            return;
        }
        
        $this->maybeResetVelocityTimeout();
        
        $count = (int)$this->getVelocityVar('Count');
        $issuerResponse = (string)$this->getVelocityVar('IssuerResponse');
        $timeoutExpiration = (int)$this->getVelocityVar('TimeoutExpiration');
        
        if ($count >= $this->_fraud_velocity_attempts
            && time() < $timeoutExpiration) {
                sleep(5);
                throw new \HpsException(sprintf($this->_fraud_text, $issuerResponse));
            }
    }
    
    public function updateVelocity($e)
    {
        if ($this->_enable_anti_fraud !== true) {
            return;
        }
        
        $this->maybeResetVelocityTimeout();
        
        $count = (int)$this->getVelocityVar('Count');
        $issuerResponse = (string)$this->getVelocityVar('IssuerResponse');
        if ($issuerResponse !== $e->getMessage()) {
            $issuerResponse = $e->getMessage();
        }
        
        //                   NOW    + (fraud velocity timeout in seconds)
        $timeoutExpiration = time() + ($this->_fraud_velocity_timeout * 60);
        
        $this->setVelocityVar('Count', $count + 1);
        $this->setVelocityVar('IssuerResponse', $issuerResponse);
        $this->setVelocityVar('TimeoutExpiration', $timeoutExpiration);       
        
    }
    
    protected function getVelocityVar($var)
    {
        return $this->_checkoutSession
            ->getData($this->getVelocityVarPrefix() . $var);
    }
    
    protected function setVelocityVar($var, $data = null)
    {
        return $this->_checkoutSession
            ->setData($this->getVelocityVarPrefix() . $var, $data);
    }
    
    protected function unsVelocityVar($var)
    {
        return $this->_checkoutSession
        ->unsetData($this->getVelocityVarPrefix() . $var);
    }
    
    protected function getVelocityVarPrefix()
    {
        return sprintf('Heartland_Velocity%s', md5($this->getRemoteIP()));
    }

    protected function getConfig($key)
    {
        if ($this->_helper === null) {
            return null;
        }

        return $this->_helper->getConfig(sprintf(self::DEFAULT_PATH_PATTERN, self::METHOD_CODE, $key));
    }
    
    protected function getRemoteIP()
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $remoteIP = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $remoteIPArray = array_values(
                array_filter(
                    explode(
                        ',',
                        $_SERVER['HTTP_X_FORWARDED_FOR']
                        )
                    )
                );
            $remoteIP = end($remoteIPArray);
        } else {
            $remoteIP = $_SERVER['REMOTE_ADDR'];
        }
        return $remoteIP;
    }   
}
