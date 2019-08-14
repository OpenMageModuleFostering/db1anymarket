<?php
/**
 * DB1_AnyMarket extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category       DB1
 * @package        DB1_AnyMarket
 * @copyright      Copyright (c) 2015
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Anymarket Products resource model
 *
 * @category    DB1
 * @package     DB1_AnyMarket

 */
class DB1_AnyMarket_Model_Resource_Anymarketproducts extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * constructor
     *
     * @access public
     * 
     */
    public function _construct()
    {
        $this->_init('db1_anymarket/anymarketproducts', 'entity_id');
    }

    /**
     * Get store ids to which specified item is assigned
     *
     * @access public
     * @param int $anymarketproductsId
     * @return array
     * 
     */
    public function lookupStoreIds($anymarketproductsId)
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getTable('db1_anymarket/anymarketproducts_store'), 'store_id')
            ->where('anymarketproducts_id = ?', (int)$anymarketproductsId);
        return $adapter->fetchCol($select);
    }

    /**
     * Perform operations after object load
     *
     * @access public
     * @param Mage_Core_Model_Abstract $object
     * @return DB1_AnyMarket_Model_Resource_Anymarketproducts
     * 
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if ($object->getId()) {
            $stores = $this->lookupStoreIds($object->getId());
            $object->setData('store_id', $stores);
        }
        return parent::_afterLoad($object);
    }

    /**
     * Retrieve select object for load object data
     *
     * @param string $field
     * @param mixed $value
     * @param DB1_AnyMarket_Model_Anymarketproducts $object
     * @return Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);
        if ($object->getStoreId()) {
            $storeIds = array(Mage_Core_Model_App::ADMIN_STORE_ID, (int)$object->getStoreId());
            $select->join(
                array('anymarket_anymarketproducts_store' => $this->getTable('db1_anymarket/anymarketproducts_store')),
                $this->getMainTable() . '.entity_id = anymarket_anymarketproducts_store.anymarketproducts_id',
                array()
            )
            ->where('anymarket_anymarketproducts_store.store_id IN (?)', $storeIds)
            ->order('anymarket_anymarketproducts_store.store_id DESC')
            ->limit(1);
        }
        return $select;
    }

    /**
     * Assign anymarket products to store views
     *
     * @access protected
     * @param Mage_Core_Model_Abstract $object
     * @return DB1_AnyMarket_Model_Resource_Anymarketproducts
     * 
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $oldStores = $this->lookupStoreIds($object->getId());
        $newStores = (array)$object->getStores();
        if (empty($newStores)) {
            $newStores = (array)$object->getStoreId();
        }
        $table  = $this->getTable('db1_anymarket/anymarketproducts_store');
        $insert = array_diff($newStores, $oldStores);
        $delete = array_diff($oldStores, $newStores);
        if ($delete) {
            $where = array(
                'anymarketproducts_id = ?' => (int) $object->getId(),
                'store_id IN (?)' => $delete
            );
            $this->_getWriteAdapter()->delete($table, $where);
        }
        if ($insert) {
            $data = array();
            foreach ($insert as $storeId) {
                $data[] = array(
                    'anymarketproducts_id'  => (int) $object->getId(),
                    'store_id' => (int) $storeId
                );
            }
            $this->_getWriteAdapter()->insertMultiple($table, $data);
        }
        return parent::_afterSave($object);
    }}
