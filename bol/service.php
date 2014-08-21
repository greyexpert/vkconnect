<?php

/**
 * Copyright (c) 2012, Sergey Kambalin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

class VKCONNECT_BOL_Service
{
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return VKCONNECT_BOL_Service
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function findQuestionList()
    {
        $questions = BOL_QuestionService::getInstance()->findAllQuestions();
        
        $out = array();
        foreach ($questions as $question)
        {
            /* @var $question BOL_Question */
            $isText = in_array($question->presentation, array('text', 'textarea', 'url'));

            if ($isText || $question->name == 'birthdate')
            {
                $out[] = $question;
            }
        }

        return $out;
    }

    public function getFieldList($questionName = null)
    {
        switch ($questionName)
        {
            case 'username':
                return array('generate_name');
            case 'birthdate':
                return array('bdate');
        }

        return array('first_name', 'last_name', 'nickname', 'bdate', 'city', 'country',
            'mobile_phone', 'home_phone', 'university_name', 'faculty_name', 'graduation', 'domain');
    }

    public function findAliasList()
    {
        $alias = OW::getConfig()->getValue('vkconnect', 'field_alias');
        if ( empty($alias) )
        {
            return array();
        }

        $alias = json_decode($alias, true);

        return $alias;
    }

    public function saveAliasList( $list )
    {
        OW::getConfig()->saveConfig('vkconnect', 'field_alias', json_encode($list));
    }

    public function getLoginUrl( $inviteCode = null, $popup = true )
    {
        $inviteCode = empty($inviteCode) ? '' : $inviteCode;
        $backUri = OW::getRequest()->getRequestUri();
        $callback = OW::getRouter()->uriFor('VKCONNECT_CTRL_Connect', 'login') . '?code=' . $inviteCode . '&backUri=' . urlencode($backUri);

        return $this->getAuthUrl($callback, $popup);
    }

    public function getSynchronizeUrl($popup = true)
    {
        $backUri = OW::getRequest()->getRequestUri();
        $callback = OW::getRouter()->uriFor('VKCONNECT_CTRL_Connect', 'synchronize') . '?backUri=' . urlencode($backUri);

        return $this->getAuthUrl($callback, $popup);
    }

    public function getRedirectUri($callback, $popup = true)
    {
        $redirectUrl = OW::getRouter()->urlForRoute('vkconnect_auth');
        $redirectUrl = OW::getRequest()->buildUrlQueryString($redirectUrl, array(
            'callbackUri' => urlencode($callback),
            "popup" => $popup ? 1 : 0
        ));
        
        return urlencode($redirectUrl);
    }
    
    public function getAuthUrl( $callback, $popup = true )
    {
        $configs = OW::getConfig()->getValues('vkconnect');

        $appId = $configs['client_id'];
        $scope = '';
        $redirectUrl = $this->getRedirectUri($callback, $popup);

        return "https://oauth.vk.com/authorize?client_id=$appId&scope=$scope&redirect_uri=$redirectUrl&response_type=code&display=" . ($popup ? "popup" : "page");
    }

    public function isAppReady()
    {
        $configs = OW::getConfig()->getValues('vkconnect');

        return !(empty($configs['client_id']) || empty($configs['client_secret']));
    }
}