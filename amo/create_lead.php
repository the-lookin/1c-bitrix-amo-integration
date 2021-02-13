<?php

use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\CompaniesCollection;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\NullTagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Collections\NotesCollection;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Models\NoteType\ServiceMessageNote;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NullCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use League\OAuth2\Client\Token\AccessTokenInterface;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\SelectCustomFieldValueModel;

class amoCRM
{
  public function add_lead($lead_data) {
    include_once __DIR__ . '/bootstrap.php';

    $name = $lead_data['NAME'];
    $phone = $lead_data['PHONE'];
    $email = $lead_data['EMAIL'];
    $companyName = $lead_data['COMPANY'];
    $description = $lead_data['TEXT'];
    $city = $lead_data['CITY'];
    $leadName = $lead_data['LEAD_NAME'];

    $accessToken = getToken();

    $apiClient->setAccessToken($accessToken)
    ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
    ->onAccessTokenRefresh(
      function (AccessTokenInterface $accessToken, string $baseDomain) {
        saveToken([
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'expires' => $accessToken->getExpires(),
            'baseDomain' => $baseDomain,
        ]);
      }
    );

    $leadsService = $apiClient->leads();

    try {
      $contacts = $apiClient->contacts()->get((new ContactsFilter())->setQuery($phone));
      $contact = $contacts[0];
    } catch(AmoCRMApiException $e) {
      $contact = new ContactModel();
      $contact->setName($name);

      $CustomFieldsValues = new CustomFieldsValuesCollection();
      $emailField = (new MultitextCustomFieldValuesModel())->setFieldCode('EMAIL');
      $emailField->setValues((new MultitextCustomFieldValueCollection())->add((new MultitextCustomFieldValueModel())->setEnum('WORK')->setValue($email)));
      $phoneField = (new MultitextCustomFieldValuesModel())->setFieldCode('PHONE');
      $phoneField->setValues((new MultitextCustomFieldValueCollection())->add((new MultitextCustomFieldValueModel())->setEnum('WORK')->setValue($phone)));

      $CustomFieldsValues->add($emailField);
      $CustomFieldsValues->add($phoneField);

      $contact->setCustomFieldsValues($CustomFieldsValues);

      try {
        $contactModel = $apiClient->contacts()->addOne($contact);
      } catch (AmoCRMApiException $e) {
        printError($e);
        die;
      }
    }

    // Создаем сделку
    $lead = new LeadModel();
    $lead->setName($leadName)->setContacts((new ContactsCollection())->add(($contact)));

    $CustomFieldsValues = new CustomFieldsValuesCollection();
    $cityField = (new SelectCustomFieldValuesModel())->setFieldId(1093513);
    $cityField->setValues((new SelectCustomFieldValueCollection())->add((new SelectCustomFieldValueModel())->setValue($city)));

    $CustomFieldsValues->add($cityField);
    $lead->setCustomFieldsValues($CustomFieldsValues);
    $leadsCollection = new LeadsCollection();
    $leadsCollection->add($lead);

    try {
      $leadsCollection = $leadsService->add($leadsCollection);
      $lead_id = $leadsCollection[0]->id;
      if($companyName != '') {
        //Создадим компанию
        $company = new CompanyModel();
        $company->setName($companyName);

        $companiesCollection = new CompaniesCollection();
        $companiesCollection->add($company);
        try {
            $apiClient->companies()->add($companiesCollection);
        } catch (AmoCRMApiException $e) {
            printError($e); die;
        }

        $links = new LinksCollection();
        $links->add($contact);
        try {
            $apiClient->companies()->link($company, $links);
        } catch (AmoCRMApiException $e) {
            printError($e); die;
        }
      }

      if($description != '') {
        $notesCollection = new NotesCollection();
        $serviceMessageNote = new CommonNote();
        $serviceMessageNote->setEntityId($lead_id)->setText($description);

        $notesCollection->add($serviceMessageNote);

        try {
            $leadNotesService = $apiClient->notes(EntityTypesInterface::LEADS);
            $notesCollection = $leadNotesService->add($notesCollection);
        } catch (AmoCRMApiException $e) {
            printError($e); die;
        }
      }
      return $lead_id;
    } catch (AmoCRMApiException $e) {
        printError($e);
        die;
    }

  }
}
