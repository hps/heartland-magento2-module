<?php
/**
 * Created by PhpStorm.
 * User: charles.simmons
 * Date: 3/23/2016
 * Time: 4:16 PM
 */

namespace app\code\HPS\Heartland\Model\Config\Source\Order\Status\Processing;
use \Magento\Sales\Model\Config\Source\Order\Status\Processing as OProcess;

class Processing  extends OProcess
{

    public function toOptionArray()
    {
        $statuses = $this->_stateStatuses
            ? $this->_orderConfig->getStateStatuses($this->_stateStatuses)
            : $this->_orderConfig->getStatuses();

        foreach ($statuses as $code => $label) {
            $options[] = ['value' => $code, 'label' => $label];
        }
        return $options;
    }
}