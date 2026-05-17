<?php

/*
|--------------------------------------------------------------------------
| LUONG_XU_LY_FILE
|--------------------------------------------------------------------------
| File: tests/Feature/ExampleTest.php
| - Buoc 1: Arrange du lieu test va mock dependency can thiet.
| - Buoc 2: Act thuc thi request/ham can kiem thu.
| - Buoc 3: Assert ket qua theo dung ky vong nghiep vu.
*/

/*
|--------------------------------------------------------------------------
| FEATURE TEST MAU
|--------------------------------------------------------------------------
| Bai test mau de xac nhan route '/' hoat dong binh thuong.
*/

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Kiem tra trang chu tra ve HTTP 200.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Gui request GET den route '/'.
        $response = $this->get('/');

        // Ky vong server tra ve thanh cong.
        $response->assertStatus(200);
    }
}



