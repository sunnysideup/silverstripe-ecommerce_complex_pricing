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

	public static $icon = "ecommerce_complex_pricing/images/treeicons/PriceListPage";

	public static $db = array(
		"ShowParentProductGroupPages" => "Int"
	);

	protected $toHideArray = array(
		"LevelOfProductsToShow"
	);


	public static $allowed_children = "none";

	function getCMSFields() {
		$fields = parent::getCMSFields();
		foreach($this->toHideArray as $name) {
			$fields->removeByName($name);
		}
		$fields->addFieldToTab('Root.Content.ProductDisplay',new CheckboxField("ShowParentProductGroupPages", _t("PriceListPage.NUMBEROFPARENTPAGESTOSHOW", "Show parent group pages with product")));
		return $fields;
	}


	/**
	 * Retrieve a set of products, based on the given parameters.
	 * Add Parent Group Pages
	 * @return DataObjectSet | Null
	 */
	protected function currentFinalProducts($buyables){
		$products = parent::currentFinalProducts($buyables);
		if($this->ShowParentProductGroupPages) {
			$dos = null;
			if($products) {
				foreach($products as $product) {
					$dos[$product->ID] = new DataObjectSet();
					$segmentArray = array();
					$item = $product;
					while($item && $item->ParentID) {
						$item = DataObject::get_by_id("ProductGroup", $item->ParentID);
						if($item) {
							$segmentArray[] = array("URLSegment" => $item->URLSegment, "ID" => $item->ID, "ClassName" => $item->ClassName, "Title" => $item->Title, "Link" => $item->Link());
						}
					}
					$segmentArray = array_reverse($segmentArray);
					foreach($segmentArray as $segment) {
						$dos[$product->ID]->push(new ArrayData($segment));
					}
					$product->ParentSegments = $dos[$product->ID] ;
					$dos = null;
				}
			}
		}
		return $products;
	}
}

class PriceListPage_Controller extends ProductGroup_Controller {


}




