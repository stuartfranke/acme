<?php

namespace Acme\Tracker\Objects;

use Acme\Tracker\Traits\Config;

/**
 * Class Helper
 * @package Acme\Tracker\Objects
 */
class Helper
{
    use Config;

    const ERROR_MESSAGE_TYPE = 'error';
    const SUCCESS_MESSAGE_TYPE = 'success';

    /**
     * @return string
     */
    public function getLoginUrl(): string
    {
        $_SESSION['state'] = hash('sha256', microtime(true) . rand() . $_SERVER['REMOTE_ADDR']);

        $loginUrl = $this->getConfig('api_authorize_url') . '?' . http_build_query([
                'client_id' => $this->getConfig('api_id'),
                'redirect_uri' => $this->getConfig('api_callback_url'),
                'state' => $_SESSION['state'],
                'scope' => $this->getConfig('api_scope')
            ]);

        return $loginUrl;
    }

    /**
     * @return string
     */
    public function getLogoutUrl(): string
    {
        return $this->getConfig('app_url') . '/logout.php';
    }

    /**
     * @return string
     */
    public function getCreateUrl(): string
    {
        return $this->getConfig('app_url') . '/create.php';
    }

    /**
     * @param string $type
     * @param string $errorMessage
     */
    public function addMessage($type, $errorMessage)
    {
        $_SESSION[$type][] = $errorMessage;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getMessages($type): array
    {
        return isset($_SESSION[$type]) && is_array($_SESSION[$type]) ? array_unique($_SESSION[$type]) : [];
    }

    /**
     * @param string $type
     */
    public function clearMessages($type)
    {
        if (isset($_SESSION[$type])) {
            $_SESSION[$type] = [];
        }
    }

    /**
     * @param string $state
     * @return bool
     */
    public function validateState($state): bool
    {
        if (empty($state) || $state !== $_SESSION['state']) {
            return false;
        }

        return true;
    }

    /**
     * @param string $code
     * @return bool
     */
    public function requestAccessToken($code)
    {
        $api = new Api();
        $requestToken = $api->setUrl($this->getConfig('api_token_url'))
            ->setRequestMethod('POST')
            ->setPostFields([
                'client_id' => $this->getConfig('api_id'),
                'client_secret' => $this->getConfig('api_secret'),
                'redirect_uri' => $this->getConfig('api_callback_url'),
                'state' => $_SESSION['state'],
                'code' => $code,
            ])
            ->setHeaders(['Accept: application/json'])
            ->request();

        if (
            (int)$requestToken['response_code'] !== 200 ||
            !isset($requestToken['data']->access_token) ||
            isset($requestToken['data']->error)
        ) {
            $this->addMessage(
                self::ERROR_MESSAGE_TYPE,
                implode(
                    ' ',
                    [
                        'An error occurred while requesting the access token, please try to login again.',
                        $requestToken['response_error'] ?: '',
                        isset($requestToken['data']->error_description) ? $requestToken['data']->error_description : '',
                        isset($requestToken['data']->message) ? $requestToken['data']->message : '',
                    ]
                )
            );
            $this->redirect();
            exit;
        }

        $_SESSION['access_token'] = $requestToken['data']->access_token;

        $this->setUserLogin();

        return true;
    }

    /**
     * @param string $url
     */
    public function redirect($url = '')
    {
        if (empty($url)) {
            $url = $this->getConfig('app_url');
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Set user login session
     */
    protected function setUserLogin()
    {
        $api = new Api();

        $userRequest = $api->setUrl(sprintf(
            "%s/user?access_token=%s",
            $this->getConfig('api_url'),
            $_SESSION['access_token']
        ))
            ->setHeaders(['Content-Type: text/plain'])
            ->setRequestMethod('GET')
            ->request();

        if (
            (int)$userRequest['response_code'] !== 200 ||
            !isset($userRequest['data']->login) ||
            isset($userRequest['data']->error)
        ) {
            $this->addMessage(
                implode(
                    '',
                    [
                        'An error occurred while creating the issue, please try again',
                        $userRequest['response_error'] ?: '',
                        isset($userRequest['data']->error_description) ?
                            $userRequest['data']->error_description :
                            '',
                        isset($userRequest['data']->message) ?
                            $userRequest['data']->message :
                            '',
                    ]
                )
            );
        } else {
            $_SESSION['user_login'] = $userRequest['data']->login;
        }
    }
}
