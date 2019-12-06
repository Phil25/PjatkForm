<?php

namespace Drupal\PjatkForm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\PjatkForm\PJATKClient\PJATKClient;

use Drupal\user\Entity\User;
use Drupal\Core\Url;

function randomString($length = 64) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function createUser(string $number, PJATKClient &$client) {
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

	$user->addRole('applicant');
	$user->activate();
	$user->save();

	return $user;
}

function loadUser(string $name) {
	$users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name'=> $name]);
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
		$username = $form_state->getValue('number');
		$password = $form_state->getValue('password');
		$user = loadUser($username);

		if (\Drupal::service('user.auth')->authenticate($username, $password)) {

			if (!$user) {
				drupal_set_message(t('Authenticated on a non-existing user. This shouldn\'t have happened.'), 'error');
				return;
			}

			drupal_set_message(t('Logged in as ' . $username));
			user_login_finalize($user);

			return $form_state->setRedirectUrl(Url::fromUri('internal:/projects'));
		}

		$client = new PJATKClient($username, $password);
		if (!$client->isLoggedIn()) {
			drupal_set_message(t('Invalid student number or password.'), 'error');
			return;
		}

		if (!$user) $user = createUser($username, $client);

		drupal_set_message(t('Logged in'));
		user_login_finalize($user);

		return $form_state->setRedirectUrl(Url::fromUri('internal:/projects'));
	}
}
