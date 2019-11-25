<?php

namespace Drupal\PjatkForm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\PjatkForm\NTLMSoapClient\NTLMSoapClient;

class PjatkForm extends FormBase {

	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'pjatk_form';
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state) {
		$form['number'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Student number'),
		];

		$form['password'] = [
			'#type' => 'password',
			'#title' => $this->t('PJATK password'),
			'#description' => $this->t('Your password is not saved.'),
		];

		$form['submit'] = [
			'#type' => 'submit',
			'#value' => $this->t('Login'),
		];

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		$client = new NTLMSoapClient('https://ws.pjwstk.edu.pl/test/Service.svc?wsdl', array(
			'ntlm_username' => $form_state->getValue('number'),
			'ntlm_password' => $form_state->getValue('password'),
		));

		try {
			$result = $client->GetStudentPersonalDataSimple();
			drupal_set_message('Response: ' . print_r($result, true));
		} catch(\Exception $e) {
			drupal_set_message(t('Login failed: ' . $e->getMessage()), 'error');
		}
	}
}
