<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Utils\ArrayUtil;
use PHPUnit\Framework\TestCase;

class ArrayUtilTest extends TestCase {

    /**
     * @test
     */
    function shouldTestIsNumericArray() {

        $this->assertTrue( ArrayUtil::is_numeric_array( [ 'a', 'b', 'c' ] ) );
        $this->assertFalse( ArrayUtil::is_numeric_array( [ 'a' => 'x', 'b' => 'y', 'c' => 'z' ] ) );
        $this->assertTrue( ArrayUtil::is_numeric_array( [ [ 'a' => 'x', 'b' => 'y', 'c' => 'z' ] ] ) );

    }

}
