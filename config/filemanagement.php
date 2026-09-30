<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ตั้งค่าโมดูลจัดการไฟล์ (file_info / folder_info)
    |--------------------------------------------------------------------------
    |
    | ไฟล์ที่อัพโหลดถูกเก็บบน disk นี้ (ต้องไม่ symlink ไป public — ห้ามเรียกผ่าน URL ตรง)
    | โครงสร้างจริงบน disk: {base_path}/{ปี}/{เดือน}/{วัน}/{hash_name}
    | thumbnail ที่ resize แล้วจะถูก cache ไว้ที่ {base_path}/thumbnails/{width}/{hash_name}
    |
    */

    'disk' => 'local',

    'base_path' => 'filemanager',

    'max_size_kb' => 5120, // 5 MB

    'thumbnail_default_width' => 500,

    // ความกว้าง thumbnail ที่หลังบ้านขอได้ (/admin/file/type/thumbnail/size/{size}/...) — ขนาดอื่น = 404
    // กัน request ขนาดใหญ่/ขนาดแปลก ๆ จนกิน memory ของ GD และสร้างไฟล์ thumbnail บน disk ไม่จำกัด
    // (หน้าบ้านใช้ config('front.thumbnail_sizes') แยกต่างหาก) — เพิ่มขนาดใหม่ในหน้าจอหลังบ้านต้องเพิ่มที่นี่ด้วย
    'admin_thumbnail_sizes' => [80, 100, 200, 320, 480, 500, 960],

    /*
    | นามสกุลที่อนุญาตให้อัพโหลด → mime type ที่ต้องตรงกัน (กันไฟล์เปลี่ยนนามสกุลหลอก)
    */
    'allowed' => [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pdf' => 'application/pdf',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'ico' => 'image/vnd.microsoft.icon', // favicon ของตั้งค่าระบบ — mime จริงตรวจได้หลายแบบ ดู StoreFileUploadRequest::ICO_MIME_FALLBACKS
    ],

];
