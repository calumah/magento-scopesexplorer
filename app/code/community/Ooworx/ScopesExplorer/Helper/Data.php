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
class Ooworx_ScopesExplorer_Helper_Data extends Mage_Core_Helper_Abstract
{
    /*
     * Return additionnal html for config scopes
     *
     */
    public function getConfigScopesHtml($path) {
        // Init html
        $html = "";

        // Check empty path
        if (empty($path)) {
            return $html;
        }

        // Fetch config_data informations for stores
        $config_data_stores = Mage::getModel('core/config_data')
                            ->getCollection()
                            ->addFieldToFilter("path", $path)
                            ->addFieldToFilter("scope", "stores");

        // Fetch config data informations for website
        $config_data_websites = Mage::getModel('core/config_data')
                              ->getCollection()
                              ->addFieldToFilter("path", $path)
                              ->addFieldToFilter("scope", "websites");

        // Count rows availables
        $stores_count = $config_data_stores->count();
        $websites_count = $config_data_websites->count();

        // Generate html
        if ($stores_count > 0 || $websites_count > 0) {
            // Generate header
            $html .= "<span> (<b>Overridden scopes : ";
            // Websites
            if ($websites_count > 0) {
                $desc = 'Overridden in ' . $websites_count . ' websites : \n';
                foreach ($config_data_websites as $config) {
                    $website = Mage::getModel("core/website")->load($config->getScopeId());
                    $desc .= '- [' . $website->getName() . ']\n';
                }
                $html .= '<a href="#" onclick="alert(\'' . $desc . '\');return false;">Websites: ' . $websites_count . '</a>';
            }
            // Stores
            if ($stores_count > 0) {
                if ($websites_count > 0) {
                    $html .= " / ";
                }
                $desc = 'Overridden in ' . $stores_count . ' stores : \n';
                foreach ($config_data_stores as $config) {
                    $store = Mage::getModel("core/store")->load($config->getScopeId());
                    $desc .= '- [' . $store->getName() . ']\n';
                }
                $html .= '<a href="#" onclick="alert(\'' . $desc . '\');return false;">Stores: ' . $stores_count . '</a>';
            }
            // End
            $html .= "</b>)</span>";
        }
        return $html;
    }

    public function getProductCategoryScopesHtml($element, $data_object) {
        $html = "";
        // Generate html for category and product form
        if (is_null($data_object) || $data_object->getId() == null
            || is_null($element) || $element->getId() == null) {
            return $html;
        }
        // Read product attribute code selected
        $attribute_code = $element->getEntityAttribute()->getData('attribute_code');
        // Get current entity_id from object
        $entity_id = $data_object->getData('entity_id');

        // Load query for category or product
        $query = $this->getQueryByDataObject($data_object, $attribute_code, $entity_id);

        // Init raw database
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');

        // Run query
        $attributes_list = $readConnection->fetchAll($query);

        // Stores
        $stores_count = count($attributes_list);
        if ($stores_count > 0) {
            // Generate header
            $html .= '<span> (<b>Overridden scopes : ';
            $desc = 'Overridden in ' . $stores_count . ' stores:\n';
            foreach ($attributes_list as $attribute) {
                $store = Mage::getModel("core/store")->load($attribute["store_id"]);
                $desc .= '- [' . $store->getName() . ']\n';
            }
            $html .= '<a href="#" onclick="alert(\'' . $desc . '\');return false;">Stores: ' . $stores_count . '</a>';
            // End
            $html .= "</b>)</span>";
        }
        return $html;
    }

