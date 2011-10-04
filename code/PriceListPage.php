<?php

/**
 *@author nicolaas[at]
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 **/

class PriceListPage extends ProductGroup {

	public static $icon = "ecommerce_complex_pricing/images/treeicons/PriceListPage";

	protected $toHideArray = array(
		"LevelOfProductsToShow",
		"ProductsAlsoInOthersGroups"
	);


	public static $allowed_children = "none";

	function getCMSFields() {
		$fields = parent::getCMSFields();
		foreach($this->toHideArray as $name) {
			$fields->removeByName($name);
		}
		return $fields;
	}


	/**
	 * Retrieve a set of products, based on the given parameters. Checks get query for sorting and pagination.
	 *
	 * @param string $extraFilter Additional SQL filters to apply to the Product retrieval
	 * @param boolean $recursive
	 * @return DataObjectSet | Null
	 */
	function ProductsShowable($extraFilter = '', $recursive = true){

		// WHERE
		$filter = $this->getStandardFilter(); //
		if($extraFilter) {
			$filter.= " AND $extraFilter";
		}
		$where = "\"Price\" > 0 $filter";

		//SORT BY
		if(!isset($_GET['sortby'])) {
			$sortKey = $this->MyDefaultSortOrder();
		}
		else {
			$sortKey = Convert::raw2sqL($_GET['sortby']);
		}
		$sort = $this->getSortOptionSQL($sortKey);

		//JOIN
		$join = "";

		//LIMIT
		$limit = (isset($_GET['start']) && (int)$_GET['start'] > 0) ? (int)$_GET['start'] : "0";
		$limit .= ", ".$this->MyNumberOfProductsPerPage();

		//ACTION
		$products = DataObject::get('Product',$where,$sort, null,$limit);
		$products->TotalCount = $products->count();
		return $products;
	}
}

class PriceListPage_Controller extends ProductGroup_Controller {


}




