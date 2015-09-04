<?php
/*
 Copyright (C) 2015  Ooworx

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class Ooworx_ScopesExplorer_Block_Catalog_Form_Renderer_Fieldset_Element extends Mage_Adminhtml_Block_Catalog_Form_Renderer_Fieldset_Element
{
    /**
     * Retrieve label of attribute scope
     *
     * GLOBAL | WEBSITE | STORE
     *
     * @return string
     */
    public function getScopeLabel()
    {
        $html = '';
        $attribute = $this->getElement()->getEntityAttribute();
        if (!$attribute || Mage::app()->isSingleStoreMode() || $attribute->getFrontendInput()=='gallery') {
            return $html;
        }

        /*
         * Check if the current attribute is a 'price' attribute. If yes, check
         * the config setting 'Catalog Price Scope' and modify the scope label.
         */
        $isGlobalPriceScope = false;
        if ($attribute->getFrontendInput() == 'price') {
            $priceScope = Mage::getStoreConfig('catalog/price/scope');
            if ($priceScope == 0) {
                $isGlobalPriceScope = true;
            }
        }

        if ($attribute->isScopeGlobal() || $isGlobalPriceScope) {
            $html .= Mage::helper('adminhtml')->__('[GLOBAL]');
        } elseif ($attribute->isScopeWebsite()) {
            $html .= Mage::helper('adminhtml')->__('[WEBSITE]');
        } elseif ($attribute->isScopeStore()) {
            $html .= Mage::helper('adminhtml')->__('[STORE VIEW]');
        }

        // Rewrite scopehelper
        $html .= Mage::helper("scopesexplorer")->getProductCategoryScopesHtml($this->getElement(), $this->getDataObject());

        return $html;
    }
}
