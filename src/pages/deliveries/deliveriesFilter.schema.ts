import * as z from "zod";

/** Zod schema for deliveries list filter form */
export const deliveriesFilterSchema = z.object({
  order_id: z.string().optional(),
  client_id: z.string().optional(),
  employee_id: z.string().optional(),
  cloth_name: z.string().optional(),
  cloth_code: z.string().optional(),
  visit_date_from: z.string().optional(),
  visit_date_to: z.string().optional(),
  delivery_date_from: z.string().optional(),
  delivery_date_to: z.string().optional(),
  return_date_from: z.string().optional(),
  return_date_to: z.string().optional(),
});

export type DeliveriesFilterFormValues = z.infer<typeof deliveriesFilterSchema>;
