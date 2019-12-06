<?php declare(strict_types = 1);

namespace App\Components\Contact;

use App\Model\Form;
use Nette\Application\UI\Control;

final class ContactControl extends Control
{

	protected function createComponentForm(): Form
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
