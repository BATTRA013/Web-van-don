<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Unit/ExampleTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| UNIT TEST MAU
|--------------------------------------------------------------------------
| Bai test don gian nhat de minh hoa assert trong PHPUnit.
*/

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Kiem tra menh de true la true.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}



