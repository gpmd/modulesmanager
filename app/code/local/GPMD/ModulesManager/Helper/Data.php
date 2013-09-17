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
 * @subpackage Helper
 * @author     Carlo Tasca <charles.pocket@gmail.com>
 */
class GPMD_ModulesManager_Helper_Data extends Mage_Core_Helper_Data
{
    /**
     * Takes a IO directory, a filename and some file content and writes it to the file.
     *
     * If path does not exists, allows to create folders.
     *
     * @param $ioContext
     * @param $content
     * @return bool
     */
    public function writeFile($ioContext, $content)
    {
        $filename = array_pop(explode('/', $ioContext));
        $dir = str_replace(DS . $filename, '' ,$ioContext);
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $dir));
        $io->streamOpen($ioContext, 'w');
        $io->streamLock(true);
        $result = $io->streamWrite($content);
        $io->streamUnlock();
        $io->streamClose();
        return $result;
    }
}
