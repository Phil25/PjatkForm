<?php

namespace Drupal\PjatkForm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\PjatkForm\PJATKClient\PJATKClient;

use Drupal\user\Entity\User;

function randomString($length = 64) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function createUser($number, PJATKClient &$client) {
	$user = User::create();

	$user->setPassword(randomString());
	$user->enforceIsNew();
	$user->setEmail($number . '@pjwstk.edu.pl');
	$user->setUsername($number);

	$name = $client->getStudentName();
	$user->set('field_name', $name['name']);
	$user->set('field_surname', $name['surname']);

	$user->set('field_faculty', $client->getFaculty());
	$user->set('field_semester', $client->getSemester());

	$user->activate();
	$user->save();

	return $user;
}

function loadUser($number) {
	$users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name'=> $number]);
	return reset($users);
}

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
		$number = $form_state->getValue('number');
		$client = new PJATKClient($number, $form_state->getValue('password'));

		if(!$client->isLoggedIn()) {
			drupal_set_message(t('Invalid student number or password.'), 'error');
			return;
		}

		$user = loadUser($number);
		if(!$user) $user = createUser($number, $client);

		drupal_set_message(t('Logged in'));
		user_login_finalize($user);
	}
}
