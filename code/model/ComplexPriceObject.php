<?php


class ComplexPriceObject extends DataObject {

	public static $db = array(
		'NewPrice' => 'Currency',
		'Percentage' => 'Double',
		'Reduction' => 'Currency',
		'From' => 'SS_Datetime',
		'Until' => 'SS_Datetime',
		'NoLongerValid' => 'Boolean'
	);

	public static $many_many = array(
		'Groups' => 'Group',
		'EcommerceCountries' => 'EcommerceCountry',
		'DiscountCouponOptions' => 'DiscountCouponOption'
	);

	public static $searchable_fields = array(
		"NoLongerValid" => true,
		"From" => true,
		"Until" => true
	);

	public static $field_labels = array(
		'From' => 'Valid From',
		'Until' => 'Valid Until',
		'NoLongerValidNice' => 'Valid?'
	);

	public static $summary_fields = array(
		'From' => 'Valid From',
		'Until' => 'Valid Until',
		'AppliesTo' => 'Applies To',
		'CalculatedPrice' => 'New Price',
		'NoLongerValidNice' => 'Valid'
	);

	public static $casting = array(
		'NoLongerValidNice' => 'Varchar',
		'Buyable' => 'DataOject',
		'Name' => 'Varchar',
		'CalculatedPrice' => 'Currency',
		'AppliesTo' => 'Text'
	);

	public static $singular_name = "Price";

	public static $plural_name = "Prices";

	//defaults
	public static $default_sort = "\"NoLongerValid\" ASC, \"Until\" DESC, \"From\" DESC ";

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("From", new TextField("From", "Valid From - add any date and time"));
		$fields->replaceField("Until", new TextField("Until", "Valid Until - add any date and time"));
		$fields->replaceField("NewPrice", new CurrencyField("NewPrice", "PRICE (OPTION 1 / 3) - only enter if there is a set new price independent of the 'standard' price."));
		$fields->replaceField("Percentage", new NumericField("Percentage", "PERCENTAGE (OPTIONAL 2/ 3) discount from 0 (0% discount) to 100 (100% discount)."));
		$fields->replaceField("Reduction", new CurrencyField("Reduction", "REDUCTION (OPTION 3 /3 ) - e.g. if you enter 2.00 then the new price will be the standard product price minus 2."));
		if(!$this->ID) {
			$fields->addFieldToTab("Root.Main", new LiteralField("SaveFirst", "<p>Please save first - and then select security groups / countries</p>"));
			$fields->removeByName("NoLongerValid");
		}
		if($groups = DataObject::get("Group")) {
			$fields->replaceField("Groups", new CheckboxSetField("Groups", "Who", $groups->toDropdownMap()));
		}
		else {
			$fields->removeByName("Groups");
		}
		if($ecommerceCountries = DataObject::get("EcommerceCountry")) {
			$fields->replaceField("EcommerceCountries", new CheckboxSetField("EcommerceCountries", "Where", $ecommerceCountries->toDropdownMap()));
		}
		else {
			$fields->removeByName("EcommerceCountries");
		}
		if($discountCouponOptions = DataObject::get("DiscountCouponOption")) {
			$fields->replaceField("DiscountCouponOptions", new CheckboxSetField("DiscountCouponOptions", "Discount Coupons", $discountCouponOptions));
		}
		else {
			$fields->removeByName("EcommerceCountries");
		}

		return $fields;
	}

	function Buyable() {return $this->getBuyable();}
	function getBuyable() {
		$array = $this->stat("has_one");
		if($array && is_array($array) && count($array)) {
			foreach($array as $className) {
				$fieldName = $className."ID";
				if(isset($this->$fieldName) && $this->$fieldName > 0) {
					return DataObject::get_by_id($className, $this->$fieldName);
				}
			}
		}
	}

	function CalculatedPrice() {return $this->getCalculatedPrice();}
	function getCalculatedPrice() {
		$buyable = $this->getBuyable();
		if($this->NewPrice && $this->NewPrice > 0) {
			$newPrice = $this->NewPrice;
		}
		else {
			$newPrice = $buyable->Price;
			if($this->Percentage) {
				$newPrice  = $newPrice - ($newPrice * ($this->Percentage / 100));
			}
			if($this->Reduction) {
				$newPrice = $newPrice - $this->Reduction;
			}
			return $newPrice;
		}
		if($newPrice < 0) {
			$newPrice = 0;
		}
		return $newPrice;
	}

	function AppliesTo() {return $this->getAppliesTo();}
	function getAppliesTo() {
		$appliesTo = array(); //;
		if($this->Groups()) {
			foreach($this->Groups() as $group) {
				$appliesTo[] = $group->getTitle();
			}
		}
		if($this->EcommerceCountries()) {
			foreach($this->EcommerceCountries() as $ecommerceCountries) {
				$appliesTo[] = $ecommerceCountries->getTitle();
			}
		}
		if(!count($appliesTo)) {
			$appliesTo[] = "Everyone";
		}
		return implode(", ", $appliesTo) . ".";
	}

	function NoLongerValidNice() {return $this->getNoLongerValidNice();}
	function getNoLongerValidNice() {
		$nowTS = strtotime("now");
		$untilTS = strtotime($this->Until);
		if($this->NoLongerValid ||  $untilTS < $nowTS) {
			if($untilTS < $nowTS) {
				$this->NoLongerValid;
				$this->write();
				return "expired";
			}
			return "no longer valid";
		}
		else {
			return "current";
		}
	}

	public function validate() {
		$errors = array();
		if(strtotime($this->From) < strtotime("1 jan 2000")) {
			$errors[] = "The FROM field needs to be after 1 Jan 2000";
		}
		if(strtotime($this->Until) < strtotime("1 jan 2000")) {
			$errors[] = "The UNTIL field needs to be after 1 Jan 2000";
		}
		if(strtotime($this->Until) < strtotime($this->From)) {
			$errors[] = "The UNTIL field needs to be after the UNTIL field";
		}
		if($this->Percentage < 0 || $this->Percentage > 100) {
			$errors[] = "The PERCENTAGE field needs to be between 0 and 100";
		}
		if(count($errors)== 0) {
			return new ValidationResult();
		}
		else {
			return new ValidationResult(false, "Please check: ".implode("; ", $errors).".");
		}
	}

	function Name() {return $this->getName();}
	function getName() {
		if($buyable = $this->getBuyable()) {
			return $buyable->getTitle();
		}
		return "no name";
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
	}

}
