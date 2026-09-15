<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * อีเมลทดสอบการตั้งค่า SMTP — หัวข้อ/เนื้อหากรอกเองจากหน้าตั้งค่าระบบ (ดู SettingController::testSmtp())
 * ใช้ Mailable จริง (ไม่ใช่ Mail::raw()) เพื่อให้ Mail::fake()/assertSent() ทดสอบได้ — Mail::fake() ไม่รองรับ
 * การส่งแบบ raw array (ดู Illuminate\Support\Testing\Fakes\MailFake::sendMail())
 */
class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(htmlString: nl2br(e($this->bodyText)));
    }
}
