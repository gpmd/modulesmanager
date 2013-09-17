<?php
/**
 * GPMD extension for Magento
 *
 * Long description of this file (if any...)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade
 * the GPMD ModulesManager module to newer versions in the future.
 * If you wish to customize the GPMD ModulesManager module for your needs
 * please refer to http://www.magentocommerce.com for more information.
 *
 * @category   GPMD
 * @package    GPMD_ModulesManager
 * @copyright  Copyright (C) 2013 
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Short description of the class
 *
 * Long description of the class (if any...)
 *
 * @category   GPMD
 * @package    GPMD_ModulesManager
 * @subpackage Model
 * @author     Carlo Tasca <charles.pocket@gmail.com>
 */
class GPMD_ModulesManager_Model_Modules_Manager
{
    protected $_xml;
    protected $_moduleName;
    protected $_moduleXmlPath;
    protected $_moduleXmlName;
    protected $_request;

    /**
     * Returns request object
     *
     * @return mixed
     */
    protected function _getRequest ()
    {
        return $this->_request;
    }

    /**
     * Refreshes all cache types
     *
     * @throws Exception
     */
    protected function _refreshCache() {
        try {
            $allTypes = Mage::app()->useCache();
            foreach($allTypes as $type => $blah) {
                Mage::app()->getCacheInstance()->cleanType($type);
            }
        } catch (Exception $e) {
            // do something
            throw new Exception($e->getMessage());
        }
    }

    /**
     *
     * Changes module's enable/disable status and returns true on success, or false on failure.
     *
     * @return bool
     */
    protected function _doChangeStatus ()
    {
        $session = Mage::getSingleton('adminhtml/session');
        $request = $this->_getRequest();
        $this->_moduleName = $request['module'];
        $this->_xmlFilename = $request['xmlfilename'];

        $this->_moduleXmlPath = Mage::getBaseDir() .DS . 'app/etc/modules/' . $this->_xmlFilename . '.xml';

        if (!is_writable($this->_moduleXmlPath)) {
            $session->addError("{$this->_moduleXmlPath} is not writable. Check 'modules' folder permissions." );
            return false;
        }


        if (isset($request['button-type-disable'])) {
            $active = (string) 'false';
            $currentStatus  = (string) 'true';
        }

        if (isset($request['button-type-enable'])) {
            $active = (string) 'true';
            $currentStatus = (string) 'false';
        }

        if (isset($request['refresh-cache'])) {
            $this->_refreshCache();
            $cacheRefreshNotice = ' and cache has been refreshed';
        }
        $xml = file_get_contents($this->_moduleXmlPath);
        $newXml = str_replace($currentStatus, $active, $xml);
        $isSet = Mage::helper('modulesmanager')->writeFile($this->_moduleXmlPath, $newXml);
        $status = $active == 'true' ? 'enabled' : 'disabled';
        if ($isSet) {
           $session->addSuccess("{$this->_moduleName} successully {$status}{$cacheRefreshNotice}." );
            return true;
        } else {
            $session->addError("{$this->_moduleName} could not be {$status}." );
            return false;
        }
    }

    /**
     * Gets Xml Data Model (simplexml_load_file wrapper)
     *
     * @return GPMD_ModulesManager_Model_Data_Simplexml
     */
    protected function getXml()
    {
        return new GPMD_ModulesManager_Model_Data_Simplexml($this->_moduleXmlPath, new Varien_Io_File());
    }


    /**
     * Deals with enable/disable module requests
     *
     * @param $request
     * @return array
     */
    public function manageSetStatusRequest ($request)
    {
        $this->_request = $request;
        $this->_doChangeStatus();
        return array('string' => 'ok');
    }
}
