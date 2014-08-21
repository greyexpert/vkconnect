<?php

/**
 * Copyright (c) 2012, Sergey Kambalin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

OW::getRouter()->addRoute(new OW_Route('vkconnect_auth', 'vk-connect/auth', 'VKCONNECT_CTRL_Connect', 'auth'));
OW::getRouter()->addRoute(new OW_Route('vkconnect_admin_fields', 'admin/plugins/vkconnect/fields', 'VKCONNECT_CTRL_Admin', 'fields'));
OW::getRouter()->addRoute(new OW_Route('vkconnect_admin_settings', 'admin/plugins/vkconnect/settings', 'VKCONNECT_CTRL_Admin', 'settings'));

if ( VKCONNECT_BOL_Service::getInstance()->isAppReady() )
{
    $registry = OW::getRegistry();

    $loginBtn = new VKCONNECT_CMP_Connect(VKCONNECT_CMP_Connect::TYPE_LOGIN);
    $registry->addToArray(BASE_CTRL_Join::JOIN_CONNECT_HOOK, array($loginBtn, 'render'));

    $registry->addToArray(BASE_CMP_ConnectButtonList::HOOK_REMOTE_AUTH_BUTTON_LIST, array($loginBtn, 'render'));

    $syncBtn = new VKCONNECT_CMP_Connect(VKCONNECT_CMP_Connect::TYPE_SYNC);
    $registry->addToArray(BASE_CTRL_Edit::EDIT_SYNCHRONIZE_HOOK, array($syncBtn, 'render'));
}

$eventHandler = new VKCONNECT_CLASS_EventHandler();
$eventHandler->init();