<?php



//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START ecommerce_group_pricing MODULE ----------------===================
//MUST SET - WHERE APPLICABLE
//DataObject::add_extension("Product", "ComplexPriceBuyableDecorator");
//DataObject::add_extension("ProductVariation", "ComplexPriceBuyableDecorator");
//DataObject::add_extension("ComplexPriceObject", "ComplexPriceBuyableDecorator_ComplexPriceObject");
//MAY SET
/**
 * ADD TO ECOMMERCE.YAML:
ProductsAndGroupsModelAdmin:
	managed_modules: [
		...
		ComplexPriceObject
	]
*/

//===================---------------- END ecommerce_group_pricing MODULE ----------------===================
