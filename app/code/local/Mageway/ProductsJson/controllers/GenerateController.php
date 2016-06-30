<?php

/**
 * Class Mageway_ProductsJson_GenerateController
 */
class Mageway_ProductsJson_GenerateController extends Mage_Core_Controller_Front_Action {

    /**
     * Number of products to display
     *
     * @var int
     */
    protected $_productsLimit = 30;

    /**
     * Attribute to sort products by
     *
     * @var string
     */
    protected $_sortAttr = "created_at";

    /**
     * Sorting direction
     *
     * @var string
     */
    protected $_sortOrder = Varien_Data_Collection::SORT_ORDER_DESC;

    /**
     * Attributes that will be included in the generated json
     *
     * @var array
     */
    protected $_outputAttributes = array
    (
        "name",
        "description",
        "price",
        "images",
    );

    /**
     * Action to generate json with products
     */
    public function productsAction()
    {
        /* @var Mage_Catalog_Model_Resource_Product_Collection $products_collection */
        $products_collection = Mage::getResourceModel("catalog/product_collection");
        $products_collection->addAttributeToSelect($this->_outputAttributes)
            ->addAttributeToSort($this->_sortAttr, $this->_sortOrder)
            ->setPageSize($this->_productsLimit)
            ->setCurPage(1);
        if(in_array("images", $this->_outputAttributes))
        {
            $media_backend_model = $products_collection->getResource()->getAttribute('media_gallery')->getBackend();
        }

        // Create products array that will be converted to json
        $products = array();
        foreach($products_collection as $product)
        {
            $prod_id = $product->getId();

            // Loop through attributes and add the values to the array
            foreach($this->_outputAttributes as $attr_code)
            {
                switch($attr_code)
                {
                    case "price":
                        $value = floatval($product->getData($attr_code));
                        break;
                    case "images":
                        $media_backend_model->afterLoad($product);
                        $value = array();
                        foreach($product->getMediaGalleryImages() as $image)
                        {
                            $value[] = $image->getUrl();
                        }
                        break;
                    default:
                        $value = $product->getData($attr_code);
                        break;
                }
                $products[$prod_id][$attr_code] = $value;
            }
        }

        // Send the response as json
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($products));
    }

}