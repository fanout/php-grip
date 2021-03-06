<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Errors\CStringEncodeInvalidInputError;
use Fanout\Grip\Utils\StringUtil;
use PHPUnit\Framework\TestCase;

class StringUtilTest extends TestCase {

    /**
     * @test
     */
    function shouldEncodeCStringSimple() {

        $input = 'simple';
        $this->assertEquals( 'simple', StringUtil::encode_cstring( $input ) );

    }

    /**
     * @test
     */
    function shouldEncodeCStringWithBackslashes() {

        // The \\ in the input string resolves to a single backslash
        $input = "string\\with\\backslashes";
        // The \\\\ in the test string resolves to a double backslash
        $this->assertEquals( "string\\\\with\\\\backslashes", StringUtil::encode_cstring( $input ) );

    }

    /**
     * @test
     */
    function shouldEncodeCStringWithCR() {

        $input = "multi\rline";
        $this->assertEquals( "multi\\rline", StringUtil::encode_cstring( $input ) );

    }

    /**
     * @test
     */
    function shouldEncodeCStringWithLF() {

        $input = "multi\nline";
        $this->assertEquals( "multi\\nline", StringUtil::encode_cstring( $input ) );

    }

    /**
     * @test
     */
    function shouldEncodeCStringWithTab() {

        $input = "foo\tbar";
        $this->assertEquals( "foo\\tbar", StringUtil::encode_cstring( $input ) );

    }

    /**
     * @test
     */
    function shouldFailEncodeCStringWithUnprintable() {

        $this->expectException(CStringEncodeInvalidInputError::class);

        $input = "foo" . chr(7) . "bar";
        StringUtil::encode_cstring( $input );

    }

    /**
     * @test
     */
    function shouldEscapeQuotesSimpleString() {
        $input = 'simple string';
        $this->assertEquals( 'simple string', StringUtil::escape_quotes( $input ) );
    }

    /**
     * @test
     */
    function shouldEscapeQuotesSingleQuotedString() {
        $input = "'single-quoted string'";
        $this->assertEquals( "'single-quoted string'", StringUtil::escape_quotes( $input ) );
    }

    /**
     * @test
     */
    function shouldEscapeQuotesDoubleQuotedString() {
        $input = "\"double-quoted string\"";
        $this->assertEquals( "\\\"double-quoted string\\\"", StringUtil::escape_quotes( $input ) );
    }

    /**
     * @test
     */
    function shouldStringStartsWith() {
        $test_subject = 'abcdefg';

        $this->assertTrue( StringUtil::string_starts_with( $test_subject, '' ) );
        $this->assertTrue( StringUtil::string_starts_with( $test_subject, 'abcd' ) );
        $this->assertTrue( StringUtil::string_starts_with( $test_subject, 'ab' ) );
        $this->assertTrue( StringUtil::string_starts_with( $test_subject, 'abcdefg' ) );
        $this->assertFalse( StringUtil::string_starts_with( $test_subject, 'abcdf' ) );
    }

    /**
     * @test
     */
    function shouldStringEndsWith() {
        $test_subject = 'abcdefg';

        $this->assertTrue( StringUtil::string_ends_with( $test_subject, '' ) );
        $this->assertTrue( StringUtil::string_ends_with( $test_subject, 'efg' ) );
        $this->assertTrue( StringUtil::string_ends_with( $test_subject, 'fg' ) );
        $this->assertTrue( StringUtil::string_ends_with( $test_subject, 'abcdefg' ) );
        $this->assertFalse( StringUtil::string_ends_with( $test_subject, 'cefg' ) );
    }


}
