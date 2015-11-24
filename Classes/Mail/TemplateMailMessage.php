<?php
namespace TYPO3\Fluidmail\Mail;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 R3 H6 <r3h6@outlook.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use InvalidArgumentException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\Fluidmail\Exception as FluidmailException;

/**
 * TemplateMailMessage
 */
class TemplateMailMessage extends \TYPO3\CMS\Core\Mail\MailMessage{

	const FORMAT_HTML = 'html';
	const FORMAT_TEXT = 'text';
	const FORMAT_BOTH = 'both';
	const VARIABLE_NAME = 'message';

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * [$configurationManager description]
	 * @var TYPO3\CMS\Extbase\Configuration\ConfigurationManager
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * [$variables description]
	 * @var array
	 */
	protected $variables = array();

	/**
	 * [getVariables description]
	 * @return array [description]
	 */
	public function getVariables (){
		return $this->variables;
	}

	/**
	 * [setBodyFromTemplate description]
	 * @param string $templateName Template name
	 * @param array  $variables    [description]
	 * @param string $format html or text
	 * @return  TemplateMailMessage [description]
	 */
	public function setBodyFromTemplate ($templateName, $variables = array(), $format = TemplateMailMessage::FORMAT_BOTH){

		switch ($format) {
			case TemplateMailMessage::FORMAT_BOTH:
			case TemplateMailMessage::FORMAT_HTML:
			case TemplateMailMessage::FORMAT_TEXT:
				break;
			default:
				throw new InvalidArgumentException("Invalid argument format '$format'!", 1429125858);
				break;
		}

		$this->variables = array_merge($this->variables, $variables);

		$text = NULL;
		$html = NULL;

		if ($format !== TemplateMailMessage::FORMAT_HTML){
			try {
				$text = $this->renderTemplate($templateName, $variables, 'txt');
			}catch (FluidmailException $exception){
				if ($format !== TemplateMailMessage::FORMAT_BOTH){
					throw $exception;
				}
			}
		}

		if ($format !== TemplateMailMessage::FORMAT_TEXT){
			$html = $this->renderTemplate($templateName, $variables, 'html');
		}

		if ($text === NULL && $format === TemplateMailMessage::FORMAT_BOTH){
			// $text = Converter::html2text($html);
		}

		if ($text !== NULL){
			$this->setBody($text, 'text/plain');
			if ($html !== NULL){
				$this->addPart($html, 'text/html');
			}
		} else {
			$this->setBody($html, 'text/html');
		}

		return $this;
	}

	/**
	 * [renderTemplate description]
	 * @param  string $templateName Template name
	 * @param  array $variables
	 * @param  string $format       [description]
	 * @return string               [description]
	 */
	protected function renderTemplate ($templateName, $variables, $format){

//CONFIGURATION_TYPE_FRAMEWORK
		$extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$templateRootPath = GeneralUtility::getFileAbsFileName($extbaseFrameworkConfiguration['view']['templateRootPath']);
		$templatePathAndFilename = rtrim($templateRootPath, '/') . '/MailMessage/' . $templateName . '.' . $format;

		if (!file_exists($templatePathAndFilename)){
			throw new FluidmailException("Template '$templatePathAndFilename' not found.", 1429045685);
		}


		$layoutRootPath = GeneralUtility::getFileAbsFileName($extbaseFrameworkConfiguration['view']['layoutRootPath']);
		$partialRootPath = GeneralUtility::getFileAbsFileName($extbaseFrameworkConfiguration['view']['partialRootPath']);
		// \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($extbaseFrameworkConfiguration);
		// \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($templateRootPath);

		/** @var \TYPO3\CMS\Fluid\View\StandaloneView $view */
		$view = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$view->setFormat($format);
		$view->setTemplatePathAndFilename($templatePathAndFilename);
		$view->setLayoutRootPath($layoutRootPath);
		$view->setPartialRootPath($partialRootPath);
		$view->assign(self::VARIABLE_NAME, $this);
		$view->assignMultiple($variables);
		return $view->render();
	}
}