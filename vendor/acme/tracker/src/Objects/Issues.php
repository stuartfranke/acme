<?php

namespace Acme\Tracker\Objects;

use Acme\Tracker\Traits\Config;
use Acme\Tracker\Traits\Validation;

/**
 * Class Issues
 * @package Acme\Tracker\Objects
 */
class Issues
{
    use Config;
    use Validation;

    const LABEL_CLIENT = 'C';
    const LABEL_PRIORITY = 'P';
    const LABEL_TYPE = 'T';

    /**
     * @return array
     */
    public function getIssues(): array
    {
        $this->checkLoginSession();

        $api = new Api();
        $issueResponseItems = $api->setUrl(sprintf(
            "%s/repos/%s/%s/issues?state=all",
            $this->getConfig('api_url'),
            $this->getConfig('api_user'),
            $this->getConfig('api_repo')
        ))
            ->setHeaders(['Content-Type: text/plain'])
            ->setRequestMethod('GET')
            ->request();

        if (
            (int)$issueResponseItems['response_code'] !== 200 ||
            !is_array($issueResponseItems['data']) ||
            isset($issueResponseItems['data']->error)
        ) {
            $this->addMessage(
                Helper::ERROR_MESSAGE_TYPE,
                implode(
                    ' ',
                    [
                        'An error occurred while requesting the issues',
                        $issueResponseItems['response_error'] ?: '',
                        isset($issueResponseItems['data']->error_description) ?
                            $issueResponseItems['data']->error_description :
                            '',
                        isset($issueResponseItems['data']->message) ?
                            $issueResponseItems['data']->message :
                            '',
                    ]
                )
            );

            return [];
        }

        return $this->setIssueReturnData($issueResponseItems['data']);
    }

    /**
     * @param array $issueResponseItems
     * @return array
     */
    protected function setIssueReturnData(array $issueResponseItems): array
    {
        $counter = 0;
        $issues = [];

        array_walk($issueResponseItems, function ($value, $key) use (&$issues, &$counter) {
            $issues[$counter]['number'] = $value->number;
            $issues[$counter]['title'] = $value->title;
            $issues[$counter]['body'] = $value->body;
            $issues[$counter]['client'] = implode(
                ', ',
                $this->parseLabelData(
                    $value->labels,
                    self::LABEL_CLIENT,
                    false
                )
            );
            $issues[$counter]['priority'] = implode(
                ', ',
                $this->parseLabelData(
                    $value->labels,
                    self::LABEL_PRIORITY,
                    false
                )
            );
            $issues[$counter]['type'] = implode(
                ', ',
                $this->parseLabelData(
                    $value->labels,
                    self::LABEL_TYPE,
                    false
                )
            );
            $issues[$counter]['assignees'] = $value->assignees = $this->getAssigneesData($value->assignees);
            $issues[$counter]['status'] = $value->state;

            $counter++;
        });

        return $issues;
    }

    /**
     * @param array $issueAssignees
     * @return string
     */
    protected function getAssigneesData(array $issueAssignees): string
    {
        $assignees = [];

        array_walk($issueAssignees, function ($assignee, $key) use (&$assignees) {
            $assignees[] = $assignee->login;
        });

        return implode(',', $assignees);
    }

    /**
     * @param array $issueLabels
     * @param $labelType $type
     * @param bool $returnRawLabels
     * @return array
     */
    public function parseLabelData(array $issueLabels, $labelType = '', $returnRawLabels = true): array
    {
        $labelData = [];

        array_walk(
            $issueLabels,
            function ($value, $key) use (&$labelData, $labelType, $returnRawLabels) {
                list($type, $label) = array_merge(explode(':', $value->name, 2), ['']);

                if (strtoupper($type) === $labelType) {
                    $labelData[] = (bool)$returnRawLabels === true ? trim($value->name) : trim($label);
                }
            }
        );

        return $labelData;
    }

    /**
     * @return array
     */
    public function getIssueLabels(): array
    {
        $this->checkLoginSession();

        $api = new Api();
        $labelResponseItems = $api->setUrl(sprintf(
            "%s/repos/%s/%s/labels",
            $this->getConfig('api_url'),
            $this->getConfig('api_user'),
            $this->getConfig('api_repo')
        ))
            ->setHeaders(['Content-Type: text/plain'])
            ->setRequestMethod('GET')
            ->request();

        if (
            (int)$labelResponseItems['response_code'] !== 200 ||
            !is_array($labelResponseItems['data']) ||
            isset($labelResponseItems['data']->error)
        ) {
            $this->addMessage(
                Helper::ERROR_MESSAGE_TYPE,
                implode(
                    ' ',
                    [
                        'An error occurred while requesting the issue labels',
                        $labelResponseItems['response_error'] ?: '',
                        isset($labelResponseItems['data']->error_description) ?
                            $labelResponseItems['data']->error_description :
                            '',
                        isset($labelResponseItems['data']->message) ?
                            $labelResponseItems['data']->message :
                            '',
                    ]
                )
            );

            return [];
        }

        return $labelResponseItems['data'];
    }

    /**
     * @param array $postData
     */
    public function createIssue(array $postData)
    {
        $this->checkLoginSession();
        $validationResult = $this->validateIssueCreateData($postData);

        if ((bool)$validationResult['success'] === false) {
            $helper = new Helper();

            foreach ($validationResult['messages'] as $message) {
                $helper->addMessage(Helper::ERROR_MESSAGE_TYPE, $message);
            }

            $_SESSION['show_form'] = 1;
            $helper->redirect();
            exit;
        }

        $api = new Api();
        $createIssueRequest = $api->setUrl(sprintf(
            "%s/repos/%s/%s/issues",
            $this->getConfig('api_url'),
            $this->getConfig('api_user'),
            $this->getConfig('api_repo')
        ))
            ->setRequestMethod('POST')
            ->setPostFields(json_encode([
                'title' => $postData['title'],
                'body' => $postData['body'],
                'labels' => [$postData['client'], $postData['priority'], $postData['type']],
                'assignees' => [isset($_SESSION['user_login']) ? $_SESSION['user_login'] : '']
            ]))
            ->setHeaders(['Accept: application/json'])
            ->request();

        if (
            (int)$createIssueRequest['response_code'] !== 201 ||
            !$createIssueRequest['data'] instanceof \stdClass ||
            isset($createIssueRequest['data']->error)
        ) {
            $_SESSION['show_form'] = 1;
            $this->addMessage(
                implode(
                    '',
                    [
                        'An error occurred while creating the issue, please try again',
                        $createIssueRequest['response_error'] ?: '',
                        isset($createIssueRequest['data']->error_description) ?
                            $createIssueRequest['data']->error_description :
                            '',
                        isset($createIssueRequest['data']->message) ?
                            $createIssueRequest['data']->message :
                            '',
                    ]
                )
            );
        } else {
            $this->addMessage(Helper::SUCCESS_MESSAGE_TYPE, 'Issue created successfully');
        }
    }

    /**
     * Validate login session
     */
    public function checkLoginSession()
    {
        if (empty($_SESSION['access_token'])) {
            $this->addMessage(Helper::ERROR_MESSAGE_TYPE, 'Access token has expired, please log in again', true);
            exit;
        }
    }

    /**
     * @param string $type
     * @param string $errorMessage
     * @param bool $redirect
     */
    protected function addMessage($type, $errorMessage, $redirect = false)
    {
        $helper = new Helper();
        $helper->addMessage($type, $errorMessage);

        if ((bool)$redirect === true) {
            $helper->redirect();
        }
    }
}
