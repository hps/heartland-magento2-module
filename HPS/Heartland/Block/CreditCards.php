<?php
/**
 * Created by PhpStorm.
 * User: ttnnkkrr
 * Date: 12/19/16
 * Time: 6:08 PM
 */

namespace HPS\Heartland\Block;

use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\CustomerTokenManagement;

class CreditCards extends \Magento\Vault\Block\CreditCards
{
    /**
     * @param PaymentTokenInterface $token
     * @return string
     */
    public function renderTokenHtml(PaymentTokenInterface $token)
    {

        foreach ($this->getChildNames() as $childName) {
            $childBlock = $this->getChildBlock($childName);
            if ($childBlock instanceof TokenRendererInterface && $childBlock->canRender($token)) {
                return $childBlock->render($token);
            }
        }
        return $this->render('{}');
    }
}
