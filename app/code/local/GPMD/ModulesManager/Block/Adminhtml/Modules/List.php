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
 * @subpackage Block
 * @author     Carlo Tasca <charles.pocket@gmail.com>
 */
class GPMD_ModulesManager_Block_Adminhtml_Modules_List extends Mage_Adminhtml_Block_Template
{
    const MAGE_MATCH = "#^Mage_#";
    const ENTERPRISE_MATCH = "#^Enterprise_#";
    const GPMD_MATCH = "#GPMD_ModulesManager#";

    protected $_xml;
    protected $_moduleXmlPath;
    protected $_moduleXmlName;
    protected $_modules = array();
    protected $_errors = array();

    /**
     * Returns Magento modules directory path
     *
     * @return string
     */
    protected function _getModulesDir()
    {
        return Mage::getBaseDir() . "/app/etc/modules";
    }

    /**
     * Returns current module name by turning its path into an array,
     * getting array last element and remove '.xml'
     *
     * @return mixed
     */
    protected function _getCurrentModuleName()
    {
        $parts = explode('/', $this->_moduleXmlPath);
        return str_replace('.xml', '', array_pop($parts));
    }

    /**
     * Returns 'active' node as a string.
     *
     * If a node is passed as argument, then instance's module xml name is not used.
     *
     * @param bool|string $node
     * @return string
     */
    protected function _getActiveNode($node = false)
    {
        if ($node) {
            (string) $this->getXml()->get('modules/' . $node . '/active');
        }
        return (string) $this->getXml()->get('modules/' . $this->_moduleXmlName . '/active');
    }

    /**
     * Returns 'codePool' node as a string
     *
     * If a node is passed as argument, then instance's module xml name is not used.
     *
     * @param bool|string $node
     * @return string
     */
    protected function _getModuleCodepool($node = false)
    {
        if ($node) {
            return (string) $this->getXml()->get('modules/' . $node . '/codePool');
        }
        return (string) $this->getXml()->get('modules/' . $this->_moduleXmlName . '/codePool');
    }

    /**
     * Returns 'depends' node object
     *
     * If a node is passed as argument, then instance's module xml name is not used.
     *
     * @param bool|string $node
     * @return object
     */
    protected function _getModuleDepends($node = false)
    {
        if ($node) {
            return $this->getXml()->getXml()->modules->{$node}->depends;
        }
        return $this->getXml()->getXml()->modules->{$this->_moduleXmlName}->depends;
    }

    /**
     * Takes a filename as argument and verifies it is a xml file
     *
     * @param $filename
     * @return bool
     */
    protected function _isXmlFile($filename)
    {
        $parts = explode('.', $filename);
        $ext = array_pop($parts);
        return $ext == "xml";
    }

    /**
     * Returns 'modules' children nodes or null if node has no children
     *
     * @return mixed
     */
    protected function _getModulesNodeChildren()
    {
        return $this->getXml()->getXml()->modules->children();
    }

