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
    protected $_depends;
    protected $_request;

    /**
     * Returns request object
     *
     * @return mixed
     */
    protected function _getRequest()
    {
        return $this->_request;
    }

    /**
     * Refreshes all cache types
     *
     * @throws Exception
     */
    protected function _refreshCache()
    {
        try {
            $allTypes = Mage::app()->useCache();
            foreach ($allTypes as $type => $blah) {
                Mage::app()->getCacheInstance()->cleanType($type);
            }
        } catch (Exception $e) {
            // do something
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @return bool|string
     */
    protected function _getModuleDependencies()
    {
        if (!array_key_exists($this->_moduleName, $this->_depends)) {
            return false;
        }
        return $this->_depends[$this->_moduleName]['depends'];
    }

    /**
     * @param $module
     * @return bool|string
     */
    protected function _isModuleActive($module)
    {
        return (string) $this->_depends[$module]['status'];
    }

    /**
     * Searches each module dependencies and status and returns false
     * when dependency module is active.
     *
     * @return bool
     */
    protected function _canModuleBeDisabled()
    {
        foreach ($this->_depends as $module => $depend) {
            $dependencies = explode(',', preg_replace('~\s~', '', $depend['depends']));
            foreach ($dependencies as $dependency) {
                if ($this->_moduleName == $dependency && $this->_isModuleActive($module) === 'true') {
                    return false;
                }
            }

        }
        return true;
    }

    /**
     * Returns true if module can be enabled.
     * A module cannot be enabled when it depends on other modules that are currently disabled.
     *
     * Mage_ dependencies are not checked. Only community and local modules.
     *
     * @return bool
     */
    protected function _canModuleBeEnabled()
    {
        foreach ($this->_depends as $module => $depend) {
            if ($this->_moduleName == $module) {
                $dependencies = explode(',', preg_replace('~\s~', '', $depend['depends']));
                foreach ($dependencies as $dependency) {
                    if (preg_match('~Mage_~', $dependency)) {
                        continue;
                    }
                    if ($this->_isModuleActive($dependency) === 'false') {
                        return false;
                    }
                }
            }

        }
        return true;
    }

    /**
     *
     * Changes module's enable/disable status and returns true on success, or false on failure.
     *
     * @return bool
     */
    protected function _doChangeStatus()
    {
        $session = Mage::getSingleton('adminhtml/session');
        $request = $this->_getRequest();
        $this->_moduleName = $request['module'];
        $this->_xmlFilename = $request['xmlfilename'];
        $this->_depends = unserialize($request['srlzdepends']);
        if (!$this->_canModuleBeDisabled()) {
            $session->addError("There is one or more modules depending on {$this->_moduleName} being active. Module could not be disabled.");
            return false;
        }
        if (!$this->_canModuleBeEnabled()) {
            $session->addError("{$this->_moduleName} depends on other modules that are currently disabled. You should enable dependencies modules first.");
            return false;
        }
        $this->_moduleXmlPath = Mage::getBaseDir() . DS . 'app/etc/modules/' . $this->_xmlFilename . '.xml';

        if (!is_writable($this->_moduleXmlPath)) {
            $session->addError("{$this->_moduleXmlPath} is not writable. Check 'modules' folder permissions.");
            return false;
        }

        if (isset($request['button-type-disable'])) {
            $active = (string) 'false';
            $currentStatus = (string) 'true';
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

        $moduleNodes = $this->_getModuleNodes($xml);

        $moduleXml = str_replace($currentStatus, $active, $moduleNodes);
        $newXml = str_replace($moduleNodes, $moduleXml, $xml);
        $isSet = Mage::helper('modulesmanager')->writeFile($this->_moduleXmlPath, $newXml);
        $status = $active == 'true' ? 'enabled' : 'disabled';
        if ($isSet) {
            $session->addSuccess("{$this->_moduleName} successully {$status}{$cacheRefreshNotice}.");
            return true;
        } else {
            $session->addError("{$this->_moduleName} could not be {$status}.");
            return false;
        }
    }

    protected function _getModuleNodes ($xml)
    {
        $nodes = '';
        $xmlAsArray = explode(PHP_EOL, $xml);
        $append = false;
        foreach ($xmlAsArray as $i => $line) {
            if (preg_match("~</\s{0,}{$this->_moduleName}\s{0,}>~", $line)) {
                $nodes .= $line . PHP_EOL;
                $append = false;
            }
            if ($append) {
                $nodes .= $line . PHP_EOL;
            }
            if (preg_match("~<\s{0,}{$this->_moduleName}\s{0,}>~", $line)) {
                $nodes .= $line . PHP_EOL;
                $append = true;
            }


        }
        return $nodes;
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
    public function manageSetStatusRequest($request)
    {
        $this->_request = $request;
        $this->_doChangeStatus();
        return array('string' => 'ok');
    }
}
