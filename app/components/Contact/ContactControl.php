<?php

namespace App\Components\Contact;

use App\Model\Form;
use Nette\Application\UI\Control;

final class ContactControl extends Control
{

	/**
	 * @return Form
	 */
	protected function createComponentForm()
	{
		$form = new Form();
		$form->setMethod('POST');

		$form->addText('name', 'Jméno');

		$form->addSubmit('send', 'Odeslat');

		$form->onSuccess[] = function (Form $form) {
			bdump($form->getValues());
		};

		if ($form->isSuccess()) {
			$form->fireEvents();
		}

		return $form;
	}

}
