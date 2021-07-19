<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Data\FormatBase;
use Fanout\Grip\Data\Item;
use Fanout\Grip\Errors\DuplicateFormatNameError;
use PHPUnit\Framework\TestCase;

class TestFormat1 extends FormatBase {
    public string $content;

    public function __construct( string $content ) {
        $this->content = $content;
    }

    function name(): string {
        return 'test-format-1';
    }

    function export(): array {
        return [
            'content' => $this->content,
        ];
    }
}

class TestFormat2 extends FormatBase {
    public string $content;

    public function __construct( string $content ) {
        $this->content = $content;
    }

    function name(): string {
        return 'test-format-2';
    }

    function export(): array {
        return [
            'content' => $this->content,
        ];
    }
}

class ItemTest extends TestCase {

    public FormatBase $format1;
    public FormatBase $format2;

    function setUp(): void {

        $this->format1 = new TestFormat1( 'content1' );
        $this->format2 = new TestFormat2( 'content2' );

    }

    /**
     * @test
     */
    function shouldConstructWithOneFormat() {
        $item = new Item( $this->format1 );
        $this->assertEquals( $this->format1, $item->formats[0] );
    }

    /**
     * @test
     */
    function shouldConstructWithOneFormatAndId() {
        $item = new Item( $this->format1, 'id' );
        $this->assertEquals( $this->format1, $item->formats[0] );
        $this->assertEquals( 'id', $item->id );
    }

    /**
     * @test
     */
    function shouldConstructWithOneFormatAndPrevId() {
        $item = new Item( $this->format1, 'id', 'prev-id' );
        $this->assertEquals( $this->format1, $item->formats[0] );
        $this->assertEquals( 'id', $item->id );
        $this->assertEquals( 'prev-id', $item->prev_id );
    }

    /**
     * @test
     */
    function shouldConstructWithTwoFormats() {
        $item = new Item( [ $this->format1, $this->format2 ] );
        $this->assertEquals( $this->format1, $item->formats[0] );
        $this->assertEquals( $this->format2, $item->formats[1] );
    }

    /**
     * @test
     */
    function shouldExportWithOneFormat() {
        $item = new Item( $this->format1 );

        $export = $item->export();
        $this->assertIsArray( $export );
        $this->assertIsArray( $export[ 'formats' ] );
        $this->assertCount( 1, $export[ 'formats' ] );
        $this->assertArrayNotHasKey( 'id', $export );
        $this->assertArrayNotHasKey( 'prev-id', $export );

        $this->assertEquals([
            'content' => 'content1',
        ], $export[ 'formats' ][ 'test-format-1' ]);
    }

    /**
     * @test
     */
    function shouldExportWithTwoFormats() {
        $item = new Item( [ $this->format1, $this->format2 ] );

        $export = $item->export();
        $this->assertIsArray( $export );
        $this->assertIsArray( $export[ 'formats' ] );
        $this->assertCount( 2, $export[ 'formats' ] );
        $this->assertArrayNotHasKey( 'id', $export );
        $this->assertArrayNotHasKey( 'prev-id', $export );

        $this->assertEquals([
            'content' => 'content1',
        ], $export[ 'formats' ][ 'test-format-1' ]);

        $this->assertEquals([
            'content' => 'content2',
        ], $export[ 'formats' ][ 'test-format-2' ]);
    }

    /**
     * @test
     */
    function shouldFailExportDuplicateFormats() {
        $this->expectException( DuplicateFormatNameError::class );

        $item = new Item( [ $this->format1, $this->format1 ] );
        $item->export();
    }

    /**
     * @test
     */
    function shouldExportWithId() {
        $item = new Item( $this->format1, 'id' );

        $export = $item->export();
        $this->assertIsArray( $export );
        $this->assertEquals( 'id', $export[ 'id' ] );
        $this->assertArrayNotHasKey( 'prev-id', $export );
    }

    /**
     * @test
     */
    function shouldExportWithPrevId() {
        $item = new Item( $this->format1, 'id', 'prev-id' );

        $export = $item->export();
        $this->assertIsArray( $export );
        $this->assertEquals( 'id', $export[ 'id' ] );
        $this->assertEquals( 'prev-id', $export[ 'prev-id' ] );
    }

}
