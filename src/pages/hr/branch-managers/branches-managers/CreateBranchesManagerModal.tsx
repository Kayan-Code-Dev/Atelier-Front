import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogFooter,
    DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { useForm, useWatch } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { ScrollArea } from "@/components/ui/scroll-area";
import { toast } from "sonner";
import { CountriesSelectByModule } from "@/components/custom/CountriesSelectByModule";
import { CitiesSelectByModule } from "@/components/custom/CitiesSelectByModule";
import { useCreateBranchesManagerMutationOptions, useGetBranchesManagersRolesQueryOptions } from "@/api/hr/branch-managers/branches-managers/branches-managers.hooks";
import { useQuery, useMutation } from "@tanstack/react-query";
import { Plus } from "lucide-react";
import { branchesManagerFormSchema } from "@/api/hr/branch-managers/branches-managers/branches-managers.types";

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

const CreateBranchesManagerModal = ({ open, onOpenChange }: Props) => {
    const { data: adminRoles, isLoading: isLoadingRoles } = useQuery(useGetBranchesManagersRolesQueryOptions());
    const { mutate: createManager, isPending } = useMutation(useCreateBranchesManagerMutationOptions());

    const form = useForm<z.infer<typeof branchesManagerFormSchema>>({
        resolver: zodResolver(branchesManagerFormSchema),
        defaultValues: {
            role_id: "", // Assuming manager object has role_id
            first_name: "",
            last_name: "",
            branch_name: "",
            email: "",
            phone: "",
            id_number: "",
            country_id: "",
            location: "",
            city_id: "", // Assuming city is the ID
            image: undefined, // Don't pre-fill file input
        }
    });

    // Watch the selected country to trigger the cities query
    const selectedCountryId = useWatch({
        control: form.control,
        name: "country_id",
    });

    const onSubmit = (values: z.infer<typeof branchesManagerFormSchema>) => {
        const formData = new FormData();
        // Append all form fields to FormData
        Object.keys(values).forEach((key) => {
            const formKey = key as keyof typeof values;
            if (values[formKey]) {
                formData.append(formKey, values[formKey] as string | Blob);
            }
        });

        createManager(formData, {
            onSuccess: () => {
                toast.success("تم إنشاء المدير بنجاح");
                form.reset();
                onOpenChange(false);
            },
            onError: (err) => {
                toast.error("حدث خطأ أثناء إنشاء المدير", {
                    description: err.message
                });
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader className="grid flex-1 gap-2">
                    <div className="mx-auto h-15 w-15 border-2 border-main-gold rounded-full flex items-center justify-center" >
                        <Plus className="text-main-gold size-8" />
                    </div>
                    <DialogTitle className="text-2xl font-semibold text-center">
                        إضافة مدير الفروع
                    </DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)} dir="rtl">
                        <ScrollArea className="h-[60vh] p-4">
                            <div className="space-y-4" dir="rtl">
                                {/* Role Select */}
                                <FormField
                                    control={form.control}
                                    name="role_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>الدور</FormLabel>
                                            <Select
                                                onValueChange={field.onChange}
                                                value={field.value}
                                                disabled={isLoadingRoles}
                                            >
                                                <FormControl>
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="اختر دور المشرف..." />
                                                    </SelectTrigger>
                                                </FormControl>
                                                <SelectContent>
                                                    {adminRoles?.map((role) => (
                                                        <SelectItem
                                                            key={role.id}
                                                            value={role.id.toString()}
                                                        >
                                                            {role.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                {/* First/Last Name */}
                                <div className="flex gap-4">
                                    <FormField
                                        control={form.control}
                                        name="first_name"
                                        render={({ field }) => (
                                            <FormItem className="flex-1">
                                                <FormLabel>الاسم الأول</FormLabel>
                                                <FormControl>
                                                    <Input {...field} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                    <FormField
                                        control={form.control}
                                        name="last_name"
                                        render={({ field }) => (
                                            <FormItem className="flex-1">
                                                <FormLabel>الاسم الأخير</FormLabel>
                                                <FormControl>
                                                    <Input {...field} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </div>

                                {/* Email/Phone */}
                                <div className="flex gap-4">
                                    <FormField
                                        control={form.control}
                                        name="email"
                                        render={({ field }) => (
                                            <FormItem className="flex-1">
                                                <FormLabel>البريد الإلكتروني</FormLabel>
                                                <FormControl>
                                                    <Input type="email" {...field} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                    <FormField
                                        control={form.control}
                                        name="phone"
                                        render={({ field }) => (
                                            <FormItem className="flex-1">
                                                <FormLabel>رقم الهاتف</FormLabel>
                                                <FormControl>
                                                    <Input type="tel" {...field} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </div>

                                {/* ID Number */}
                                <FormField
                                    control={form.control}
                                    name="id_number"
                                    render={({ field }) => (
                                        <FormItem className="flex-1">
                                            <FormLabel>رقم الهوية</FormLabel>
                                            <FormControl>
                                                <Input {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <div className="flex gap-4">
                                    <FormField
                                        control={form.control}
                                        name="location"
                                        render={({ field }) => (
                                            <FormItem className="flex-1">
                                                <FormLabel>الموقع</FormLabel>
                                                <FormControl>
                                                    <Input {...field} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                    <FormField
                                        control={form.control}
                                        name="branch_name"
                                        render={({ field }) => (
                                            <FormItem className="flex-1">
                                                <FormLabel>اسم الفرع</FormLabel>
                                                <FormControl>
                                                    <Input {...field} />
                                                </FormControl>
                                                <FormMessage />
                                            </FormItem>
                                        )}
                                    />
                                </div>

                                {/* Country Select (Reusable) */}
                                <FormField
                                    control={form.control}
                                    name="country_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>الدولة</FormLabel>
                                            <FormControl>
                                                <CountriesSelectByModule
                                                    value={field.value}
                                                    onChange={(value) => {
                                                        field.onChange(value);
                                                        // Reset city when country changes
                                                        form.setValue("city_id", "");
                                                    }}
                                                    switchKey="branches-managers"
                                                />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                {/* City Select (Dependent) */}
                                <FormField
                                    control={form.control}
                                    name="city_id"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>المدينة</FormLabel>
                                            <FormControl>
                                                <CitiesSelectByModule
                                                    onChange={field.onChange}
                                                    value={field.value}
                                                    switchKey="branches-managers"
                                                    selectedCountryId={Number(selectedCountryId)}
                                                />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />

                                {/* Image Upload */}
                                <FormField
                                    control={form.control}
                                    name="image"
                                    render={({ field }) => (
                                        <FormItem>
                                            <FormLabel>تغيير الصورة الشخصية</FormLabel>
                                            <FormControl>
                                                <Input
                                                    type="file"
                                                    accept="image/*"
                                                    onChange={(e) =>
                                                        field.onChange(e.target.files?.[0] ?? undefined)
                                                    }
                                                />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                            </div>
                        </ScrollArea>
                        <DialogFooter className="gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                            >
                                إلغاء
                            </Button>
                            <Button type="submit" disabled={isPending} className="bg-main-gold hover:bg-main-gold/90">
                                {isPending ? "جاري الحفظ..." : "حفظ التغييرات"}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}

export default CreateBranchesManagerModal