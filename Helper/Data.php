<?php

namespace Cadence\Pinterest\Helper;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;


class Data extends AbstractHelper{

	/** @var \Magento\Checkout\Model\Session $_checkoutSession */
	protected $_checkoutSession;
	/** @var \Magento\Sales\Model\OrderFactory $_orderFactory */
	protected $_orderFactory;
	/** @var ScopeConfigInterface $_scopeConfig */
	protected $_scopeConfig;
	/** @var \Magento\Sales\Model\Order $_order */
	protected $_order;
	/** @var \Magento\Catalog\Model\ProductRepository $_productRepository */
	protected $_productRepository;
	/** @var \Magento\Store\Model\StoreManagerInterface $_storeManager */
	protected $_storeManager;
	/** @var \Cadence\Pinterest\Model\Session $_pinterestSession */
	protected $_pinterestSession;

	public function __construct(
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Catalog\Model\ProductRepository $productRepository,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\App\Helper\Context $context,
		\Cadence\Pinterest\Model\Session $_pinterestSession
	) {
		$this->_checkoutSession = $checkoutSession;
		$this->_orderFactory = $orderFactory;
		$this->_scopeConfig = $context->getScopeConfig();
		$this->_productRepository = $productRepository;
		$this->_storeManager = $storeManager;
		$this->_pinterestSession = $_pinterestSession;

		parent::__construct( $context );
	}

	public function isVisitorPixelEnabled()
	{
		return $this->_scopeConfig->getValue('cadence_pinterest/visitor/enabled');
	}
	public function isConversionPixelEnabled()
	{
		return $this->_scopeConfig->getValue("cadence_pinterest/conversion/enabled");
	}

	public function isAddToCartPixelEnabled()
	{
		return $this->_scopeConfig->getValue("cadence_pinterest/add_to_cart/enabled");
	}

	public function getVisitorPixelId()
	{
		return $this->_scopeConfig->getValue("cadence_pinterest/visitor/pixel_id");
	}

	/**
	 * @param $event
	 * @param $data
	 * @return string
	 */
	public function getPixelHtml($event, $data = false)
	{
        $json = '';
        if ($data) {
            $json = ', ' . json_encode($data);
        }
        $html = <<<HTML
    <!-- Begin Pinterest {$event} -->
    <script type="text/javascript">
        pintrk('track', '{$event}'{$json});
    </script>
    <!-- End Pinterest {$event} -->
HTML;
        return $html;
	}

	public function getOrderIDs()
	{
		$orderIDs = array();

		/** @var \Magento\Sales\Model\Order\Item $item */
		foreach($this->getOrder()->getAllVisibleItems() as $item){
			$product = $this->_productRepository->getById($item->getProductId());
			$orderIDs = array_merge($orderIDs, $this->_getProductTrackID($product));
		}

		return json_encode(array_unique($orderIDs));
	}

	public function getOrder(){
		if(!$this->_order){
			$this->_order = $this->_checkoutSession->getLastRealOrder();
		}

		return $this->_order;
	}

	protected function _getProductTrackID($product)
	{
		$productType = $product->getTypeID();

		if($productType == "grouped") {
			return $this->_getProductIDs($product);
		} else {
			return $this->_getProductID($product);
		}
	}

	protected function _getProductID($product)
	{
		return array(
			$product->getSku()
		);
	}
    
    protected function _getProductIDs($product)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $group = $product->getTypeInstance()->setProduct($product);
        /** @var \Magento\GroupedProduct\Model\Product\Type\Grouped $group */
        $group_collection = $group->getAssociatedProductCollection($product);
        $ids = array();

        foreach ($group_collection as $group_product) {

            $ids[] = $this->_getProductID($group_product);
        }

        return $ids;
    }

	public function getCurrencyCode(){
		return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
	}

	public function getSession(){
		return $this->_pinterestSession;
	}

	public function getTagId()
    {
        return $this->_scopeConfig->getValue("cadence_pinterest/visitor/tag_id");
    }
    
    public function getOrderItemsQty()
    {
        $order = $this->_order;

        $qty = 0;

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach($order->getAllVisibleItems() as $item) {
            $qty += $item->getQtyOrdered();
        }

        return max(round($qty), 1);
    }

    /**
     * @return string
     */
    public function getOrderItemsJson()
    {
        $order = $this->_order;

        $itemData = array();

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach($order->getAllVisibleItems() as $item) {
            $qty = max(round($item->getQtyOrdered()), 1);
            $itemData[] = [
                "product_name" => $item->getName(),
                "product_id" => $item->getSku(),
                "product_price" => round($item->getPrice(),2),
                "product_quantity" => $qty
            ];
        }

        return json_encode($itemData);
    }
}