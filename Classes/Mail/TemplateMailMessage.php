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
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\Fluidmail\Utility\FormatUtility;
use TYPO3\Fluidmail\Exception as FluidmailException;

/**
 * TemplateMailMessage.
 */
class TemplateMailMessage extends \TYPO3\CMS\Core\Mail\MailMessage
{
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
     * [$configurationManager description].
     *
     * @var TYPO3\CMS\Extbase\Configuration\ConfigurationManager
     * @inject
     */
    protected $configurationManager;

    /**
     * [$variables description].
     *
     * @var array
     */
    protected $variables = array();

    /**
     * Create a new Message.
     *
     * Details may be optionally passed into the constructor.
     *
     * @param string $subject
     * @param string $body
     * @param string $contentType
     * @param string $charset
     */
    public function __construct($subject = null, $body = null, $contentType = null, $charset = null)
    {
        parent::__construct($subject, $body, $contentType, $charset);
        $this->setFrom(MailUtility::getSystemFrom());
    }

    /**
     * [getVariables description].
     *
     * @return array [description]
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Renders a template given by name and adds this to the message.
     *
     * @param string $templateName Template name
     * @param array  $variables    [description]
     * @param string $format       html, text or both
     *
     * @return TemplateMailMessage [description]
     */
    public function setBodyFromTemplate($templateName, $variables = array(), $format = self::FORMAT_BOTH)
    {
        switch ($format) {
            case self::FORMAT_BOTH:
            case self::FORMAT_HTML:
            case self::FORMAT_TEXT:
                break;
            default:
                throw new InvalidArgumentException("Invalid argument format '$format'!", 1429125858);
                break;
        }

        $this->variables = array_merge($this->variables, $variables);

        $text = null;
        $html = null;

        if ($format !== self::FORMAT_HTML) {
            try {
                $text = $this->renderTemplate($templateName, $variables, 'txt');
            } catch (FluidmailException $exception) {
                if ($format !== self::FORMAT_BOTH) {
                    throw $exception;
                }
            }
        }

        if ($format !== self::FORMAT_TEXT) {
            $html = $this->renderTemplate($templateName, $variables, 'html');
        }

        if ($format === self::FORMAT_BOTH && $text === null) {
            $text = FormatUtility::html2text($html);
        }

        if ($text !== null) {
            $this->setBody($text, 'text/plain');
            if ($html !== null) {
                $this->addPart($html, 'text/html');
            }
        } else {
            $this->setBody($html, 'text/html');
        }

        return $this;
    }

    /**
     * [renderTemplate description].
     *
     * @param string $templateName Template name
     * @param array  $variables
     * @param string $format       [description]
     *
     * @return string [description]
     */
    protected function renderTemplate($templateName, $variables, $format)
    {
        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $partialRootPaths = isset($extbaseFrameworkConfiguration['view']['partialRootPaths']) ?
            $extbaseFrameworkConfiguration['view']['partialRootPaths']:
            [0 => $extbaseFrameworkConfiguration['view']['partialRootPath']];

        $layoutRootPaths = isset($extbaseFrameworkConfiguration['view']['layoutRootPaths']) ?
            $extbaseFrameworkConfiguration['view']['layoutRootPaths']:
            [0 => $extbaseFrameworkConfiguration['view']['layoutRootPath']];

        $templateRootPaths = isset($extbaseFrameworkConfiguration['view']['templateRootPaths']) ?
            $extbaseFrameworkConfiguration['view']['templateRootPaths']:
            [0 => $extbaseFrameworkConfiguration['view']['templateRootPath']];

        $templatePathAndFilename = null;
        foreach (array_reverse($templateRootPaths) as $templateRootPath) {
            $templatePathAndFilename = GeneralUtility::getFileAbsFileName(
                rtrim($templateRootPath, '/') . '/MailMessage/' . $templateName . '.' . $format
            );
            if (file_exists($templatePathAndFilename)) {
                break;
            } else {
                $templatePathAndFilename = null;
            }
        }
        if ($templatePathAndFilename === null) {
            throw new FluidmailException("Template 'MailMessage/$templateName.$format' not found!", 1429045685);
        }

        if (isset($variables[self::VARIABLE_NAME])) {
            throw new FluidmailException(sprintf("Variable name '%s' is reserved", self::VARIABLE_NAME), 1451333653);
        }

        $variables[self::VARIABLE_NAME] = $this;

        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $view */
        $view = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
        $view->setFormat($format);
        $view->setTemplatePathAndFilename($templatePathAndFilename);
        $view->setLayoutRootPaths($layoutRootPaths);
        $view->setPartialRootPaths($partialRootPaths);
        $view->assignMultiple($variables);

        return $view->render();
    }
}
