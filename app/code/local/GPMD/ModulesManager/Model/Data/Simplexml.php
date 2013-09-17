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
class GPMD_ModulesManager_Model_Data_Simplexml
{
    private $_path;
    /**
     * @var Varien_Io_File
     */
    private $_io;
    private $_xml;

    public function __construct ($filepath, Varien_Io_File $io)
    {
        $this->_path = $filepath;
        $this->_io = $io;
        $this->_xml = simplexml_load_file($this->_path);
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @return Varien_Io_File
     */
    public function getIo()
    {
        return $this->_io;
    }

    /**
     * Set config value
     *
     * If $value is an array, then $childArrayNode has to be set.
     *
     * $value array should be in the format
     *
     *   array (
     *      array('value' => 12345),
     *      array('value' => 123456)
     *   );
     *
     * @param string $path separated by slash (/)
     * @param string $value
     * @param bool $childArrayNode
     * @return bool|int
     */
    public function set($path, $value, $childArrayNode = false)
    {

        $segments = explode('/', $path);
        $node = $this->_xml;
        foreach($segments as $_key => $_segment)
        {
            if(!$node->$_segment->getName()) {
                $node->addChild($_segment);
            }

            if($_key == count($segments) - 1) {
                if ((string) trim($value)) {
                    $node->$_segment = (string) $value;
                }
            }
            $node = $node->$_segment;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                $child = $node->addChild($childArrayNode);
                foreach ($item as $key => $val) {
                    if (isset($val)) {
                        $child->addChild($key, $val);
                    } else {
                        $child->addChild($key);
                    }
                }
            }
        }
        return $this->getIo()->write($this->_path,  $this->_xml->asXML());
    }

    /**
     * Format xml with indents and line breaks
     *
     * @return string
     * @author Gary Malcolm
     */
    public function asPrettyXML()
    {
        $string = $this->_xml->asXML();

        // put each element on it's own line
        $string =preg_replace("/>\s*</",">\n<",$string);

        // each element to own array
        $xmlArray = explode("\n",$string);

        // holds indentation
        $currIndent = 0;

        $indent = "    ";
        // set xml element first by shifting of initial element
        $string = array_shift($xmlArray) . "\n";
        foreach($xmlArray as $element)
        {
            // find open only tags... add name to stack, and print to string
            // increment currIndent
            if (preg_match('/^<([\w])+[^>\/]*>$/U',$element))
            {
                $string .=  str_repeat($indent, $currIndent) . $element . "\n";
                $currIndent += 1;
            } // find standalone closures, decrement currindent, print to string
            elseif ( preg_match('/^<\/.+>$/',$element))
            {
                $currIndent -= 1;
                $string .=  str_repeat($indent, $currIndent) . $element . "\n";
            } // find open/closed tags on the same line print to string
            else
                $string .=  str_repeat($indent, $currIndent) . $element . "\n";
        }
        return $string;
    }

    public function getXml ()
    {
        return $this->_xml;
    }

    /**
     * Read the config value
     *
     * @param string $path
     * @return string
     */
    public function get($path)
    {
        $node = $this->_xml;
        foreach(explode('/', $path) as $_segment) {
            if($node->$_segment) {
                $node = $node->$_segment;
            }
        }
        return (string) trim($node);
    }
}
