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

class PriceListPage extends ProductGroup {

	/**
	 * Standard SS variable.
	 */
	public static $singular_name = "Price List Page";
		function i18n_singular_name() { return _t("ProductGroup.PRICELISTPAGE", "Price List Page");}

	/**
	 * Standard SS variable.
	 */
	public static $plural_name = "Price List Pages";
		function i18n_plural_name() { return _t("ProductGroup.PRICELISTPAGES", "Price List Pages");}

	/**
	 * standard SS variable
	 * @static Array | String
	 *
	 */
	public static $icon = "ecommerce_complex_pricing/images/treeicons/PriceListPage";

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	public static $db = array(
		"NumberOfLevelsToHide" => "Int"
	);

	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	public static $defaults = array(
		"LevelOfProductsToShow" => -1,
		"NumberOfProductsPerPage" => 100,
		"NumberOfLevelsToHide" => 1
	);


	/**
	 * standard SS variable
	 * @static Array
	 *
	 */
	public static $allowed_children = "none";


	/**
	 * standard SS Method
	 * return FieldSet
	 *
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab('Root.Content.ProductDisplay',new NumericField("NumberOfLevelsToHide", _t("PriceListPage.NUMBEROFLEVELSTOHIDE", "Nummber of levels to hide from the top down (e.g. set to one to hide main (top) product group holder). To hide all the parent product groups you can set this variable to something like 999.")));
		return $fields;
	}


	/**
	 * Retrieve a set of products, based on the given parameters.
	 * Add Parent Group Pages to diplay within list.
	 *
	 * Note that you can hide the "top level"
	 * @return DataObjectSet | Null
	 */
	protected function currentFinalProducts($buyables){
		$products = parent::currentFinalProducts($buyables);
		if($products) {
			foreach($products as $product) {
				$product->ParentSegments = null;
				if($this->NumberOfLevelsToHide < 20) {
					$segmentArray = array();
					$item = $product;
					while($item && $item->ParentID) {
						$item = DataObject::get_by_id("ProductGroup", $item->ParentID);
						if($item) {
							$segmentArray[] = array(
								"URLSegment" => $item->URLSegment,
								"ID" => $item->ID,
								"ClassName" => $item->ClassName,
								"Title" => $item->Title,
								"Link" => $item->Link()
							);
						}
					}
					if(count($segmentArray)) {
						$product->ParentSegments = new DataObjectSet();
						$segmentArray = array_reverse($segmentArray);
						foreach($segmentArray as $key => $segment) {
							if($key > $this->NumberOfLevelsToHide) {
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

class PriceListPage_Controller extends ProductGroup_Controller {


	function init(){
		parent::init();
		Requirements::themedCSS("PriceListPage");
	}

}




