<?php


class ComplexPriceBuyableDecorator extends DataExtension
{
    private static $has_many = array(
        'ComplexPriceObjects' => 'ComplexPriceObject'
    );

    public function updateCMSFields(FieldList $fields)
    {
        $tabName = 'Root.Pricing';

        if (class_exists("DataObjectOneFieldUpdateController")) {
            $link = DataObjectOneFieldUpdateController::popup_link(
                $this->owner->ClassName,
                "Price",
                $where = "",
                $sort = "Price ASC ",
                $linkText = "Check all prices..."
            );
            $fields->AddFieldToTab($tabName, new HeaderField("MetaTitleFixesHeader", "Quick review", 3));
            $fields->AddFieldToTab($tabName, new LiteralField("MetaTitleFixes", $link.".<br /><br /><br />"));
        }

        // move price field under new 'Pricing' tab
//		$priceField = $fields->fieldByName('Root.Details.Price');
//		$fields->remove($priceField);

        $fields->addFieldsToTab(
            $tabName,
            array(
//				$priceField,
                new HeaderField("ComplexPricesHeader", "Alternative Pricing", 3),
                new LiteralField("ComplexPricesExplanation", "<p>Please enter <i>alternative</i> pricing below. You can enter a price per <a href=\"admin/security/\">security group</a> and/or per country.</p>"),
                $this->complexPricesHasManyTable()
            )
        );
    }

    protected function complexPricesHasManyTable()
    {
        $gridCfg = new GridFieldConfig_RelationEditor();
        $grid = new GridField('ComplexPriceObjects', '', $this->owner->ComplexPriceObjects(), $gridCfg);
        return $grid;
    }

    public function HasDiscount()
    {
        if ($this->owner->Price > 0) {
            if ($this->owner->Price > $this->owner->getCalculatedPrice()) {
                return true;
            }
        }
        return false;
    }

    public function updateCalculatedPrice(&$startingPrice)
    {
        $newPrice = -1;
        $fieldName = $this->owner->ClassName."ID";
        $singleton = ComplexPriceObject::get()->first();
        if ($singleton) {
            // Check that ComplexPriceObject can be joined to this type of object
            if (!$singleton->hasField($fieldName)) {
                $ancestorArray = ClassInfo::ancestry($this->owner, true);
                foreach ($ancestorArray as $ancestor) {
                    $fieldName = $ancestor."ID";
                    if ($singleton->hasField($fieldName)) {
                        break;
                    }
                }
            }

            // Load up the alternate prices for this product
            $prices = ComplexPriceObject::get()
                ->filter(array($fieldName => $this->owner->ID, "NoLongerValid" => 0))
                ->sort("NewPrice", "DESC");
            ;
            $memberGroupsArray = array();
            if ($prices->count()) {
                // Load up the groups for the current memeber, if any
                if ($member = Member::currentUser()) {
                    if ($memberGroupComponents = $member->getManyManyComponents('Groups')) {
                        if ($memberGroupComponents && $memberGroupComponents->count()) {
                            $memberGroupsArray = $memberGroupComponents->column("ID");
                            if (!is_array($memberGroupsArray)) {
                                $memberGroupsArray = array();
                            }
                        }
                    }
                }

                $countryID = EcommerceCountry::get_country_id();

                // Look at each price and see if it can be used
                foreach ($prices as $price) {
                    $priceCanBeUsed = true;

                    // Does it pass the group filter?
                    if ($priceGroupComponents = $price->getManyManyComponents('Groups')) {
                        if ($priceGroupComponents && $priceGroupComponents->count()) {
                            $priceCanBeUsed = false;
                            $priceGroupArray = $priceGroupComponents->column("ID");
                            if (!is_array($priceGroupArray)) {
                                $priceGroupArray = array();
                            }
                            $interSectionArray = array_intersect($priceGroupArray, $memberGroupsArray);
                            if (is_array($interSectionArray) && count($interSectionArray)) {
                                $priceCanBeUsed = true;
                            }
                        }
                    }

                    // Does it pass the country filter?
                    if ($priceCanBeUsed) {
                        if ($priceCountryComponents = $price->getManyManyComponents('EcommerceCountries')) {
                            if ($priceCountryComponents && $priceCountryComponents->count()) {
                                $priceCanBeUsed = false;
                                $priceCountryArray = $priceCountryComponents->column("ID");
                                if (!is_array($priceCountryArray)) {
                                    $priceCountryArray = array();
                                }
                                if ($countryID && in_array($countryID, $priceCountryArray)) {
                                    $priceCanBeUsed = true;
                                }
                            }
                        }
                    }

                    // Does it pass the date filter?
                    if ($priceCanBeUsed) {
                        $nowTS = strtotime("now");
                        if ($price->From) {
                            $priceCanBeUsed = false;
                            $fromTS = strtotime($price->From);
                            if ($fromTS && $fromTS < $nowTS) {
                                $priceCanBeUsed = true;
                            }
                        }
                    }

                    if ($priceCanBeUsed) {
                        if ($price->Until) {
                            $priceCanBeUsed = false;
                            $untilTS = strtotime($price->Until);
                            if ($untilTS && $untilTS > $nowTS) {
                                $priceCanBeUsed = true;
                            }
                        }
                    }

                    // If so, apply the price
                    if ($priceCanBeUsed) {
                        $newPrice = $price->getCalculatedPrice();
                    }
                }
            }
        }

        if ($newPrice > -1) {
            $startingPrice = $newPrice;
        }

        return $startingPrice;
    }
}


class ComplexPriceBuyableDecorator_ComplexPriceObject extends DataExtension
{
    public static function get_extra_config($class, $extension, $args)
    {
        $buyables = EcommerceConfig::get("EcommerceDBConfig", "array_of_buyables");
        $hasOneArray = array();
        if ($buyables && is_array($buyables) && count($buyables)) {
            foreach ($buyables as $item) {
                $hasOneArray[$item] = $item;
            }
            return array(
                'has_one' => $hasOneArray
            );
        }
        return array();
    }

    public function updateCMSFields(FieldList $fields)
    {
        $this->owner->getBuyable();
        $buyables = EcommerceConfig::get("EcommerceDBConfig", "array_of_buyables");
        if ($buyables && is_array($buyables) && count($buyables)) {
            foreach ($buyables as $item) {
                $fields->replaceField($item."ID", new HiddenField($item."ID"));
            }
        }
        $fields->replaceField("From", new TextField("From"));
        return $fields;
    }
}
