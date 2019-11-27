<?php

namespace Drupal\PjatkForm\PJATKClient;

use Drupal\PjatkForm\NTLMSoapClient\NTLMSoapClient;

use Drupal\user\Entity\User;

class PJATKClient {
    private const ENDPOINT = 'https://ws.pjwstk.edu.pl/test/Service.svc?wsdl';

    private $client = NULL;

    public function __construct($username, $password) {
        $this->client = new NTLMSoapClient(PJATKClient::ENDPOINT, array(
            'ntlm_username' => $username,
            'ntlm_password' => $password,
        ));
    }

    public function isLoggedIn() : bool {
        try {
            $this->client->tester();
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }

    public function getStudentName() {
        try {
            $info = $this->client->GetStudentPersonalDataSimple()->GetStudentPersonalDataSimpleResult;
            return array(
                'name' => $info->Imie,
                'surname' => $info->Nazwisko
            );
        } catch(\Exception $e) {
            return array();
        }
    }

    public function getFaculty() : string {
        try {
            return $this->client->GetStudentFaculty()->GetStudentFacultyResult->NazwaKierunekAng;
        } catch(\Exception $e) {
            return 'unknown';
        }
    }

    public function getSemester() : string {
        try {
            return $this->client->GetStudentStudies()->GetStudentStudiesResult->Studia->SemestrStudiow;
        } catch(\Exception $e) {
            return 'unknown';
        }
    }
}
