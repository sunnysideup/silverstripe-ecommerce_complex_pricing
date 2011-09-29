<?php


class ComplexPriceBuyableDecorator extends DataObjectDecorator {

	public function extraStatics() {
		return array (
			'has_many' => array(
				'ComplexPriceObjects' => 'ComplexPriceObject'
			)
		);
	}

	function updateCMSFields(&$fields) {
		$fields->addFieldToTab(
			"Root.Content.Prices",
			$this->complexPricesHasManyTable()
		);
	}

	protected function complexPricesHasManyTable(){
		$field = new HasManyComplexTableField(
			$controller = $this->owner,
			$name = "ComplexPriceObjects",
			$sourceClass = "ComplexPriceObject"
		);
		$field->setRelationAutoSetting(true);
		return $field;
	}

	function HasDiscount() {
		if($this->owner->Price > 0) {
			if($this->owner->Price > $this->owner->getCalculatedPrice()) {
				return true;
			}
		}
		return false;
	}

	function updateCalculatedPrice(&$startingPrice) {
		$fieldName = $this->owner->ClassName."ID";
		$singleton = DataObject::get_one("ComplexPriceObject");
		if($singleton) {
			if(!$singleton->hasField($fieldName)) {
				$ancestorArray = ClassInfo::ancestry($this->owner, true );
				foreach($ancestorArray as $ancestor) {
					$fieldName = $ancestor."ID";
					if($singleton->hasField($fieldName)) {
						break;
					}
				}
			}
			$prices = DataObject::get("ComplexPriceObject", "\"$fieldName\" = '".$this->owner->ID."' AND \"NoLongerValid\" = 0", "\"Price\" DESC");
			$memberGroupsArray = array();
			$newPrice = null;
			if($prices) {
				if($member = Member::currentMember()) {
					if($memberGroupComponents = $member->getManyManyComponents('Groups')) {
						if($memberGroupComponents && $memberGroupComponents->count()) {
							$memberGroupsArray = $memberGroupComponents->column("ID");
							if(!is_array($memberGroupsArray)) {
								$memberGroupsArray = array();
							}
						}
					}
				}
				$countryID = EcommerceCountry::get_country_id();
				foreach($prices as $price) {
					$priceCanBeUsed = true;
					if($priceGroupComponents = $price->getManyManyComponents('Groups')) {
						$priceCanBeUsed = false;
						if($priceGroupComponents && $priceGroupComponents->count()) {
							//print_r($price->Groups());
							$priceGroupArray = $priceGroupComponents->column("ID");
							if(!is_array($priceGroupArray)) {$priceGroupArray = array();}
							$interSectionArray = array_intersect($priceGroupArray, $memberGroupsArray);
							if(is_array($interSectionArray) && count($interSectionArray)) {
								$priceCanBeUsed = true;
							}
						}
					}
					if($priceCanBeUsed) {
						if($priceCountryComponents = $price->getManyManyComponents('EcommerceCountries')) {
							if($priceCountryComponents && $priceCountryComponents->count()) {
								$priceCanBeUsed = false;
								$priceCountryArray = $priceCountryComponents->column("ID");
								if(!is_array($priceCountryArray)) {$priceCountryArray = array();}
								if($countryID && in_array($countryID, $priceCountryArray)) {
									$priceCanBeUsed = true;
								}
							}
						}
					}
					if($priceCanBeUsed) {
						$nowTS = strtotime("now");
						if($price->From) {
							$priceCanBeUsed = false;
							$fromTS = strtotime($price->From);
							if($fromTS && $fromTS < $nowTS) {
								$priceCanBeUsed = true;
							}
						}
					}
					if($priceCanBeUsed) {
						if($price->Until) {
							$priceCanBeUsed = false;
							$untilTS = strtotime($price->Until);
							if($untilTS && $untilTS > $nowTS) {
								$priceCanBeUsed = true;
							}
						}
					}
					if($priceCanBeUsed) {
						$newPrice = $price->Price;
					}
				}
			}
		}
		if(!$newPrice) {
			return null;
		}
		else {
			$startingPrice = $newPrice;
			return $startingPrice;
		}
	}

}


class ComplexPriceBuyableDecorator_ComplexPriceObject extends DataObjectDecorator {

	public function extraStatics() {
		$array = Buyable::get_array_of_buyables();
		$hasOneArray = array();
		if($array && is_array($array) && count($array)) {
			foreach($array as $item) {
				$hasOneArray[$item] = $item;
			}
			return array (
				'has_one' => $hasOneArray
			);
		}
		return array();
	}

	function updateCMSFields(&$fields) {
		$this->owner->getBuyable();
		$array = Buyable::get_array_of_buyables();
		if($array && is_array($array) && count($array)) {
			foreach($array as $item) {
				$fields->replaceField($item."ID", new HiddenField($item."ID"));
			}
		}
		$fields->replaceField("From", new TextField("From"));
		return $fields;
	}


}