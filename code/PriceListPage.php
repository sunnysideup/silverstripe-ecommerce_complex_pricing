<?php

/**
 *@author nicolaas[at]
 *@description
 *
 * Provides a price list
 *
 *
 *
 *
 **/

class PriceListPage extends ProductGroup
{

    /**
     * Standard SS variable.
     */
    private static $singular_name = "Price List Page";
    public function i18n_singular_name()
    {
        return _t("ProductGroup.PRICELISTPAGE", "Price List Page");
    }

    /**
     * Standard SS variable.
     */
    private static $plural_name = "Price List Pages";
    public function i18n_plural_name()
    {
        return _t("ProductGroup.PRICELISTPAGES", "Price List Pages");
    }

    /**
     * standard SS variable
     * @static Array | String
     *
     */
    private static $icon = "ecommerce_complex_pricing/images/treeicons/PriceListPage";

    /**
     * standard SS variable
     * @static Array
     *
     */
    private static $db = array(
        "NumberOfLevelsToHide" => "Int"
    );

    /**
     * standard SS variable
     * @static Array
     *
     */
    private static $defaults = array(
        "LevelOfProductsToShow" => -1,
        "NumberOfProductsPerPage" => 100,
        "NumberOfLevelsToHide" => 1
    );


    /**
     * standard SS variable
     * @static Array
     *
     */
    private static $allowed_children = "none";


    /**
     * standard SS Method
     * return FieldSet
     *
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab('Root.ProductDisplay', new NumericField("NumberOfLevelsToHide", _t("PriceListPage.NUMBEROFLEVELSTOHIDE", "Number of levels to hide from the top down (e.g. set to one to hide main (top) product group holder). To hide all the parent product groups you can set this variable to something like 999.")));
        return $fields;
    }


    /**
     * Retrieve a set of products, based on the given parameters.
     * Add Parent Group Pages to diplay within list.
     *
     * Note that you can hide the "top level"
     * @return DataObjectSet | Null
     */
    public function currentFinalProducts($alternativeSort = null)
    {
        $products = parent::currentFinalProducts($alternativeSort);
        if ($products) {
            foreach ($products as $product) {
                $product->ParentSegments = null;
                if ($this->NumberOfLevelsToHide < 20) {
                    $segmentArray = array();
                    $item = $product;
                    while ($item && $item->ParentID) {
                        $item = ProductGroup::get()->byID($item->ParentID);
                        if ($item) {
                            $segmentArray[] = array(
                                "URLSegment" => $item->URLSegment,
                                "ID" => $item->ID,
                                "ClassName" => $item->ClassName,
                                "Title" => $item->Title,
                                "Link" => $item->Link()
                            );
                        }
                    }
                    if (count($segmentArray)) {
                        $product->ParentSegments = new ArrayList();
                        $segmentArray = array_reverse($segmentArray);
                        foreach ($segmentArray as $key => $segment) {
                            if ($key > $this->NumberOfLevelsToHide) {
                                $product->ParentSegments->push(new ArrayData($segment));
                            }
                        }
                    }
                }
            }
        }
        return $products;
    }
}

class PriceListPage_Controller extends ProductGroup_Controller
{
    public function init()
    {
        parent::init();
        Requirements::themedCSS("PriceListPage", "ecommerce_complex_pricing");
    }
}
