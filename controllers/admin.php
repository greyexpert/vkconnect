<?php

/**
 * Copyright (c) 2012, Sergey Kambalin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

class VKCONNECT_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    private function getMenu()
    {
        $language = OW::getLanguage();

        $menuItems = array();

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('vkconnect', 'admin_menu_settings'));
        $item->setUrl(OW::getRouter()->urlForRoute('vkconnect_admin_settings'));
        $item->setKey('vkconnect_settings');
        $item->setIconClass('ow_ic_gear_wheel');
        $item->setOrder(0);

        $menuItems[] = $item;

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('vkconnect', 'admin_menu_fields'));
        $item->setUrl(OW::getRouter()->urlForRoute('vkconnect_admin_fields'));
        $item->setKey('vkconnect_fields');
        $item->setIconClass('ow_ic_files');
        $item->setOrder(1);

        $menuItems[] = $item;

        return new BASE_CMP_ContentMenu($menuItems);
    }

    public function settings()
    {
        $settingForm = new VKCONNECT_SettingsForm();
        $this->addForm($settingForm);

        if ( OW::getRequest()->isPost() && $settingForm->isValid($_POST) )
        {
            $settingForm->process();
            OW::getFeedback()->info(OW::getLanguage()->text('vkconnect', 'admin_settings_saved'));
            $this->redirect();
        }

        $this->addComponent('menu', $this->getMenu());

        OW::getDocument()->setHeading(OW::getLanguage()->text('vkconnect', 'admin_heading_settings'));
        OW::getDocument()->setHeadingIconClass('ow_ic_key');

        $addAppUrl = 'http://vk.com/editapp?act=create';
        $this->assign('addAppUrl', $addAppUrl);

        $appId = OW::getConfig()->getValue('vkconnect', 'client_id');
        $appUrl = null;
        if ( !empty($appId) )
        {
            $appUrl = 'http://vk.com/editapp?id=' . $appId;
        }

        $this->assign('appUrl', $appUrl);
    }

    public function fields()
    {
        $this->addComponent('menu', $this->getMenu());
        $this->assign('questions_url', OW::getRouter()->urlForRoute('questions_index'));

        OW::getDocument()->setHeading(OW::getLanguage()->text('vkconnect', 'admin_heading_fields'));
        OW::getDocument()->setHeadingIconClass('ow_ic_key');

        $service = VKCONNECT_BOL_Service::getInstance();
        $ignoreQuestionList = array(
            'email'
        );
        $questionDtoList = $service->findQuestionList();
        $aliases = $service->findAliasList();
        $aliases = array_flip($aliases);

        $questionList = array();
        foreach ( $questionDtoList as $dto )
        {
            /* @var $dto BOL_Question */
            if ( in_array($dto->name, $ignoreQuestionList) )
            {
                continue;
            }
            
            $sectionName = empty($dto->sectionName) ? "vkGeneralSection" : $dto->sectionName;

            $questionList[$sectionName][(int) $dto->sortOrder] = array(
                'name' => $dto->name,
                'vkFields' => $service->getFieldList($dto->name),
                'alias' => empty($aliases[$dto->name]) ? '' : $aliases[$dto->name]
            );
        }

        $questionSectionDtoList = BOL_QuestionService::getInstance()->findAllSections();
        
        $tplSectionQuestionList = array();
        

		if ( !empty($questionList["vkGeneralSection"]) )
		{
        	$tplSectionQuestionList[0] = array(
            	'name' => "vkGeneralSection",
            	'items' => $questionList["vkGeneralSection"]
        	);
        }
        
        foreach ( $questionSectionDtoList as $sectionDto )
        {
            if ( empty($questionList[$sectionDto->name]) )
            {
                continue;
            }

            /* @var $sectionDto BOL_QuestionSection */
            $tplSectionQuestionList[(int) $sectionDto->sortOrder] = array(
                'name' => $sectionDto->name,
                'items' => $questionList[$sectionDto->name]
            );
        }
        
        
        ksort($tplSectionQuestionList);
        $this->assign('questionList', $tplSectionQuestionList);

        $this->assign('formAction', OW::getRouter()->urlFor('VKCONNECT_CTRL_Admin', 'formProcess'));
    }

    public function formProcess()
    {
        if ( empty($_POST['alias']) )
        {
            $this->redirect(OW::getRouter()->urlForRoute('vkconnect_admin_fields'));
        }

        $list = $_POST['alias'];

        $alias = VKCONNECT_BOL_Service::getInstance()->findAliasList();

        foreach ( $list as $question => $field )
        {
            if ( !empty($field) )
            {
                $alias[$field] = $question;
            }
            else
            {
                unset($alias[$field]);
            }
        }

        VKCONNECT_BOL_Service::getInstance()->saveAliasList($alias);

        $this->redirect(OW::getRouter()->urlForRoute('vkconnect_admin_fields'));
    }
}

class VKCONNECT_SettingsForm extends Form
{

    public function __construct()
    {
        parent::__construct('VKCONNECT_SettingsForm');

        $config = OW::getConfig();

        $field = new TextField('client_id');
        $field->setRequired(true);
        $field->setValue($config->getValue('vkconnect', 'client_id'));
        $this->addElement($field);

        $field = new TextField('client_secret');
        $field->setRequired(true);
        $field->setValue($config->getValue('vkconnect', 'client_secret'));
        $this->addElement($field);

        $field = new CheckboxField('allow_synchronize');
        $field->setValue((bool) $config->getValue('vkconnect', 'allow_synchronize'));
        $this->addElement($field);

        $field = new CheckboxField('synchronize_avatar');
        $field->setValue((bool) $config->getValue('vkconnect', 'synchronize_avatar'));
        $this->addElement($field);
        
        $field = new CheckboxField('require_email');
        $field->setValue((bool) $config->getValue('vkconnect', 'require_email'));
        $this->addElement($field);

        // submit
        $submit = new Submit('save');
        $submit->setValue(OW::getLanguage()->text('vkconnect', 'save_btn_label'));
        $this->addElement($submit);
    }

    public function process()
    {
        $values = $this->getValues();
        $config = OW::getConfig();

        $config->saveConfig('vkconnect', 'client_id', trim($values['client_id']) );
        $config->saveConfig('vkconnect', 'client_secret', trim($values['client_secret']) );
        $config->saveConfig('vkconnect', 'allow_synchronize', empty($values['allow_synchronize']) ? 0 : 1 );
        $config->saveConfig('vkconnect', 'synchronize_avatar', empty($values['synchronize_avatar']) ? 0 : 1 );
        $config->saveConfig('vkconnect', 'require_email', empty($values['require_email']) ? 0 : 1 );

        return true;
    }
}