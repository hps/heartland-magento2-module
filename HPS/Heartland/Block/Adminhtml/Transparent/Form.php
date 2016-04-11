<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace HPS\Heartland\Block\Adminhtml\Transparent;

class Form extends \HPS\Heartland\Block\Transparent\Form
{
    /**
     * On backend this block does not have any conditional checks
     *
     * @return bool
     */
    protected function shouldRender()
    {
        return true;
    }

    /**
     * {inheritdoc}
     */
    protected function initializeMethod()
    {
        return;
    }
}
