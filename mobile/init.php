<?php

OW::getRouter()->addRoute(new OW_Route('vkconnect_auth', 'vk-connect/auth', 'VKCONNECT_CTRL_Connect', 'auth'));

$eventHandler = new VKCONNECT_MCLASS_EventHandler();
$eventHandler->genericInit();