import * as z from "zod";

export type TCity = {
    id: number;
    name: string;
    code: string;
    country_id: number;
    active?: number;
};

export type TBranchesManager = {
    id: number;
    uuid: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    branch_number?: string;
    branch_name: string;
    id_number: string;
    image: string;
    image_url: string;
    blocked?: boolean;
    location: string;
    latitude?: string;
    longitude?: string;
    status: "active" | "inactive" | string;
    city_id: string;
    country_id: string;
    city?: TCity;
};

export const branchesManagerFormSchema = z.object({
    role_id: z.string({ required_error: "الدور مطلوب" }),
    first_name: z.string().min(2, { message: "الاسم الأول مطلوب" }),
    last_name: z.string().min(2, { message: "الاسم الأخير مطلوب" }),
    branch_name: z.string().min(2, { message: "اسم الفرع مطلوب" }),
    email: z.string().email({ message: "بريد إلكتروني غير صالح" }),
    phone: z.string().min(8, { message: "رقم الهاتف مطلوب" }),
    id_number: z.string().min(5, { message: "رقم الهوية مطلوب" }),
    country_id: z.string({ required_error: "الدولة مطلوبة" }),
    city_id: z.string({ required_error: "المدينة مطلوبة" }),
    location: z.string().min(5, { message: "الموقع مطلوب" }),
    image: z.any().optional(), // Allow file or undefined
});