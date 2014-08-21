<?php

class VKCONNECT_CLASS_EventHandler
{
    public function onCollectButtonList( BASE_CLASS_EventCollector $event )
    {
        $cssUrl = OW::getPluginManager()->getPlugin('vkconnect')->getStaticUrl() . 'vkconnect.css';
        OW::getDocument()->addStyleSheet($cssUrl);

        $button = new VKCONNECT_CMP_Connect(VKCONNECT_CMP_Connect::TYPE_LOGIN);
        $event->add(array('iconClass' => 'ow_ico_signin_vk', 'markup' => $button->render()));
    }

    public function afterUserRegistered( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['method'] != 'vk' )
        {
            return;
        }

        $userId = (int) $params['userId'];

        $event = new OW_Event('feed.action', array(
            'pluginKey' => 'vkconnect',
            'entityType' => 'user_join',
            'entityId' => $userId,
            'userId' => $userId,
            'replace' => true,
        ), array(
            'string' => array("key" => 'vkconnect+feed_user_join'),
            'view' => array(
                'iconClass' => 'ow_ic_user'
            )
        ));
        
        OW::getEventManager()->trigger($event);
    }

    public function afterUserSynchronized( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['method'] !== 'vk' )
        {
            return;
        }
        
        $event = new OW_Event(OW_EventManager::ON_USER_EDIT, array(
            'method' => 'native',
            'userId' => $params['userId']
        ));
        
        OW::getEventManager()->trigger($event);
    }
    
    public function onCollectAccessExceptions( BASE_CLASS_EventCollector $event ) 
    {
        $event->add(array('controller' => 'VKCONNECT_CTRL_Connect', 'action' => 'login'));
        $event->add(array('controller' => 'VKCONNECT_CTRL_Connect', 'action' => 'alertRsp'));
        $event->add(array('controller' => 'VKCONNECT_CTRL_Connect', 'action' => 'auth'));
    }
    
    public function onCollectAdminNotification( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        
        if ( !VKCONNECT_BOL_Service::getInstance()->isAppReady() )
        {
            $event->add($language->text('vkconnect', 'admin_configuration_required_notification', array( 
                'href' => OW::getRouter()->urlForRoute('vkconnect_admin_settings') 
            )));
        }
    }    
    
    public function beforeDocumentRender()
    {
        $userId = OW::getUser()->getId();
    
        $confirmEmail = OW::getConfig()->getValue('base', 'confirm_email') == 1;

        if ($confirmEmail)
        {
            return;
        }

        if ( empty($userId) )
        {
            return;
        }

        $emailNotUpdated = BOL_PreferenceService::getInstance()->getPreferenceValue('vkconnect_email_required', $userId);

        if ( !$emailNotUpdated )
        {
            return;
        }

        $remind = OW::getSession()->get('vkconnect-remind');
        $emailRequired = OW::getConfig()->getValue('vkconnect', 'require_email');

        if ( !$emailRequired && (empty($remind) || $remind == 3) )
        {
            return;
        }

        $js = UTIL_JsGenerator::newInstance()->addScript('VKCONNECT_AlertFB = OW.ajaxFloatBox("VKCONNECT_CMP_Alert", [], {$params});', array(
            'params' => array(
                'title' => OW::getLanguage()->text('vkconnect', 'alert_fb_title'),
                'iconClass' => 'ow_ic_info',
                'width' => 300
            )
        ));

        if ( $emailRequired )
        {
            $js->addScript('VKCONNECT_AlertFB.bind("close", function() { OW.error({$msg}); return false; });', array(
                "msg" => OW::getLanguage()->text('vkconnect', 'enter_email_message')
            ));
        }

        OW::getDocument()->addOnloadScript($js);

        if ( $remind == 1 )
        {
            OW::getSession()->delete('vkconnect-remind');
        }
        else
        {
            OW::getSession()->set('vkconnect-remind', 3);
        }
    }
    
    public function genericInit()
    {
        OW::getEventManager()->bind(BASE_CMP_ConnectButtonList::HOOK_REMOTE_AUTH_BUTTON_LIST, array($this, "onCollectButtonList"));
        OW::getEventManager()->bind(OW_EventManager::ON_USER_REGISTER, array($this, "afterUserRegistered"));
        OW::getEventManager()->bind(OW_EventManager::ON_USER_EDIT, array($this, "afterUserSynchronized"));
        
        OW::getEventManager()->bind('base.members_only_exceptions', array($this, "onCollectAccessExceptions"));
        OW::getEventManager()->bind('base.password_protected_exceptions', array($this, "onCollectAccessExceptions"));
        OW::getEventManager()->bind('base.splash_screen_exceptions', array($this, "onCollectAccessExceptions"));
    }
    
    public function init()
    {
        $this->genericInit();
        
        OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($this, "beforeDocumentRender"));
        OW::getEventManager()->bind('admin.add_admin_notification', array($this, "onCollectAdminNotification"));
    }
}