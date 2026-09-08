export interface User {
    id: number;
    titlename: string | null;
    firstname: string;
    lastname: string;
    name: string; // accessor: คำนำหน้า + ชื่อ + นามสกุล
    email: string;
    email_verified_at?: string;
    mobile?: string | null;
    phone?: string | null;
    line?: string | null;
    facebook?: string | null;
    permissions?: string[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
