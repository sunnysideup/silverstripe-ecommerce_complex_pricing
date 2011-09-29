<?php


class ComplexPriceObject extends DataObject {

	public static $db = array(
		'Price' => 'Currency',
		'From' => 'SS_Datetime',
		'Until' => 'SS_Datetime',
		'NoLongerValid' => 'Boolean'
	);

	public static $many_many = array(
		'Groups' => 'Group',
		'EcommerceCountries' => 'EcommerceCountry'
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
		'Price' => 'Price',
		'From' => 'Valid From',
		'Until' => 'Valid Until',
		'NoLongerValidNice' => 'Valid'
	);

	public static $casting = array(
		'NoLongerValidNice' => 'Varchar',
		'Buyable' => 'DataOject',
		'Name' => 'Varchar'
	);

	public static $singular_name = "Price";

	public static $plural_name = "Prices";

	//defaults
	public static $default_sort = "\"NoLongerValid\" ASC, \"LastEdited\" DESC ";

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("From", new TextField("From"));
		$fields->replaceField("Until", new TextField("Until"));
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

	function NoLongerValidNice() {return $this->getNoLongerValidNice();}
	function getNoLongerValidNice() {
		$nowTS = strtotime("now");
		$untilTS = strtotime($this->Until);
		if($this->NoLongerValid ||  $untilTS < $nowTS) {
			if($untilTS < $nowTS) {
				$this->NoLongerValid;
				$this->write();
				return "expired $nowTS, $untilTS";
			}
			return "no longer valid";
		}
		else {
			return "current";
		}
	}

	function Name() {return $this->getName();}
	function getName() {
		if($buyable = $this->getBuyable()) {
			return $buyable->getTitle();
		}
		return "no name";
	}

	static function work_out_price($buyable, $member = null, $dateTimeStamp = null ) {
		if(!$member) {
			$member = Member::currentMember();
		}
		if(!$dateTimeStamp) {
			$dateTimeStamp = Date();
		}
		DataObject::get("ComplexPriceObject", "\"BuyableID\" = ".$buyable->ID." AND \"BuyableClassName\" = '".$buyable->ClassName."'");
	}


}
