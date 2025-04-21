<?php

namespace App\Services\Megaplan;

use App\Services\Megaplan\Data\MegaplanAuthResponseData;

class MegaplanApiClient
{
    private string $host;
    private string $login;
    private string $password;

    private string $accessId;
    private string $secretKey;

    public function __construct($host, $login, $password)
    {
        $this->host = $host;
        $this->login = $login;
        $this->password = $password;
    }

    public function getChecklistList(string $subjectType, int $subjectId)
    {
        return $this->getRequest('/BumsCommonApiV01/Checklist/list.api', [
            'SubjectType' => $subjectType,
            'SubjectId' => $subjectId
        ]);
    }

    public function getCommentList(string $subjectType, int $subjectId)
    {
        return $this->getRequest('/BumsCommonApiV01/Comment/list.api', [
            'SubjectType' => $subjectType,
            'SubjectId' => $subjectId
        ]);
    }

    public function getSubTasks(int $superTaskId)
    {
        return $this->getRequest('/BumsCommonApiV01/Task/list.api', [
            'SuperTaskId' => $superTaskId
        ]);
    }

    public function getTaskCard(int $id)
    {
        return $this->getRequest('/BumsCommonApiV01/Task/card.api', [
            'Id' => $id,
            'ExtraFields' => ['ActualWork', 'ActualWorkWithSubTasks']
        ]);
    }

    public function searchQuick(string $qs)
    {
        return $this->getRequest('/BumsCommonApiV01/Search/quick.api', ['qs' => $qs]);
    }

    public function userAuthorize(): MegaplanAuthResponseData
    {
        $url = '/BumsCommonApiV01/User/authorize.api';
        $params = [
            'Login' => $this->login,
            'Password' => md5($this->password)
        ];

        $request = new MegaplanRequest('', '', $this->host, true);
        $response = json_decode($request->get($url, $params));

        $responseObject = new MegaplanAuthResponseData();
        $responseObject->accessId = $response->data->AccessId;
        $responseObject->secretKey = $response->data->AccessId;
        
        $this->accessId = $response->data->AccessId;
        $this->secretKey = $response->data->SecretKey;

        return $responseObject;
    }

    private function getRequest($url, $params)
    {
        if (empty($this->accessId) || empty($this->secretKey)) {
            $this->userAuthorize();
        }

        $request = new MegaplanRequest($this->accessId, $this->secretKey, $this->host, true);
        return json_decode($request->get($url, $params));
    }
}
