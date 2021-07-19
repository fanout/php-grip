<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Data\Http\HttpResponseFormat;
use PHPUnit\Framework\TestCase;

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
    function shouldConstructParams() {

        $format = new HttpResponseFormat([
            'code' => 'code',
            'reason' => 'reason',
            'headers' => [
                'foo' => 'bar',
            ],
            'body' => 'body',
            'body_format' => HttpResponseFormat::HTTP_RESPONSE_BODY_FORMAT_PACK,
        ]);
        $this->assertEquals( 'code', $format->code );
        $this->assertEquals( 'reason', $format->reason );
        $this->assertEquals( [ 'foo' => 'bar' ], $format->headers );
        $this->assertEquals( 'body', $format->body );
        $this->assertEquals( HttpResponseFormat::HTTP_RESPONSE_BODY_FORMAT_PACK, $format->body_format );

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
    function shouldExportStringBody() {

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
    function shouldExportPackedBody() {

        $data = pack( 'C*', 0x41, 0x42, 0x43 );
        $format = new HttpResponseFormat([
            'body' => $data,
            'body_format' => HttpResponseFormat::HTTP_RESPONSE_BODY_FORMAT_PACK,
        ]);

        $export = $format->export();

        $this->assertIsArray( $export );
        $this->assertArrayNotHasKey( 'body', $export );
        $this->assertEquals( [ 'body-bin' => 'QUJD' ], $export );

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
            'body' => pack( 'C*', 0x41, 0x42, 0x43 ),
            'body_format' => HttpResponseFormat::HTTP_RESPONSE_BODY_FORMAT_PACK,
        ]);

        $export = $format->export();

        $this->assertEquals([
            'code' => 'code',
            'reason' => 'reason',
            'headers' => [
                'foo' => 'bar',
            ],
            'body-bin' => 'QUJD',
        ], $export);

    }

}
