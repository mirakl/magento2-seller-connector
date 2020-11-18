<?php
namespace MiraklSeller\Core\Test\Unit\Model;

use MiraklSeller\Core\Model\Offer;
use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group model
 * @group offer
 * @coversDefaultClass \MiraklSeller\Core\Model\Offer
 */
class OfferTest extends TestCase
{
    /**
     * @covers ::getOfferStatuses
     */
    public function testGetStatuses()
    {
        $expectedStatuses = ['NEW', 'PENDING', 'SUCCESS', 'ERROR', 'DELETE'];
        $this->assertSame($expectedStatuses, Offer::getOfferStatuses());
    }

    /**
     * @covers ::getProductStatuses
     */
    public function testGetProductStatuses()
    {
        $expectedStatuses = [
            'NEW', 'PENDING', 'TRANSFORMATION_ERROR', 'WAITING_INTEGRATION',
            'INTEGRATION_COMPLETE', 'INTEGRATION_ERROR', 'INVALID_REPORT_FORMAT',
            'NOT_FOUND_IN_REPORT', 'SUCCESS',
        ];
        $this->assertSame($expectedStatuses, Offer::getProductStatuses());
    }

    /**
     * @covers ::getProductErrorStatuses
     */
    public function testGetProductErrorStatuses()
    {
        $expectedStatuses = ['TRANSFORMATION_ERROR', 'INTEGRATION_ERROR'];
        $this->assertSame($expectedStatuses, Offer::getProductErrorStatuses());
    }

    /**
     * @covers ::getProductImportCompleteStatuses
     */
    public function testGetProductImportCompleteStatuses()
    {
        $expectedStatuses = ['WAITING_INTEGRATION', 'INTEGRATION_COMPLETE'];
        $this->assertSame($expectedStatuses, Offer::getProductImportCompleteStatuses());
    }

    /**
     * @covers ::getProductImportFailedStatuses
     */
    public function testGetProductImportFailedStatuses()
    {
        $expectedStatuses = [
            'TRANSFORMATION_ERROR',
            'INTEGRATION_ERROR',
            'INVALID_REPORT_FORMAT',
            'NOT_FOUND_IN_REPORT',
        ];
        $this->assertSame($expectedStatuses, Offer::getProductImportFailedStatuses());
    }
}