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
class Ooworx_ScopesExplorer_Block_System_Config_Form extends Mage_Adminhtml_Block_System_Config_Form
{
    /**
     * Retrieve label for scope
     *
     * @param Mage_Core_Model_Config_Element $element
     * @return string
     */
    public function getScopeLabel($element)
    {
        // Dirty hack to fetch parents params (section, group, fieldPrefix)
        $parent_args = debug_backtrace()[1]["args"];
        // Avoid to override initFields and break compatibility

        /**
         * Look for custom defined field path
         */
        $path = (string)$element->config_path;
        if (empty($path)) {
            if (isset($parent_args[3])) {
                $path = $parent_args[2]->getName() . '/' . $parent_args[1]->getName() . '/' . $parent_args[3] . $element->getName();
            } else {
                $path = $parent_args[2]->getName() . '/' . $parent_args[1]->getName() . '/' . $element->getName();
            }
        }

        // Original code
        $html = '';
        if ($element->show_in_store == 1) {
            $html .= $this->_scopeLabels[self::SCOPE_STORES];
        } elseif ($element->show_in_website == 1) {
            $html .= $this->_scopeLabels[self::SCOPE_WEBSITES];
        } else {
            $html .= $this->_scopeLabels[self::SCOPE_DEFAULT];
        }
        $html .= Mage::helper("scopesexplorer")->getConfigScopesHtml($path);
        return $html;
    }

}
