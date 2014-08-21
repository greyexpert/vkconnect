<?php

class VKCONNECT_MCLASS_EventHandler extends VKCONNECT_CLASS_EventHandler
{
    public function onCollectButtonList( BASE_CLASS_EventCollector $event )
    {
        $cssUrl = OW::getPluginManager()->getPlugin('vkconnect')->getStaticCssUrl() . 'vkconnect.css';
        OW::getDocument()->addStyleSheet($cssUrl);

        $button = new VKCONNECT_MCMP_Connect(VKCONNECT_MCMP_Connect::TYPE_LOGIN);
        $event->add(array('iconClass' => 'ow_ico_signin_vk', 'markup' => $button->render()));
    }
}