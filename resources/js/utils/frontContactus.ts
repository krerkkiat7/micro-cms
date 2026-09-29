/**
 * ชนิดข้อมูลหน้าติดต่อเราที่หน้าบ้าน — App\Http\Controllers\Front\Contactus\ContactusController
 */

export type ContactusTextPart = 'owner' | 'address' | 'phone' | 'fax' | 'mobile' | 'email';

export interface ContactusTextSetting {
    show: boolean;
    style: { font_size: number; font_family: string; bold: boolean; color: string };
}

export interface FrontContactusData {
    title: string;
    displayType: 'stacked' | 'split_info' | 'half';
    texts: Record<ContactusTextPart, ContactusTextSetting>;
    showSocial: boolean;
    mapImage: { url: string; name: string } | null;
    googleMap: { embedUrl: string; linkUrl: string } | null;
    fontsUrl: string | null;
}

/** ฟิลด์ของแบบฟอร์มที่เปิดรับ => บังคับกรอก (fullname มาก่อนเสมอ) */
export type ContactusFormField = 'fullname' | 'position' | 'company' | 'phone' | 'email' | 'subject' | 'detail';

export interface FrontContactusForm {
    fields: Partial<Record<ContactusFormField, boolean>>;
    siteKey: string;
    token: string;
    action: string;
}
