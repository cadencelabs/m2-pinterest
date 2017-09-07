<?php

namespace Cadence\Pinterest\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesQuoteProductAddAfter implements ObserverInterface {

	/** @var \Cadence\Pinterest\Model\Session $_pinterestSession */
	protected $_pinterestSession;
	/** @var  \Cadence\Pinterest\Helper\Data $_helper */
	protected $_helper;

	public function __construct(
		\Cadence\Pinterest\Model\Session $pinterestSession,
		\Cadence\Pinterest\Helper\Data $helper
	) {
		$this->_pinterestSession = $pinterestSession;
		$this->_helper         = $helper;
	}
    
    public function execute( \Magento\Framework\Event\Observer $observer ) {
        if (!$this->_helper->isAddToCartPixelEnabled()) {
            return $this;
        }

        $items = $observer->getItems();

        $candidates = array_replace(array(
            'value' => 0.00,
            'order_quantity' => 0,
            'line_items' => array()
        ), $this->_pinterestSession->getAddToCart() ?: array());

        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($items as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            $candidates['value'] += $item->getProduct()->getFinalPrice() * $item->getProduct()->getQty();
            $candidates['order_quantity'] += $item->getProduct()->getQty();
            $candidates['line_items'][] = [
                "product_name" => $item->getName(),
                "product_id" => $item->getSku(),
                "product_price" => round($item->getProduct()->getFinalPrice(),2),
                "product_quantity" => max(round($item->getProduct()->getQty()), 1)
            ];
        }

        // Ensure the quantity is a whole integer
        $data = array(
            'value' => round($candidates['value'],2),
            'order_quantity' => max(round($candidates['order_quantity']), 1),
            'currency' => $this->_helper->getCurrencyCode(),
            'line_items' => $candidates['line_items']
        );

        $this->_pinterestSession->setAddToCart($data);

        return $this;
    }
}