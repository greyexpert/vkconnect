<?php

Updater::getConfigService()->addConfig('vkconnect', 'require_email', 0);
Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'vkconnect');