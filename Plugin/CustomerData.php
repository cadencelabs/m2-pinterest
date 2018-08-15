<?php

namespace Cadence\Pinterest\Plugin;

use Magento\Customer\Helper\Session\CurrentCustomer;

class CustomerData {
    
    protected $_currentCustomer;
    
    public function __construct(
        CurrentCustomer $currentCustomer
    ) {
        $this->_currentCustomer = $currentCustomer;
    }
    
    public function afterGetSectionData(\Magento\Customer\CustomerData\Customer $subject, $result) {
        if($this->_currentCustomer->getCustomerId()) {
            $customer = $this->_currentCustomer->getCustomer();
            $result['email'] = $customer->getEmail();
        }
        return $result;
    }
}