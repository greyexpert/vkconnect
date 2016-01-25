<?php

/**
 * Copyright (c) 2012, Sergey Kambalin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

class VKCONNECT_CMP_Connect extends OW_Component
{
    const TYPE_SYNC = 'sync';
    const TYPE_LOGIN = 'login';

    private $type;

    public function __construct( $type )
    {
        parent::__construct();

        $this->type = $type;
    }

    public function setCallback( $callback )
    {

    }

    public function initJs()
    {
        $js = UTIL_JsGenerator::newInstance();
        $js->jQueryEvent('.vkconnect-connect', 'click',
            'var top = ( window.screen.height / 2 ) - 350 / 2;
            var left = ( window.screen.width / 2 ) - 620 / 2;
            window.open($(this).attr("vkauthurl"), "vkconnect-login", "width=620,height=345,status=no,toolbar=no,menubar=no,top=" + top + ",left=" + left)');

        OW::getDocument()->addOnloadScript($js);
    }

    public function getLoginUrl( $invCode )
    {
        return VKCONNECT_BOL_Service::getInstance()->getLoginUrl($invCode);
    }

    public function getSynchronizeUrl()
    {
        return VKCONNECT_BOL_Service::getInstance()->getSynchronizeUrl();
    }

    public function onBeforeRender()
    {
        $cssUrl = OW::getPluginManager()->getPlugin('vkconnect')->getStaticUrl() . 'vkconnect.css';
        OW::getDocument()->addStyleSheet($cssUrl);

        if ( $this->type == self::TYPE_LOGIN )
        {
            $invCode = empty($_GET['code']) ? null : $_GET['code'];
            $popupUrl = $this->getLoginUrl($invCode);
            $this->assign('label', OW::getLanguage()->text('vkconnect', 'connect_label'));
        }
        else
        {
            $allowSync = OW::getConfig()->getValue('vkconnect', 'allow_synchronize');

            if ( !$allowSync )
            {
                $vkUser = BOL_RemoteAuthService::getInstance()->findByUserId(OW::getUser()->getId());
                $allowSync = !empty($vkUser) && $vkUser->type = 'vk';
            }

            if ( !$allowSync )
            {
                $this->setVisible(false);
                return;
            }

            $popupUrl = $this->getSynchronizeUrl();
            $this->assign('label', OW::getLanguage()->text('vkconnect', 'synchronize_label'));
        }

        $this->assign('popupUrl', $popupUrl);

        $this->initJs();

        $logoIconUrl = OW::getPluginManager()->getPlugin('vkconnect')->getStaticUrl() . 'vk.png';
        $css = '.vk_ic_logo { background-image: url(' . $logoIconUrl . ') }';
        OW::getDocument()->addStyleDeclaration($css);
    }
}