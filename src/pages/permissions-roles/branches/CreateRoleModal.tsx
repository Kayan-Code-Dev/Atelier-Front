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
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { ScrollArea } from "@/components/ui/scroll-area";
import { toast } from "sonner";
import { Plus } from "lucide-react";
import { useCreateBranchesRoleMutationOptions } from "@/api/permissions-roles/branches/branches.hooks";
import { useMutation } from "@tanstack/react-query";

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

const schema = z.object({
    guard_name: z.string({ required_error: "الدور مطلوب" }),
    name: z.string().min(2, { message: "الاسم مطلوب" }),
});

const CreateRoleModal = ({ open, onOpenChange }: Props) => {
    const { mutate: createRole, isPending } = useMutation(useCreateBranchesRoleMutationOptions());

    const form = useForm<z.infer<typeof schema>>({
        resolver: zodResolver(schema),
        defaultValues: {
            guard_name: "employee-api",
            name: ""
        }
    });

    const onSubmit = (formData: z.infer<typeof schema>) => {
        createRole(formData, {
            onSuccess: () => {
                toast.success("تم إنشاء الصلاحية بنجاح");
                form.reset();
                onOpenChange(false);
            },
            onError: (err) => {
                toast.error("حدث خطأ أثناء إنشاء الصلاحية", {
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
                        إنشاء دور
                    </DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)} dir="rtl">
                        <ScrollArea className="h-[60vh] p-4">
                            <div className="space-y-4" dir="rtl">
                                <FormField
                                    control={form.control}
                                    name="name"
                                    render={({ field }) => (
                                        <FormItem className="flex-1">
                                            <FormLabel>الاسم</FormLabel>
                                            <FormControl>
                                                <Input {...field} />
                                            </FormControl>
                                            <FormMessage />
                                        </FormItem>
                                    )}
                                />
                                <FormField
                                    control={form.control}
                                    name="guard_name"
                                    render={({ field }) => (
                                        <FormItem className="flex-1 hidden">
                                            <FormLabel>الصلاحية</FormLabel>
                                            <FormControl>
                                                <Input {...field} className="hidden" />
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

export default CreateRoleModal