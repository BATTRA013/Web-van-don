<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/TestCase.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| BASE TEST CASE
|--------------------------------------------------------------------------
| Lop nen cho tat ca bai test cua du an.
| Moi feature/unit test se ke thua lop nay de dung helper testing cua Laravel.
*/

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Dat helper chung cho test tai day neu can.
}



