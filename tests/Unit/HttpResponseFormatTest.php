<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Data\Http\HttpResponseFormat;
use Fanout\Grip\Tests\Utils\TestStreamData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class HttpResponseFormatTest extends TestCase {

    /**
     * @test
     */
    function shouldConstructNoParams() {

        $format = new HttpResponseFormat();
        $this->assertNull( $format->code );
        $this->assertNull( $format->reason );
        $this->assertEmpty( $format->headers );
        $this->assertEmpty( $format->body );

    }

    /**
     * @test
     */
    function shouldConstructString() {

        $format = new HttpResponseFormat([
            'code' => 'code',
            'reason' => 'reason',
            'headers' => [
                'foo' => 'bar',
            ],
            'body' => 'body',
        ]);
        $this->assertEquals( 'code', $format->code );
        $this->assertEquals( 'reason', $format->reason );
        $this->assertEquals( [ 'foo' => 'bar' ], $format->headers );
        $this->assertEquals( 'body', $format->body );

    }

    /**
     * @test
     */
    function shouldConstructStream() {

        $format = new HttpResponseFormat([
            'code' => 'code',
            'reason' => 'reason',
            'headers' => [
                'foo' => 'bar',
            ],
            'body' => TestStreamData::$sample_stream,
        ]);
        $this->assertEquals( 'code', $format->code );
        $this->assertEquals( 'reason', $format->reason );
        $this->assertEquals( [ 'foo' => 'bar' ], $format->headers );
        $this->assertEquals( TestStreamData::$sample_stream, $format->body );

    }

    /**
     * @test
     */
    function shouldConstructConverted() {
        $format = new HttpResponseFormat([
            'code' => 'code',
            'reason' => 'reason',
            'headers' => [
                'foo' => 'bar',
            ],
            'body' => TestStreamData::get_sample_iterator(),
        ]);
        $this->assertEquals( 'code', $format->code );
        $this->assertEquals( 'reason', $format->reason );
        $this->assertEquals( [ 'foo' => 'bar' ], $format->headers );

        $this->assertInstanceOf( StreamInterface::class, $format->body );
        $this->assertEquals( TestStreamData::$sample_iterator_values_to_string, $format->body->getContents() );
    }

    /**
     * @test
     */
    function shouldReportName() {

        $format = new HttpResponseFormat();
        $this->assertEquals( 'http-response', $format->name() );

    }

    /**
     * @test
     */
    function shouldExportString() {

        $format = new HttpResponseFormat([
            'body' => 'body',
        ]);

        $export = $format->export();

        $this->assertIsArray( $export );
        $this->assertEquals( [ 'body' => 'body' ], $export );
        $this->assertArrayNotHasKey( 'body-bin', $export );

    }

    /**
     * @test
     */
    function shouldExportStream() {

        $format = new HttpResponseFormat([
            'body' => TestStreamData::$sample_stream,
        ]);

        $export = $format->export();

        $this->assertIsArray( $export );
        $this->assertArrayNotHasKey( 'body', $export );
        $this->assertEquals( [ 'body-bin' => TestStreamData::$sample_stream_value_to_base64 ], $export );

    }

    /**
     * @test
     */
    function shouldExportConverted() {

        $format = new HttpResponseFormat([
            'body' => TestStreamData::get_sample_iterator(),
        ]);

        $export = $format->export();

        $this->assertIsArray( $export );
        $this->assertArrayNotHasKey( 'body', $export );
        $this->assertEquals( [ 'body-bin' => TestStreamData::$sample_iterator_values_to_base64 ], $export );

    }

    /**
     * @test
     */
    function shouldExportFields() {

        $format = new HttpResponseFormat([
            'code' => 'code',
            'reason' => 'reason',
            'headers' => [
                'foo' => 'bar',
            ],
        ]);

        $export = $format->export();

        $this->assertEquals([
            'code' => 'code',
            'reason' => 'reason',
            'headers' => [
                'foo' => 'bar',
            ],
        ], $export);

    }

}
