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

        $GLOBALS['TYPO3_DB'] = $this->getMock(DatabaseConnection::class);
    }

    public function tearDown()
    {
        unset($this->subject, $this->configurationManager);
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
