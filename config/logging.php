<?php

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        // error ที่ถูก report (5xx ฯลฯ) พร้อมรหัสอ้างอิง (App\Support\ErrorReference) — เขียนจาก bootstrap/app.php เสมอ ไม่ขึ้นกับ LOG_STACK
        // แยกฝั่งด้วย ErrorReference::channel() (error_front / error_admin = stack) แล้วเขียน 2 รูปแบบแยกไฟล์ หมุนรายวัน เก็บ LOG_ERROR_DAYS วัน (default 90):
        //   text-error-{front,admin}-YYYY-MM-DD.log  ข้อความอ่านง่าย (เปิดดู/grep เอง)
        //   json-error-{front,admin}-YYYY-MM-DD.log  1 บรรทัด = 1 รายการ JSON — หน้า "ตรวจสอบ Error" ในหลังบ้านอ่านไฟล์นี้ (App\Support\Report\ErrorLogReader)
        // front = หน้าบ้าน + path สาธารณะอื่น (/file, /sitemap.xml), admin = /admin + คำสั่ง artisan/queue ที่ไม่ได้มาจากหน้าเว็บ
        'error_front' => [
            'driver' => 'stack',
            'channels' => ['error_front_text', 'error_front_json'],
            'ignore_exceptions' => false,
        ],

        'error_admin' => [
            'driver' => 'stack',
            'channels' => ['error_admin_text', 'error_admin_json'],
            'ignore_exceptions' => false,
        ],

        'error_front_text' => [
            'driver' => 'daily',
            'path' => storage_path('logs/text-error-front.log'),
            'level' => 'error',
            'days' => (int) env('LOG_ERROR_DAYS', 90),
            'replace_placeholders' => true,
        ],

        'error_front_json' => [
            'driver' => 'daily',
            'path' => storage_path('logs/json-error-front.log'),
            'level' => 'error',
            'days' => (int) env('LOG_ERROR_DAYS', 90),
            'formatter' => JsonFormatter::class,
        ],

        'error_admin_text' => [
            'driver' => 'daily',
            'path' => storage_path('logs/text-error-admin.log'),
            'level' => 'error',
            'days' => (int) env('LOG_ERROR_DAYS', 90),
            'replace_placeholders' => true,
        ],

        'error_admin_json' => [
            'driver' => 'daily',
            'path' => storage_path('logs/json-error-admin.log'),
            'level' => 'error',
            'days' => (int) env('LOG_ERROR_DAYS', 90),
            'formatter' => JsonFormatter::class,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', env('APP_NAME', 'Laravel')),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
