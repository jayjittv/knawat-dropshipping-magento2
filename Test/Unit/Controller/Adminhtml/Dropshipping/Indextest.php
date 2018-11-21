<?php
namespace Knawat\Dropshipping\Test\Unit\Controller\Controller\Adminhtml\Dropshipping;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class IndexTest
 * @package Knawat\Dropshipping\Test\Unit\Controller\Controller\Adminhtml\Dropshipping
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Magento\Backend\Model\View\Result\Page|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultPage;

    /**
     * @var \Magento\Framework\View\Result\PageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pageConfig;

    /**
     * @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pageTitle;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;


    /**
     *setup environment for Index controller
     *
     */
    protected function setUp()
    {
        $this->resultPage = $this->getMockBuilder(\Magento\Backend\Model\View\Result\Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['setActiveMenu', 'getConfig','getTitle'])
            ->getMock();

        $this->resultPageFactory = $this->getMockBuilder(\Magento\Framework\View\Result\PageFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->pageConfig = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTitle = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultPage->expects($this->any())->method('getConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->pageTitle);
        $this->pageTitle->expects($this->any())->method('prepend')->willReturnSelf();
        $this->objectManager = new ObjectManager($this);

    }

    /**
     *test execution
     * @return bool
     */
    public function testExecute()
    {
        $model = $this->objectManager->getObject('Knawat\Dropshipping\Controller\Adminhtml\Dropshipping\Index',
            ['resultPageFactory' => $this->resultPageFactory]
        );
        $this->resultPageFactory->expects($this->once())->method('create')->willReturn($this->resultPage);
        $this->assertSame($this->resultPage, $model->execute());
    }
}