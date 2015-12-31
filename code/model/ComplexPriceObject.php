<?php
/**
 * This object is attached to any Buyable
 * using the ComplexPriceBuyableDecorator
 *
 *
 */

class ComplexPriceObject extends DataObject
{

    private static $db = array(
        'NewPrice' => 'Currency',
        'Percentage' => 'Double',
        'Reduction' => 'Currency',
        'From' => 'SS_Datetime',
        'Until' => 'SS_Datetime',
        'NoLongerValid' => 'Boolean'
    );

    private static $many_many = array(
        'Groups' => 'Group',
        'EcommerceCountries' => 'EcommerceCountry'
    );

    private static $searchable_fields = array(
        "NoLongerValid" => true,
        "From" => true,
        "Until" => true
    );

    private static $field_labels = array(
        'From' => 'Valid From',
        'Until' => 'Valid Until',
        'NoLongerValidNice' => 'Valid?'
    );

    private static $summary_fields = array(
        'From' => 'Valid From',
        'Until' => 'Valid Until',
        'AppliesTo' => 'Applies To',
        'CalculatedPrice' => 'New Price',
        'NoLongerValidNice' => 'Valid'
    );

    private static $casting = array(
        'NoLongerValidNice' => 'Varchar',
        'Buyable' => 'DataOject',
        'Name' => 'Varchar',
        'CalculatedPrice' => 'Currency',
        'AppliesTo' => 'Text'
    );

    private static $singular_name = "Price";

    private static $plural_name = "Prices";

    //defaults
    private static $default_sort = "\"Until\" DESC";

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->replaceField("From", $from = new DateField("From", "Valid From - add any date and time"));
        $fields->replaceField("Until", $until = new DateField("Until", "Valid Until - add any date and time"));
        $fields->replaceField("NewPrice", new CurrencyField("NewPrice", "PRICE (OPTION 1 / 3) - only enter if there is a set new price independent of the 'standard' price."));
        $fields->replaceField("Percentage", new NumericField("Percentage", "PERCENTAGE (OPTIONAL 2/ 3) discount from 0 (0% discount) to 100 (100% discount)."));
        $fields->replaceField("Reduction", new CurrencyField("Reduction", "REDUCTION (OPTION 3 /3 ) - e.g. if you enter 2.00 then the new price will be the standard product price minus 2."));
        if (!$this->ID) {
            $fields->addFieldToTab("Root.Main", new LiteralField("SaveFirst", "<p>Please save first - and then select security groups / countries</p>"));
            $fields->removeByName("NoLongerValid");
        }
        if ($groups = Group::get()->count()) {
            $groups = Group::get();
            $fields->replaceField("Groups", new CheckboxSetField("Groups", "Who", $groups->map()->toArray()));
        } else {
            $fields->removeByName("Groups");
        }
        if ($ecommerceCountries = EcommerceCountry::get()) {
            $fields->replaceField("EcommerceCountries", new CheckboxSetField("EcommerceCountries", "Where", $ecommerceCountries->map()->toArray()));
        } else {
            $fields->removeByName("EcommerceCountries");
        }
        if (DiscountCouponOption::get()->count()) {
            $fields->replaceField("DiscountCouponOptions", new CheckboxSetField("DiscountCouponOptions", "Discount Coupons", DiscountCouponOption::get()->map()->toArray()));
        } else {
            $fields->removeByName("DiscountCouponOptions");
        }

        $from->setConfig('showcalendar', true);
        $until->setConfig('showcalendar', true);
        return $fields;
    }

    public function Buyable()
    {
        return $this->getBuyable();
    }
    public function getBuyable()
    {
        $array = $this->stat("has_one");
        if ($array && is_array($array) && count($array)) {
            foreach ($array as $className) {
                $fieldName = $className."ID";
                if (isset($this->$fieldName) && $this->$fieldName > 0) {
                    return $className::get()->byID($this->$fieldName);
                }
            }
        }
    }

    /**
     *
     * works out any price reductions
     */
    public function CalculatedPrice()
    {
        return $this->getCalculatedPrice();
    }
    public function getCalculatedPrice()
    {
        $buyable = $this->getBuyable();
        if ($this->NewPrice && $this->NewPrice > 0) {
            $newPrice = $this->NewPrice;
        } else {
            $newPrice = $buyable->Price;
            if ($this->Percentage) {
                $newPrice  = $newPrice - ($newPrice * ($this->Percentage / 100));
            }
            if ($this->Reduction) {
                $newPrice = $newPrice - $this->Reduction;
            }
            return $newPrice;
        }
        if ($newPrice < 0) {
            $newPrice = 0;
        }
        return $newPrice;
    }

    public function AppliesTo()
    {
        return $this->getAppliesTo();
    }
    public function getAppliesTo()
    {
        $appliesTo = array(); //;
        if ($this->Groups()) {
            foreach ($this->Groups() as $group) {
                $appliesTo[] = $group->getTitle();
            }
        }
        if ($this->EcommerceCountries()) {
            foreach ($this->EcommerceCountries() as $ecommerceCountries) {
                $appliesTo[] = $ecommerceCountries->getTitle();
            }
        }
        if (!count($appliesTo)) {
            $appliesTo[] = "Everyone";
        }
        return implode(", ", $appliesTo) . ".";
    }

    public function NoLongerValidNice()
    {
        return $this->getNoLongerValidNice();
    }
    public function getNoLongerValidNice()
    {
        $nowTS = strtotime("now");
        $untilTS = strtotime($this->Until);
        if ($this->NoLongerValid ||  $untilTS < $nowTS) {
            if ($untilTS < $nowTS) {
                $this->NoLongerValid;
                $this->write();
                return "expired";
            }
            return "no longer valid";
        } else {
            return "current";
        }
    }

    public function validate()
    {
        $errors = array();
        if (strtotime($this->From) < strtotime("1 jan 2000")) {
            $errors[] = "The FROM field needs to be after 1 Jan 2000";
        }
        if (strtotime($this->Until) < strtotime("1 jan 2000")) {
            $errors[] = "The UNTIL field needs to be after 1 Jan 2000";
        }
        if (strtotime($this->Until) < strtotime($this->From)) {
            $errors[] = "The UNTIL field needs to be after the FROM field";
        }
        if ($this->Percentage < 0 || $this->Percentage > 100) {
            $errors[] = "The PERCENTAGE field needs to be between 0 and 100";
        }
        if (count($errors)== 0) {
            return new ValidationResult();
        } else {
            return new ValidationResult(false, "Please check: ".implode("; ", $errors).".");
        }
    }

    public function Name()
    {
        return $this->getName();
    }
    public function getName()
    {
        if ($buyable = $this->getBuyable()) {
            return $buyable->getTitle();
        }
        return "no name";
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
    }
}
