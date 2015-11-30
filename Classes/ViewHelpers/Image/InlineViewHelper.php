<?php

namespace TYPO3\Fluidmail\ViewHelpers\Image;

use Swift_Image;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\Fluidmail\Mail\TemplateMailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InlineViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'img';

    /**
     * [render description].
     *
     * @param string $src [description]
     *
     * @return [type] [description]
     */
    public function render($src = null)
    {

        /* @var TYPO3\Fluidmail\Mail\TemplateMailMessage */
        $message = $this->templateVariableContainer->get(TemplateMailMessage::VARIABLE_NAME);
        $imageUri = $message->embed(Swift_Image::fromPath(GeneralUtility::getFileAbsFileName($src)));

        $this->tag->addAttribute('src', $imageUri);

        return $this->tag->render();
    }
}
