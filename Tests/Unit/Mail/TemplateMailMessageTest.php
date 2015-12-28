<?php

namespace TYPO3\Fluidmail\Tests\Unit\Command;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\Fluidmail\Mail\TemplateMailMessage;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Database\DatabaseConnection;

class TemplateMailMessageTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Fluidmail\Mail\TemplateMailMessage
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
     */
    protected $configurationManager;

    protected $frameworkConfiguration = [
        'view' => [
            'layoutRootPath' => 'EXT:fluidmail/Tests/Fixtures/Layouts',
            'partialRootPath' => 'EXT:fluidmail/Tests/Fixtures/Partials',
            'templateRootPath' => 'EXT:fluidmail/Tests/Fixtures/Templates',
        ],
    ];

    public function setUp()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMock(DatabaseConnection::class);

        $this->subject = new TemplateMailMessage();

        $this->configurationManager = $this->getMock(ConfigurationManager::class, array('getConfiguration'), array(), '', false);

        $this->configurationManager
            ->expects($this->any())
            ->method('getConfiguration')
            ->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)
            ->will($this->returnValue($this->frameworkConfiguration));

        $this->inject($this->subject, 'configurationManager', $this->configurationManager);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->inject($this->subject, 'objectManager', $objectManager);
    }

    public function tearDown()
    {
        unset($this->subject, $this->configurationManager, $GLOBALS['TYPO3_DB']);
    }

    /**
     * @test
     */
    public function renderTextExampleAndCheckContentType()
    {
        $this->subject->setBodyFromTemplate('Example', array(), TemplateMailMessage::FORMAT_TEXT);
        $this->assertSame('text/plain', $this->subject->getContentType());
        $this->assertSame('Text Example', $this->subject->getBody());
    }

    /**
     * @test
     */
    public function renderHtmlExampleAndCheckContentType()
    {
        $this->subject->setBodyFromTemplate('Example', array(), TemplateMailMessage::FORMAT_HTML);
        $this->assertSame('text/html', $this->subject->getContentType());
        $this->assertRegExp('#<h1>HTML Example</h1>#', $this->subject->getBody());
    }

    /**
     * @test
     */
    public function renderTextAndHtmlExampleAndCheckContentType()
    {
        $this->subject->setBodyFromTemplate('Example', array(), TemplateMailMessage::FORMAT_BOTH);
        $this->assertSame('multipart/alternative', $this->subject->getContentType());
        $this->assertSame('Text Example', $this->subject->getBody());
        $children = $this->subject->getChildren();
        $this->assertSame(1, count($children));
        $this->assertRegExp('#<h1>HTML Example</h1>#', $children[0]->getBody());
    }

    /**
     * @test
     */
    public function renderHtmlWithTextFallback()
    {
        $this->subject->setBodyFromTemplate('TYPO3', array(), TemplateMailMessage::FORMAT_BOTH);
        $this->assertSame('multipart/alternative', $this->subject->getContentType());
        $this->assertSame('TYPO3', $this->subject->getBody());
        $children = $this->subject->getChildren();
        $this->assertSame(1, count($children));
        $this->assertRegExp('#<h1>TYPO3</h1>#', $children[0]->getBody());
    }

    /**
     * @test
     */
    public function setVariable()
    {
        $title = 'TYPO3';
        $this->subject->setBodyFromTemplate('Simple', ['title' => $title]);
        $this->assertRegExp('/'.$title.'/', $this->subject->getBody());
    }

    /**
     * @test
     * @expectedException TYPO3\Fluidmail\Exception
     */
    public function setReservedVariable()
    {
         $this->subject->setBodyFromTemplate('Simple', ['message' => 'Variable already exists!']);
    }

    /**
     * @test
     */
    public function setSubject()
    {
        $this->assertSame(null, $this->subject->getSubject());
        $this->subject->setBodyFromTemplate('Subject');
        $this->assertSame('TYPO3', $this->subject->getSubject());
    }

    /**
     * @test
     */
    public function setImage()
    {
        $this->subject->setBodyFromTemplate('Image');
        $this->assertRegExp('#<img src=[^"]*"cid:[a-z0-9]+@swift.generated" />#im', $this->subject->toString());
    }
}
