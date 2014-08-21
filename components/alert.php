<?php

/**
 * Copyright (c) 2012, Sergey Kambalin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

class VKCONNECT_CMP_Alert extends OW_Component
{
    public function __construct()
    {
        parent::__construct();

        $form = new VKCONNECT_AlertForm();
        $this->addForm($form);
        
        $requiredEmail = OW::getConfig()->getValue('base', 'confirm_email') == 1 
                        || OW::getConfig()->getValue('vkconnect', 'require_email') == 1;

        $js = 'owForms[{$form}].bind("success", function(data)
        {
            if ( data.message )
            {
                
                OW.info(data.message);
                ' . ( OW::getConfig()->getValue('base', 'confirm_email') == 1 ? 'window.location.reload();' : 'VKCONNECT_AlertFB.close();' ) . ' 
            }

            if ( data.error )
            {
                OW.error(data.error);
            }
        });';

        $js = UTIL_JsGenerator::composeJsString($js , array(
            'form' => $form->getName()
        ));

        OW::getDocument()->addOnloadScript($js);

        $editUrl = OW::getRouter()->urlForRoute('base_edit');
        $this->assign('editUrl', $editUrl);
        $this->assign('confirmEmail', $requiredEmail);
    }
}

class VKCONNECT_AlertForm extends Form
{
    public function __construct()
    {
        parent::__construct('VKCONNECT_AlertForm');

        $this->setAjaxResetOnSuccess(false);
        $this->setAjax(true);
        $this->setAction( OW::getRouter()->urlFor('VKCONNECT_CTRL_Connect', 'alertRsp') );

        $language = OW::getLanguage();

        $field = new TextField('email');
        $field->addValidator(new EmailValidator());
        $field->setHasInvitation(true);
        $field->setInvitation($language->text('vkconnect', 'alert_email_inv'));

        $this->addElement($field);

        $submit = new Submit('save');
        $submit->setValue($language->text('vkconnect', 'alert_save_label'));
        $this->addElement($submit);
    }
}