    /**
     * Sets instance member _modules.
     *
     * Loops the modules directory, iterates over each xml file and gets 'active', 'depends' and 'codePool' nodes
     *
     * @return void
     */
    protected function _toModulesArray()
    {
        $this->_modules = array();
        $modulesDir = $this->_getModulesDir();
        $iterator = new DirectoryIterator($modulesDir);

        foreach ($iterator as $filename) {
            if ($filename->isDot() || preg_match(self::MAGE_MATCH, $filename->getFilename()) || preg_match(self::ENTERPRISE_MATCH, $filename->getFilename()) || preg_match(self::GPMD_MATCH, $filename->getFilename()) || !$this->_isXmlFile($filename->getFilename())) {
                continue;
            }

            $this->_moduleXmlPath = $modulesDir . DS . $filename->getFilename();
            $this->_moduleXmlName = $this->_getCurrentModuleName();

            $children = $this->_getModulesNodeChildren();

            if (count($children) > 0) {
                $module = array();
                foreach ($children as $key => $child) {
                    count($children) == 1 ? $this->_checkForNameConflict($key) : '';
                    if (count($children) > 1) {
                        $isBundle = true;
                    }

                    $status = (string) $this->getXml()->get('modules/' . $key . '/active');
                    $depends = $this->_getModuleDepends($key)->children();
                    $pool = $this->_getModuleCodepool($key);
                    if (isset($isBundle) && $isBundle) {
                        $module[] = array('bundle_module' => $this->_moduleXmlName, 'module' => $key, 'active' => $status, 'pool' => $pool, 'depends' => $depends);
                    } else {
                        $module[] = array('module' => $key, 'active' => $status, 'pool' => $pool, 'depends' => $depends);
                    }

                }
                $this->_modules[$this->_moduleXmlName] = $module;
            }
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
     * Checks for conflicts between xml filename and module's name in 'modules' node
     *
     * @param $name
     */
    protected function _checkForNameConflict ($name)
    {
        if($this->_moduleXmlName != $name) {
            $this->_errors[$this->_moduleXmlName] = sprintf("<strong class=\"error\">Conflict detected: </strong> Filename '%s.xml' conflicts with modules node name '%s'", $this->_moduleXmlName, $name);
        }
    }

    /**
     * Returns Enable/Disable button with given id depeding on $active value
     *
     * $active can either be 'true' or 'false'
     *
     * @param $id
     * @param $active
     * @return string
     */
    protected function _getButtonHtml ($id, $active)
    {
        if ($active == 'true') {
           return <<<HTML
            <input type="hidden" name="button-type-disable" value="1" />
            <button id="id_{$id}" title="Disable" type="button" class="scalable" onclick="managerButton.save('{$id}-form')"  style="width:80px"><span><span><span>Disable</span></span></span></button>
HTML;

        }
        return <<<HTML
            <input type="hidden" name="button-type-enable" value="1" />
            <button id="id_{$id}" title="Enable" type="button" class="scalable" onclick="managerButton.save('{$id}-form')" style="width:80px"><span><span><span>Enable</span></span></span></button>
HTML;
    }

    /**
     * Gets row HTML
     *
     * @param       $evenClass
     * @param       $moduleName
     * @param       $xmlFilename
     * @param       $version
     * @param       $statusString
     * @param       $dependsAsString
     * @param       $error
     * @param       $pool
     * @param       $active
     * @param       $button
     * @param array $additional
     * @return string
     */
    protected function _getRowHtml ($evenClass, $moduleName, $xmlFilename, $version, $statusString, $dependsAsString, $error, $pool, $active, $button, $additional = array())
    {
        return <<<HTML
        <tr title="" style="height:60px" class="{$evenClass}">
            <td class=" ">
                <div style="margin-top:3px;" style="float:left" class="module-info">
                    <h4>{$moduleName} <span style="color:#fbcb09">{$version}</span></h4>
                    <div style="margin-top:3px;" class="status"><strong>Status: </strong>{$statusString}</div>
                    <div style="margin-top:3px;" class="depends"><strong>Depends: </strong>{$dependsAsString}</div>
                    <div style="margin-top:3px;" class=""><span class="notice">{$error}</span></div>
                </div>
            </td>
            <td class=" a-right ">{$pool}</td>
            <td class=" a-right ">{$active}</td>
            <td class=" a-center ">
                <div style="margin-top:3px" class="module-cell-actions">
                    <form id="{$moduleName}-form" name="{$moduleName}-form" method="post">
                        <input type="hidden" name="module" value="{$moduleName}" />
                        <input type="hidden" name="xmlfilename" value="{$xmlFilename}" />
                        <input type="checkbox" name="refresh-cache" /> Refresh Cache
                        <div class="form-buttons">
                            {$button}
                        </div>
                    </form>
                </div>
            </td>
        </tr>
HTML;
    }

    /**
     * Gets row HTML
     *
     * @param       $evenClass
     * @param       $moduleName
     * @param       $xmlFilename
     * @param       $version
     * @param       $statusString
     * @param       $dependsAsString
     * @param       $error
     * @param       $pool
     * @param       $active
     * @param       $button
     * @param array $additional
     * @return string
     */
    protected function _getBundleRowHtml ($evenClass, $moduleName, $xmlFilename, $version, $statusString, $dependsAsString, $error, $pool, $active, $button, $additional = array())
    {
        if (isset($additional['bundle_module'])) {
            $bundleModule = $additional['bundle_module'];
        }

        if (isset($additional['has_next']) && $additional['has_next']) {
            $removeBorder = ' style="border-bottom:0px;"';
        }

        if (isset($additional['is_first']) && $additional['is_first']) {
            $bundleHeading = "<h4>" . $additional['bundle_module'] . " (Bundle xml)</h4>";
        }

        return <<<HTML
        <tr title="" style="height:60px;"  class="{$evenClass}">
            <td {$removeBorder} class=" ">
                <div style="margin-top:3px;" style="float:left" class="module-info">
                    {$bundleHeading}
                    <div style="padding-left: 20px;"><strong>{$moduleName}<span style="color:#fbcb09"> {$version}</span></strong></div>
                    <div style="margin-top:3px;padding-left: 20px;" class="bundle"><strong>Bundle xml filename: </strong>{$bundleModule}.xml</div>
                    <div style="margin-top:3px;padding-left: 20px;" class="status"><strong>Status: </strong>{$statusString}</div>
                    <div style="margin-top:3px;padding-left: 20px;" class="depends"><strong>Depends: </strong>{$dependsAsString}</div>
                    <div style="margin-top:3px;padding-left: 20px;" class=""><span class="notice">{$error}</span></div>
                </div>
            </td>
            <td {$removeBorder} class=" a-right ">{$pool}</td>
            <td {$removeBorder} class=" a-right ">{$active}</td>
            <td {$removeBorder} class=" a-center ">
                <div style="margin-top:3px" class="module-cell-actions">
                    <form id="{$moduleName}-form" name="{$moduleName}-form" method="post">
                        <input type="hidden" name="module" value="{$moduleName}" />
                        <input type="hidden" name="xmlfilename" value="{$xmlFilename}" />
                        <input type="hidden" name="bundle-module" value="{$bundleModule}" />
                        <input type="checkbox" name="refresh-cache" /> Refresh Cache
                        <div class="form-buttons">
                            {$button}
                        </div>
                    </form>
                </div>
            </td>
        </tr>
HTML;
    }

    /**
     * Returns module dependecies as comma separated string
     *
     * @param $depends
     * @return string
     */
    protected function _dependsToString($depends)
    {
        $collectedDepends = array();
        $dependsAsString = 'This module has no dependencies.';
        if (count($depends) > 0) {
            foreach ($depends as $module => $val) {
                $collectedDepends[] = $module;
            }
            $dependsAsString = implode(', ', $collectedDepends);
        }
        return $dependsAsString;
    }

    /**
     * Returns table row HTML
     *
     * If even is true, CSS class 'even' is added to <tr> tag
     *
     * @param      $moduleName
     * @param      $data
     * @param bool $even
     * @return string
     */
    protected function _getTableRowHtml($moduleName, $data, $even = false)
    {
        if (count($data) == 1) {
            $versionNode = (string) Mage::getConfig()->getModuleConfig($data[0]['module'])->version;
            $version = $versionNode ? 'ver.' . $versionNode : '';
            $pool = $data[0]['pool'];
            $active = $data[0]['active'];
            $status = $data[0]['active'] == 'true' ? true : false ;
            $statusString = $status ? 'Enabled' : 'Disabled';
            $depends = $data[0]['depends'];
            $dependsAsString = $this->_dependsToString($depends);
            if (array_key_exists($moduleName, $this->_errors)) {
                $error = $this->_errors[$moduleName];
            }
            $evenClass = $even ? 'even ' : false;
            $button = $this->_getButtonHtml ($data[0]['module'], $active);
            return $this->_getRowHtml($evenClass, $data[0]['module'], $moduleName, $version, $statusString, $dependsAsString, $error, $pool, $active, $button);

        } else {
            $bundle = '';
            $count = 0;
            $iData = new CachingIterator(new ArrayIterator($data));
            foreach ($iData as $modulesData) {
                if (isset($modulesData['bundle_module'])) {
                    $version = Mage::getConfig()->getModuleConfig($modulesData['module'])->version ? 'ver.' .Mage::getConfig()->getModuleConfig($modulesData['module'])->version : '';
                    $pool = $modulesData['pool'];
                    $active = $modulesData['active'];
                    $status = $modulesData['active'] == 'true' ? true : false ;
                    $statusString = $status ? 'Enabled' : 'Disabled';
                    $depends = $modulesData['depends'];
                    $dependsAsString = $this->_dependsToString($depends);
                    if (array_key_exists($modulesData['module'], $this->_errors)) {
                        $error = $this->_errors[$modulesData['module']];
                    }
                    $evenClass = $even ? 'even ' : false;
                    $button = $this->_getButtonHtml ($modulesData['module'], $active);
                    $modulesData['has_next'] = $iData->hasNext();
                    $modulesData['is_first'] = $count == 0 ? true : false;
                    $bundle .= $this->_getBundleRowHtml($evenClass, $modulesData['module'], $moduleName, $version, $statusString, $dependsAsString, $error, $pool, $active, $button, $modulesData);
                    $count++;
                }
            }
            return $bundle;
        }



    }

    /**
     * Return HTML for table head
     *
     * @return string
     */
    public function getTableHeadHtml()
    {
        return <<<HTML
        <head>
            <tr class="headings">
                <th class=" no-link"><span class="nobr">Module</span></th>
                <th class=" no-link"><span class="nobr">Code Pool</span></th>
                <th class=" no-link"><span class="nobr">Active</span></th>
                <th class=" no-link"><span class="nobr">Actions</span></th>
            </tr>
        </head>
HTML;

    }

    /**
     * Returns table body HTML
     *
     * @return string
     */
    public function getTableBodyHtml()
    {
        $body = '<body>';
        $this->_toModulesArray();
        $count = 0;
        foreach ($this->_modules as $module => $data) {
            if ($count % 2 == 0) {
                $body .= $this->_getTableRowHtml($module, $data, true);
            } else {
                $body .= $this->_getTableRowHtml($module, $data);
            }

            $count++;
        }

        return $body . '</body>';
    }
}
