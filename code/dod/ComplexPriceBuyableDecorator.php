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
		if($this->owner instanceOf SiteTree) {
			$tabName = "Root.Content.Pricing";
		}
		else {
			$tabName = "Root.Pricing";
		}
		if(class_exists("DataObjectOneFieldUpdateController")) {
			$link = DataObjectOneFieldUpdateController::popup_link(
				$this->owner->ClassName,
				"Price",
				$where = "",
				$sort = "Price ASC ",
				$linkText = "Check all prices..."
			);
			$fields->AddFieldToTab($tabName, new HeaderField("metatitleFixesHeader", "Quick review", 3));
			$fields->AddFieldToTab($tabName, new LiteralField("metatitleFixes", $link.".<br /><br /><br />"));
		}
		$fields->addFieldsToTab(
			$tabName,
			array(
				new HeaderField("ComplexPricesHeader", "Alternative Pricing", 3),
				new LiteralField("ComplexPricesExplanation", "<p>Please enter <i>alternative</i> pricing below. You can enter a price per <a href=\"admin/security/\">security group</a> and/or per country.</p>"),
				$this->complexPricesHasManyTable()
			)
		);
	}

	protected function complexPricesHasManyTable(){
		$complexTableField = new HasManyComplexTableField(
			$controller = $this->owner,
			$name = "ComplexPriceObjects",
			$sourceClass = "ComplexPriceObject"
		);
		$complexTableField->setRelationAutoSetting(true);
		return $complexTableField;
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
		$newPrice = 0;
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
			$prices = DataObject::get("ComplexPriceObject", "\"$fieldName\" = '".$this->owner->ID."' AND \"NoLongerValid\" = 0", "\"NewPrice\" DESC");
			$memberGroupsArray = array();
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
						$newPrice = $price->getCalculatedPrice();
					}
				}
			}
		}
		if($newPrice > 0) {
			$startingPrice = $newPrice;
		}
		return $startingPrice;
	}

}


class ComplexPriceBuyableDecorator_ComplexPriceObject extends DataObjectDecorator {

	public function extraStatics() {
		$buyables = EcommerceConfig::get("EcommerceDBConfig", "array_of_buyables");
		$hasOneArray = array();
		if($buyables && is_array($buyables) && count($buyables)) {
			foreach($buyables as $item) {
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
		$buyables = EcommerceConfig::get("EcommerceDBConfig", "array_of_buyables");
		if($buyables && is_array($buyables) && count($buyables)) {
			foreach($buyables as $item) {
				$fields->replaceField($item."ID", new HiddenField($item."ID"));
			}
		}
		$fields->replaceField("From", new TextField("From"));
		return $fields;
	}


}
