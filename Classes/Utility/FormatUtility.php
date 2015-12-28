<?php

namespace TYPO3\Fluidmail\Utility;

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

/**
 * FormatUtility.
 */
class FormatUtility
{
    public static function html2text($html)
    {
        if (class_exists('Html2Text\\Html2Text', true)) {
            $html = new \Html2Text\Html2Text($html);
            return $html->getText();
        } else {
            return static::makePlain($html);
        }
    }

    /**
     * Function makePlain() removes html tags and add linebreaks
     *      Easy generate a plain email bodytext from a html bodytext
     * @copyright (c) 2014 Alex Kellner <alexander.kellner@in2code.de>, in2code.de
     * @param string $content HTML Mail bodytext
     * @return string $content
     */
    protected static function makePlain($content)
    {
        $tags2LineBreaks = array (
            '</p>',
            '</tr>',
            '<ul>',
            '</li>',
            '</h1>',
            '</h2>',
            '</h3>',
            '</h4>',
            '</h5>',
            '</h6>',
            '</div>',
            '</legend>',
            '</fieldset>',
            '</dd>',
            '</dt>'
        );

        // 1. remove complete head element
        $content = preg_replace('/<head>(.*?)<\/head>/is', '', $content);
        // 2. remove linebreaks, tabs
        $content = trim(str_replace(array("\n", "\r", "\t"), '', $content));
        // 3. add linebreaks on some parts (</p> => </p><br />)
        $content = str_replace($tags2LineBreaks, '</p><br />', $content);
        // 4. insert space for table cells
        $content = str_replace(array('</td>', '</th>'), '</td> ', $content);
        // 5. replace links <a href="xyz">LINK</a> -> LINK [xyz]
        $content = preg_replace('/<a\s+(?:[^>]*?\s+)?href=\"([^\"]*)\".*>(.*)<\/a>/u', '$2 [$1]', $content);
        // 6. remove all tags (<b>bla</b><br /> => bla<br />)
        $content = strip_tags($content, '<br><address>');
        // 7. <br /> to \n
        $array = array(
            '<br >',
            '<br>',
            '<br/>',
            '<br />'
        );
        $content = str_replace($array, "\n", $content);

        return trim($content);
    }
}
