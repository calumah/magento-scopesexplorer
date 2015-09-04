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
     * Init fieldset fields
     *
     * @param Varien_Data_Form_Element_Fieldset $fieldset
     * @param Varien_Simplexml_Element $group
     * @param Varien_Simplexml_Element $section
     * @param string $fieldPrefix
     * @param string $labelPrefix
     * @return Mage_Adminhtml_Block_System_Config_Form
     */
    public function initFields($fieldset, $group, $section, $fieldPrefix='', $labelPrefix='')
    {
        if (!$this->_configDataObject) {
            $this->_initObjects();
        }

        // Extends for config data
        $configDataAdditionalGroups = array();

        foreach ($group->fields as $elements) {
            $elements = (array)$elements;
            // sort either by sort_order or by child node values bypassing the sort_order
            if ($group->sort_fields && $group->sort_fields->by) {
                $fieldset->setSortElementsByAttribute(
                    (string)$group->sort_fields->by,
                    $group->sort_fields->direction_desc ? SORT_DESC : SORT_ASC
                );
            } else {
                usort($elements, array($this, '_sortForm'));
            }

            foreach ($elements as $element) {
                if (!$this->_canShowField($element)) {
                    continue;
                }

                if ((string)$element->getAttribute('type') == 'group') {
                    $this->_initGroup($fieldset->getForm(), $element, $section, $fieldset);
                    continue;
                }

                /**
                 * Look for custom defined field path
                 */
                $path = (string)$element->config_path;
                if (empty($path)) {
                    $path = $section->getName() . '/' . $group->getName() . '/' . $fieldPrefix . $element->getName();
                } elseif (strrpos($path, '/') > 0) {
                    // Extend config data with new section group
                    $groupPath = substr($path, 0, strrpos($path, '/'));
                    if (!isset($configDataAdditionalGroups[$groupPath])) {
                        $this->_configData = $this->_configDataObject->extendConfig(
                            $groupPath,
                            false,
                            $this->_configData
                        );
                        $configDataAdditionalGroups[$groupPath] = true;
                    }
                }

                $data = $this->_configDataObject->getConfigDataValue($path, $inherit, $this->_configData);
                if ($element->frontend_model) {
                    $fieldRenderer = Mage::getBlockSingleton((string)$element->frontend_model);
                } else {
                    $fieldRenderer = $this->_defaultFieldRenderer;
                }

                $fieldRenderer->setForm($this);
                $fieldRenderer->setConfigData($this->_configData);

                $helperName = $this->_configFields->getAttributeModule($section, $group, $element);
                $fieldType  = (string)$element->frontend_type ? (string)$element->frontend_type : 'text';
                $name  = 'groups[' . $group->getName() . '][fields][' . $fieldPrefix.$element->getName() . '][value]';
                $label =  Mage::helper($helperName)->__($labelPrefix) . ' '
                    . Mage::helper($helperName)->__((string)$element->label);
                $hint  = (string)$element->hint ? Mage::helper($helperName)->__((string)$element->hint) : '';

                if ($element->backend_model) {
                    $model = Mage::getModel((string)$element->backend_model);
                    if (!$model instanceof Mage_Core_Model_Config_Data) {
                        Mage::throwException('Invalid config field backend model: '.(string)$element->backend_model);
                    }
                    $model->setPath($path)
                        ->setValue($data)
                        ->setWebsite($this->getWebsiteCode())
                        ->setStore($this->getStoreCode())
                        ->afterLoad();
                    $data = $model->getValue();
                }

                $comment = $this->_prepareFieldComment($element, $helperName, $data);
                $tooltip = $this->_prepareFieldTooltip($element, $helperName);
                $id = $section->getName() . '_' . $group->getName() . '_' . $fieldPrefix . $element->getName();

                if ($element->depends) {
                    foreach ($element->depends->children() as $dependent) {
                        /* @var $dependent Mage_Core_Model_Config_Element */

                        if (isset($dependent->fieldset)) {
                            $dependentFieldGroupName = (string)$dependent->fieldset;
                            if (!isset($this->_fieldsets[$dependentFieldGroupName])) {
                                $dependentFieldGroupName = $group->getName();
                            }
                        } else {
                            $dependentFieldGroupName = $group->getName();
                        }

                        $dependentFieldNameValue = $dependent->getName();
                        $dependentFieldGroup = $dependentFieldGroupName == $group->getName()
                            ? $group
                            : $this->_fieldsets[$dependentFieldGroupName]->getGroup();

                        $dependentId = $section->getName()
                            . '_' . $dependentFieldGroupName
                            . '_' . $fieldPrefix
                            . $dependentFieldNameValue;
                        $shouldBeAddedDependence = true;
                        $dependentValue = (string)(isset($dependent->value) ? $dependent->value : $dependent);
                        if (isset($dependent['separator'])) {
                            $dependentValue = explode((string)$dependent['separator'], $dependentValue);
                        }
                        $dependentFieldName = $fieldPrefix . $dependent->getName();
                        $dependentField     = $dependentFieldGroup->fields->$dependentFieldName;
                        /*
                         * If dependent field can't be shown in current scope and real dependent config value
                         * is not equal to preferred one, then hide dependence fields by adding dependence
                         * based on not shown field (not rendered field)
                         */
                        if (!$this->_canShowField($dependentField)) {
                            $dependentFullPath = $section->getName()
                                . '/' . $dependentFieldGroupName
                                . '/' . $fieldPrefix
                                . $dependent->getName();
                            $dependentValueInStore = Mage::getStoreConfig($dependentFullPath, $this->getStoreCode());
                            if (is_array($dependentValue)) {
                                $shouldBeAddedDependence = !in_array($dependentValueInStore, $dependentValue);
                            } else {
                                $shouldBeAddedDependence = $dependentValue != $dependentValueInStore;
                            }
                        }
                        if ($shouldBeAddedDependence) {
                            $this->_getDependence()
                                ->addFieldMap($id, $id)
                                ->addFieldMap($dependentId, $dependentId)
                                ->addFieldDependence($id, $dependentId, $dependentValue);
                        }
                    }
                }
                $sharedClass = '';
                if ($element->shared && $element->config_path) {
                    $sharedClass = ' shared shared-' . str_replace('/', '-', $element->config_path);
                }

                $requiresClass = '';
                if ($element->requires) {
                    $requiresClass = ' requires';
                    foreach (explode(',', $element->requires) as $groupName) {
                        $requiresClass .= ' requires-' . $section->getName() . '_' . $groupName;
                    }
                }

                $field = $fieldset->addField($id, $fieldType, array(
                    'name'                  => $name,
                    'label'                 => $label,
                    'comment'               => $comment,
                    'tooltip'               => $tooltip,
                    'hint'                  => $hint,
                    'value'                 => $data,
                    'inherit'               => $inherit,
                    'class'                 => $element->frontend_class . $sharedClass . $requiresClass,
                    'field_config'          => $element,
                    'scope'                 => $this->getScope(),
                    'scope_id'              => $this->getScopeId(),
                    /*
                     * THE ONLY ONE LINE EDITED IN THAT REWRITE......
                     */
                    'scope_label'           => $this->getScopeLabel($element, $path),
                    /*
                     * ............
                     */
                    'can_use_default_value' => $this->canUseDefaultValue((int)$element->show_in_default),
                    'can_use_website_value' => $this->canUseWebsiteValue((int)$element->show_in_website),
                ));
                $this->_prepareFieldOriginalData($field, $element);

                if (isset($element->validate)) {
                    $field->addClass($element->validate);
                }

                if (isset($element->frontend_type)
                    && 'multiselect' === (string)$element->frontend_type
                    && isset($element->can_be_empty)
                ) {
                    $field->setCanBeEmpty(true);
                }

                $field->setRenderer($fieldRenderer);

                if ($element->source_model) {
                    // determine callback for the source model
                    $factoryName = (string)$element->source_model;
                    $method = false;
                    if (preg_match('/^([^:]+?)::([^:]+?)$/', $factoryName, $matches)) {
                        array_shift($matches);
                        list($factoryName, $method) = array_values($matches);
                    }

                    $sourceModel = Mage::getSingleton($factoryName);
                    if ($sourceModel instanceof Varien_Object) {
                        $sourceModel->setPath($path);
                    }
                    if ($method) {
                        if ($fieldType == 'multiselect') {
                            $optionArray = $sourceModel->$method();
                        } else {
                            $optionArray = array();
                            foreach ($sourceModel->$method() as $value => $label) {
                                $optionArray[] = array('label' => $label, 'value' => $value);
                            }
                        }
                    } else {
                        $optionArray = $sourceModel->toOptionArray($fieldType == 'multiselect');
                    }
                    $field->setValues($optionArray);
                }
            }
        }
        return $this;
    }

    
    /**
     * Retrieve label for scope
     *
     * @param Mage_Core_Model_Config_Element $element
     * @return string
     */
    public function getScopeLabel($element, $path)
    {
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
