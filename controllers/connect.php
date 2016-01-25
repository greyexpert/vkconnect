<?php

/**
 * Copyright (c) 2012, Sergey Kambalin
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

class VKCONNECT_CTRL_Connect extends OW_ActionController
{
    private function getInfo( $token, $userId )
    {
        $infoRequest = "https://api.vk.com/method/getProfiles?uid=$userId&fields=uid,first_name,last_name,nickname,domain,sex,bdate,city,country,timezone,photo,photo_medium,photo_big,has_mobile,rate,contacts,education&access_token=$token";
        $infoResponce = $this->apiRequest($infoRequest);
        $info = json_decode($infoResponce, true);

        if ( isset($tokenData['error']) )
        {
            return null;
        }

        $out = $info['response'][0];

        if ( !empty($out['city']) )
        {
            $request = "https://api.vk.com/method/getCities?cids={$out['city']}&access_token=$token";
            $response = $this->apiRequest($request);
            $response = json_decode($response, true);

            if ( !empty($response['response'][0]['name']) )
            {
                $out['city'] = $response['response'][0]['name'];
            }
        }

        if ( !empty($out['country']) )
        {
            $request = "https://api.vk.com/method/getCountries?cids={$out['country']}&access_token=$token";
            $response = $this->apiRequest($request);
            $response = json_decode($response, true);

            if ( !empty($response['response'][0]['name']) )
            {
                $out['country'] = $response['response'][0]['name'];
            }
        }

        return $out;
    }

    private function convert( $alias, $values )
    {
        $tmpDir = OW::getPluginManager()->getPlugin('VKCONNECT')->getPluginFilesDir();

        $out = array();
        foreach ( $alias as $field => $question )
        {
            $fValue = empty($values[$field]) ? null : $values[$field];
            $qValue = null;

            switch ( $field )
            {
                case 'bdate':
                    $date = explode('.', $fValue);
                    $year = empty($date[2]) ? 0 : $date[2];
                    $qValue = $year . '/' . $date[1] . '/' . $date[0];
                    break;

                case 'domain':

                    $qValue = 'http://vk.com/' . $fValue;
                    break;

                case 'sex':

                    $qValue = $fValue == 1 ? 2 : 1;
                    break;

                case 'username':
                    if ( empty($values['first_name']) && empty($values['last_name']) )
                    {
                        $qValue = $values['domain'];
                        break;
                    }

                    $qValue = $this->usernameConvert($values['first_name'], $values['last_name']);
                    break;

                case 'photo':
                case 'photo_medium':
                case 'photo_big':

                    $fileName = $tmpDir . 'tmp_pic_' . md5($question . $fValue . time()) . '.jpg';
                    if ( @copy($fValue, $fileName) )
                    {
                        $qValue = $fileName;
                    }
                    break;

                default:
                    $qValue = $fValue;
            }

            $out[$question] = $qValue;
        }

        return $out;
    }


    private function transLate($string)
    {

        $arr = array(
            'А' => 'A' , 'Б' => 'B' , 'В' => 'V'  , 'Г' => 'G',
            'Д' => 'D' , 'Е' => 'E' , 'Ё' => 'JO' , 'Ж' => 'ZH',
            'З' => 'Z' , 'И' => 'I' , 'Й' => 'JJ' , 'К' => 'K',
            'Л' => 'L' , 'М' => 'M' , 'Н' => 'N'  , 'О' => 'O',
            'П' => 'P' , 'Р' => 'R' , 'С' => 'S'  , 'Т' => 'T',
            'У' => 'U' , 'Ф' => 'F' , 'Х' => 'H' , 'Ц' => 'C',
            'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SH', 'Ъ' => '',
            'Ы' => 'Y' , 'Ь' => '', 'Э' => 'EH' , 'Ю' => 'JU',
            'Я' => 'JA',
            'а' => 'a' , 'б' => 'b'  , 'в' => 'v' , 'г' => 'g', 'д' => 'd',
            'е' => 'e' , 'ё' => 'jo' , 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'j', 'к' => 'k'  , 'л' => 'l' , 'м' => 'm', 'н' => 'n',
            'о' => 'o' , 'п' => 'p'  , 'р' => 'r' , 'с' => 's', 'т' => 't',
            'у' => 'u' , 'ф' => 'f'  , 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sh', 'ъ' => '' , 'ы' => 'y', 'ь' => '',
            'э' => 'eh', 'ю' => 'ju' , 'я' => 'ja'
        );

        $key = array_keys($arr);
        $val = array_values($arr);
        $translate = str_replace($key, $val, $string);

        return $translate;
    }

    public function usernameConvert( $p1, $p2, $tc = 0 )
    {
        $p1 = $this->transLate($p1);
        $p2 = $this->transLate($p2);

        $username = $p1 . $p2;

        if ( BOL_UserService::getInstance()->isExistUserName($username) )
        {
            $tc++;
            return $this->usernameConvert($p1, $p2, $tc);
        }

        return $username . ( empty($tc) ? '' : $tc );
    }

    public function auth()
    {
        $popup = !empty($_GET["popup"]);

        if ($popup)
        {
            OW::getDocument()->getMasterPage()->setTemplate(OW::getThemeManager()->getMasterPageTemplate('blank'));
        }

        if ( empty($_GET['code']) )
        {
            if ( !$popup )
            {
                $this->redirect(OW_URL_HOME);
            }

            return;
        }

        $code = $_GET['code'];

        $callbackUri = urldecode($_GET['callbackUri']);
        $callbackUrl = OW_URL_HOME . $callbackUri;

        $configs = OW::getConfig()->getValues('vkconnect');
        $appId = $configs['client_id'];
        $appSecret = $configs['client_secret'];

        $redirectUrl = VKCONNECT_BOL_Service::getInstance()->getRedirectUri($callbackUri, $popup);

        $tokenRequest = "https://oauth.vk.com/access_token?client_id=$appId&client_secret=$appSecret&code=$code&redirect_uri=$redirectUrl";
        $tokenResponce = $this->apiRequest($tokenRequest);

        $tokenData = json_decode($tokenResponce, true);

        if ( isset($tokenData['error']) )
        {
            if ($popup)
            {
                $this->assign('callback', json_encode($callbackUrl));
            }
            else
            {
                $this->redirect($callbackUrl);
            }

            return;
        }

        $userId = $tokenData['user_id'];
        $token = $tokenData['access_token'];
        $email = isset($tokenData['email']) ? $tokenData['email'] : null;

        $callback = OW::getRequest()->buildUrlQueryString($callbackUrl, array(
            'token' => $token,
            'user' => $userId,
            'email' => $email
        ));

        if ($popup)
        {
            $this->assign('callback', json_encode($callback));
        }
        else
        {
            $this->redirect($callback);
        }
    }


    private function apiRequest( $url )
    {
        if ( function_exists('curl_init') )
        {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_USERAGENT, 'vkPhpSdk');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $data = curl_exec($ch);

            curl_close($ch);
        }
        else
        {
            $data = file_get_contents($url);
        }

        return $data;
    }

    public function login()
    {
        $backUri = empty($_GET['backUri']) ? '' : urldecode($_GET['backUri']);
        $invCode = empty($_GET['code']) ? null : $_GET['code'];

        $backUrl = OW_URL_HOME . $backUri;

        if ( empty($_GET['token']) || empty($_GET['user']))
        {
            $this->redirect($backUrl);
        }

        $info = $this->getInfo($_GET['token'], $_GET['user']);
        if ( empty($info) )
        {
            $this->redirect($backUrl);
        }

        $language = OW::getLanguage();

        $vkUser = $info['uid'];

        $authAdapter = new OW_RemoteAuthAdapter($vkUser, 'vk');

        if ( $authAdapter->isRegistered() )
        {
            $authResult = OW::getUser()->authenticate($authAdapter);
            if ( $authResult->isValid() )
            {
                $emailRequired = BOL_PreferenceService::getInstance()->getPreferenceValue('vkconnect_email_required', OW::getUser()->getId());
                if ( !$emailRequired )
                {
                    OW::getFeedback()->info($language->text('vkconnect', 'login_success_msg'));
                }
                else
                {
                    OW::getSession()->set('vkconnect-remind', 1);
                }
            }

            $this->redirect($backUrl);
        }

        $service = VKCONNECT_BOL_Service::getInstance();
        $alias = $service->findAliasList();

        $alias['photo'] = 'picture_small';
        $alias['photo_medium'] = 'picture_medium';
        $alias['photo_big'] = 'picture_big';
        $alias['bdate'] = 'birthdate';
        $alias['username'] = 'username';
        $alias['sex'] = 'sex';

        $questions = $this->convert($alias, $info);

        //Register if not registered

        $username = $questions['username'];
        $homeUrl = parse_url(OW_URL_HOME);
        //Fake email
        $email = 'vk.' . $vkUser . '@' . $homeUrl['host'];
        $emailIsFetched = false;

        if ( !empty($_GET["email"]) )
        {
            $email = $_GET["email"];
            $emailIsFetched = true;
        }

        $password = uniqid();

        try
        {
            $user = BOL_UserService::getInstance()->createUser($username, $password, $email, null, $emailIsFetched);
            unset($questions['username']);
            unset($email);
        }
        catch ( Exception $e )
        {
            switch ( $e->getCode() )
            {
                case BOL_UserService::CREATE_USER_DUPLICATE_EMAIL:
                    OW::getFeedback()->error($language->text('vkconnect', 'join_dublicate_email_msg'));
                    $this->redirect($backUrl);
                    break;

                case BOL_UserService::CREATE_USER_INVALID_USERNAME:
                    OW::getFeedback()->error($language->text('vkconnect', 'join_incorrect_username'));
                    $this->redirect($backUrl);
                    break;

                default:
                    OW::getFeedback()->error($language->text('vkconnect', 'join_incomplete'));
                    $this->redirect($backUrl);
            }
        }

        if ( !empty($questions['picture_small']) )
        {
            $avatar = new BOL_Avatar();
            $avatar->hash = time();
            $avatar->userId = $user->id;

            $avatarService = BOL_AvatarService::getInstance();

            $pfSmallPicDir = $avatarService->getAvatarPluginFilesPath($user->id, 1, $avatar->hash);
            $pfMediumPicDir = $avatarService->getAvatarPluginFilesPath($user->id, 2, $avatar->hash);
            $pfBigPicDir = $avatarService->getAvatarPluginFilesPath($user->id, 3, $avatar->hash);

            $smallPicDir = $avatarService->getAvatarPath($user->id, 1, $avatar->hash);
            $mediumPicDir = $avatarService->getAvatarPath($user->id, 2, $avatar->hash);
            $bigPicDir = $avatarService->getAvatarPath($user->id, 3, $avatar->hash);

            $storage = OW::getStorage();

            $avatarSaveResult = 0;

            if ( rename($questions['picture_small'], $pfSmallPicDir) )
            {
                $avatarSaveResult += (int) $storage->copyFile($pfSmallPicDir, $smallPicDir);
            }

            if ( !empty($questions['picture_medium']) )
            {
                if ( rename($questions['picture_medium'], $pfMediumPicDir) )
                {
                    $avatarSaveResult += (int) $storage->copyFile($pfMediumPicDir, $mediumPicDir);
                }
            }
            else
            {
                $avatarSaveResult += (int) $storage->copyFile($pfSmallPicDir, $mediumPicDir);
            }

            if ( !empty($questions['picture_big']) )
            {
                if ( rename($questions['picture_big'], $pfBigPicDir) )
                {
                    $avatarSaveResult += (int) $storage->copyFile($pfBigPicDir, $bigPicDir);
                }
            }
            else
            {
                $avatarSaveResult += (int) $storage->copyFile($pfSmallPicDir, $bigPicDir);
            }

            if ( $avatarSaveResult )
            {
                $avatarService->updateAvatar($avatar);
            }

            unset($questions['picture_small']);
            unset($questions['picture_medium']);
            unset($questions['picture_big']);
        }

        BOL_QuestionService::getInstance()->saveQuestionsData(array_filter($questions), $user->id);

        $authAdapter->register($user->id);

        $authResult = OW_Auth::getInstance()->authenticate($authAdapter);
        if ( $authResult->isValid() )
        {
            $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array(
                'method' => 'vk',
                'userId' => $user->id,
                'params' => array(
                    'code' => $invCode
                )
            ));

            OW::getEventManager()->trigger($event);
            OW::getSession()->set('vkconnect-remind', 2);

            BOL_PreferenceService::getInstance()->savePreferenceValue('vkconnect_email_required', $emailIsFetched ? 0 : 1, $user->id);
        }

        $this->redirect($backUrl);
    }

    public function synchronize()
    {
        $userId = OW::getUser()->getId();

        if ( empty($userId) )
        {
            throw new AuthenticateException();
        }

        $backUri = empty($_GET['backUri']) ? '' : urldecode($_GET['backUri']);
        $backUrl = OW_URL_HOME . $backUri;

        $info = $this->getInfo($_GET['token'], $_GET['user']);
        if ( empty($info) )
        {
            $this->redirect($backUrl);
        }

        $language = OW::getLanguage();

        $questionsService = BOL_QuestionService::getInstance();
        $userService = BOL_UserService::getInstance();

        $accountType = $userService->findUserById($userId)->getAccountType();
        $editQuestionsDtoList = $questionsService->findEditQuestionsForAccountType($accountType);

        $editQuestions = array();
        foreach ( $editQuestionsDtoList as $item )
        {
            $editQuestions[] = $item['name'];
        }

        $service = VKCONNECT_BOL_Service::getInstance();
        $alias = $service->findAliasList();

        $alias['bdate'] = 'birthdate';
        $alias['sex'] = 'sex';

        foreach ( $alias as $f => $q )
        {
            if ( !in_array($q, $editQuestions) )
            {
                unset($alias[$f]);
            }
        }

        if ( OW::getConfig()->getValue('vkconnect', 'synchronize_avatar') )
        {
            $alias['photo'] = 'picture_small';
            $alias['photo_medium'] = 'picture_medium';
            $alias['photo_big'] = 'picture_big';
        }

        $questions = $this->convert($alias, $info);

        $questionsService->saveQuestionsData(array_filter($questions), $userId);

        if ( !empty($questions['picture_small']) )
        {
            $storage = OW::getStorage();
            $avatarService = BOL_AvatarService::getInstance();
            $avatarService->deleteUserAvatar($userId);

            $avatar = new BOL_Avatar();
            $avatar->hash = time();
            $avatar->userId = $userId;

            $pfSmallPicDir = $avatarService->getAvatarPluginFilesPath($userId, 1, $avatar->hash);
            $pfMediumPicDir = $avatarService->getAvatarPluginFilesPath($userId, 2, $avatar->hash);
            $pfBigPicDir = $avatarService->getAvatarPluginFilesPath($userId, 3, $avatar->hash);

            $smallPicDir = $avatarService->getAvatarPath($userId, 1, $avatar->hash);
            $mediumPicDir = $avatarService->getAvatarPath($userId, 2, $avatar->hash);
            $bigPicDir = $avatarService->getAvatarPath($userId, 3, $avatar->hash);

            $avatarSaveResult = 0;

            if ( rename($questions['picture_small'], $pfSmallPicDir) )
            {
                $avatarSaveResult += (int) $storage->copyFile($pfSmallPicDir, $smallPicDir);
            }

            if ( !empty($questions['picture_medium']) )
            {
                if ( rename($questions['picture_medium'], $pfMediumPicDir) )
                {
                    $avatarSaveResult += (int) $storage->copyFile($pfMediumPicDir, $mediumPicDir);
                }
            }
            else
            {
                $avatarSaveResult += (int) $storage->copyFile($pfSmallPicDir, $mediumPicDir);
            }

            if ( !empty($questions['picture_big']) )
            {
                if ( rename($questions['picture_big'], $pfBigPicDir) )
                {
                    $avatarSaveResult += (int) $storage->copyFile($pfBigPicDir, $bigPicDir);
                }
            }
            else
            {
                $avatarSaveResult += (int) $storage->copyFile($pfSmallPicDir, $bigPicDir);
            }

            if ( $avatarSaveResult )
            {
                $avatarService->updateAvatar($avatar);
            }

            unset($questions['picture_small']);
            unset($questions['picture_medium']);
            unset($questions['picture_big']);
        }

        OW::getFeedback()->info($language->text('vkconnect', 'synchronize_success_msg'));
        $event = new OW_Event(OW_EventManager::ON_USER_EDIT, array('method' => 'vk', 'userId' => $userId));
        OW::getEventManager()->trigger($event);

        $this->redirect($backUrl);
    }

    public function alertRsp()
    {
        if ( !OW::getRequest()->isAjax() || !OW::getUser()->isAuthenticated() )
        {
            throw new Redirect404Exception();
        }

        $userDto = OW::getUser()->getUserObject();
        $email = $_POST['email'];

        $emailUser = BOL_UserService::getInstance()->findByEmail($email);

        if ( $emailUser !== null && $userDto->id != $emailUser->id )
        {
            echo json_encode(array(
                'error' => OW::getLanguage()->text('vkconnect', 'user_with_email_exists')
            ));
            exit;
        }

        $userDto->email = $email;
        $userDto->emailVerify = 0;
        BOL_UserService::getInstance()->saveOrUpdate($userDto);

        BOL_PreferenceService::getInstance()->savePreferenceValue('vkconnect_email_required', 0, $userDto->getId());

        $remind = OW::getSession()->get('vkconnect-remind');
        $msg = $remind == 3
            ? OW::getLanguage()->text('vkconnect', 'join_success_msg')
            : OW::getLanguage()->text('vkconnect', 'alert_email_updated');

        OW::getSession()->delete('vkconnect-remind');

        echo json_encode(array(
            'message' => $msg
        ));
        exit;
    }
}