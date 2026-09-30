<?php

// หลังบ้านไม่เปิดให้สมัครสมาชิกเอง — ผู้ใช้หลังบ้านสร้างได้จากหน้าจัดการผู้ใช้งานเท่านั้น (route ของ Breeze ถูกลบออกแล้ว)
test('back-office registration is not available', function () {
    $this->get('/admin/register')->assertNotFound();

    $this->post('/admin/register', [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])->assertNotFound();

    $this->assertGuest();
    $this->assertDatabaseMissing('sys_user', ['email' => 'test@example.com']);
});
