<?php

class VKCONNECT_MCMP_Connect extends VKCONNECT_CMP_Connect
{
    public function __construct($type)
    {
        parent::__construct($type);
        
        $tpl = OW::getPluginManager()->getPlugin("vkconnect")->getMobileCmpViewDir() . "connect.html";
        $this->setTemplate($tpl);
    }
    
    public function initJs() 
    {
        // pass
    }
    
    public function getLoginUrl( $invCode )
    {
        return VKCONNECT_BOL_Service::getInstance()->getLoginUrl($invCode, false);
    }
    
    public function getSynchronizeUrl()
    {
        return VKCONNECT_BOL_Service::getInstance()->getSynchronizeUrl(false);
    }
    
    public function onBeforeRender() 
    {
        parent::onBeforeRender();
        
        $this->assign('label', OW::getLanguage()->text('vkconnect', 'connect_label_mobile'));
    }
}