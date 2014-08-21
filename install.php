<?php

/**
 * Copyright (c) 2012, Sergey Kambalin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

OW::getConfig()->addConfig('vkconnect', 'client_id', '');
OW::getConfig()->addConfig('vkconnect', 'client_secret', '');
OW::getConfig()->addConfig('vkconnect', 'allow_synchronize', 1);
OW::getConfig()->addConfig('vkconnect', 'synchronize_avatar', 0);
OW::getConfig()->addConfig('vkconnect', 'require_email', 0);

OW::getConfig()->addConfig('vkconnect', 'field_alias', json_encode(array(
    'first_name' => 'realname'
)));

OW::getPluginManager()->addPluginSettingsRouteName('vkconnect', 'vkconnect_admin_settings');

BOL_LanguageService::getInstance()->importPrefixFromZip( dirname(__FILE__) . DS . 'langs.zip', 'vkconnect');



$preference = BOL_PreferenceService::getInstance()->findPreference('vkconnect_email_required');

if ( empty($preference) )
{
    $preference = new BOL_Preference();
}

$preference->key = 'vkconnect_email_required';
$preference->sectionName = 'general';
$preference->defaultValue = 0;
$preference->sortOrder = 10001;

BOL_PreferenceService::getInstance()->savePreference($preference);