    // Thanks https://gist.github.com/ticean/735798
    public function getQueryByDataObject($data_object, $attribute_code, $entity_id) {
        if ($data_object instanceof Mage_Catalog_Model_Product) {
            // PRODUCTS
            return "SELECT ea.attribute_code, eav.value AS 'value', eav.store_id AS 'store_id',  'varchar' AS 'type'
FROM catalog_product_entity e
JOIN catalog_product_entity_varchar eav
  ON e.entity_id = eav.entity_id
JOIN eav_attribute ea
  ON eav.attribute_id = ea.attribute_id
WHERE e.entity_id = " . $entity_id . " AND ea.attribute_code = '" . $attribute_code . "' AND store_id != 0

UNION

SELECT ea.attribute_code, eav.value AS 'value', eav.store_id AS 'store_id', 'int' AS 'type'
FROM catalog_product_entity e
JOIN catalog_product_entity_int eav
  ON e.entity_id = eav.entity_id
JOIN eav_attribute ea
  ON eav.attribute_id = ea.attribute_id
WHERE e.entity_id = " . $entity_id . " AND ea.attribute_code = '" . $attribute_code . "' AND store_id != 0

UNION

SELECT ea.attribute_code, eav.value AS 'value', eav.store_id AS 'store_id', 'decimal' AS 'type'
FROM catalog_product_entity e
JOIN catalog_product_entity_decimal eav
  ON e.entity_id = eav.entity_id
JOIN eav_attribute ea
  ON eav.attribute_id = ea.attribute_id
WHERE e.entity_id = " . $entity_id . " AND ea.attribute_code = '" . $attribute_code . "' AND store_id != 0

UNION

SELECT ea.attribute_code, eav.value AS 'value', eav.store_id AS 'store_id', 'datetime' AS 'type'
FROM catalog_product_entity e
JOIN catalog_product_entity_datetime eav
  ON e.entity_id = eav.entity_id
JOIN eav_attribute ea
  ON eav.attribute_id = ea.attribute_id
WHERE e.entity_id = " . $entity_id . " AND ea.attribute_code = '" . $attribute_code . "' AND store_id != 0

UNION

SELECT ea.attribute_code, eav.value AS 'value', eav.store_id AS 'store_id', 'text' AS 'type'
FROM catalog_product_entity e
JOIN catalog_product_entity_text eav
  ON e.entity_id = eav.entity_id
JOIN eav_attribute ea
  ON eav.attribute_id = ea.attribute_id
WHERE e.entity_id = " . $entity_id . " AND ea.attribute_code = '" . $attribute_code . "' AND store_id != 0";

        } else {
            // CATEGORY
            return "SELECT ea.attribute_id, ea.attribute_code, eav.value AS 'value', eav.store_id AS 'store_id', 'varchar' AS 'type'
FROM catalog_category_entity e
JOIN catalog_category_entity_varchar eav
  ON e.entity_id = eav.entity_id
JOIN eav_attribute ea
  ON eav.attribute_id = ea.attribute_id
WHERE e.entity_id = " . $entity_id . " AND ea.attribute_code = '" . $attribute_code . "' AND store_id != 0

UNION

SELECT ea.attribute_id, ea.attribute_code, eav.value AS 'value', eav.store_id AS 'store_id', 'int' AS 'type'
FROM catalog_category_entity e
JOIN catalog_category_entity_int eav
  ON e.entity_id = eav.entity_id
JOIN eav_attribute ea
  ON eav.attribute_id = ea.attribute_id
WHERE e.entity_id = " . $entity_id . " AND ea.attribute_code = '" . $attribute_code . "' AND store_id != 0

UNION

SELECT ea.attribute_id, ea.attribute_code, eav.value AS 'value', eav.store_id AS 'store_id', 'decimal' AS 'type'
FROM catalog_category_entity e
JOIN catalog_category_entity_decimal eav
  ON e.entity_id = eav.entity_id
JOIN eav_attribute ea
  ON eav.attribute_id = ea.attribute_id
WHERE e.entity_id = " . $entity_id . " AND ea.attribute_code = '" . $attribute_code . "' AND store_id != 0

UNION

SELECT ea.attribute_id, ea.attribute_code, eav.value AS 'value', eav.store_id AS 'store_id', 'datetime' AS 'type'
FROM catalog_category_entity e
JOIN catalog_category_entity_datetime eav
  ON e.entity_id = eav.entity_id
JOIN eav_attribute ea
  ON eav.attribute_id = ea.attribute_id
WHERE e.entity_id = " . $entity_id . " AND ea.attribute_code = '" . $attribute_code . "' AND store_id != 0

UNION

SELECT ea.attribute_id, ea.attribute_code, eav.value AS 'value', eav.store_id AS 'store_id', 'text' AS 'type'
FROM catalog_category_entity e
JOIN catalog_category_entity_text eav
  ON e.entity_id = eav.entity_id
JOIN eav_attribute ea
  ON eav.attribute_id = ea.attribute_id
WHERE e.entity_id = " . $entity_id . " AND ea.attribute_code = '" . $attribute_code . "' AND store_id != 0";
        }
    }
}
