<?php
namespace TYPO3\Fluidmail\ViewHelpers\Header;

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluidmail\Mail\TemplateMailMessage;

class SubjectViewHelper extends AbstractViewHelper {
	/**
	 * [render description]
	 * @param  string $subject [description]
	 * @return void
	 */
	public function render ($subject = null){
		if ($subject === null){
			$subject = $this->renderChildren();
		}
		/** @var TYPO3\Fluidmail\Mail\TemplateMailMessage */
		$message = $this->templateVariableContainer->get(TemplateMailMessage::VARIABLE_NAME);
		$message->setSubject($subject);
	}
}