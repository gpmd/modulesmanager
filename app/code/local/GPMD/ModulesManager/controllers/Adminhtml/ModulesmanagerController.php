<?php
class GPMD_ModulesManager_Adminhtml_ModulesmanagerController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction ()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function setModuleStatusAction ()
    {
        $request = $this->getRequest()->getPost();
        $manager = Mage::getModel('modulesmanager/modules_manager');
        $result = $manager->manageSetStatusRequest($request);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}