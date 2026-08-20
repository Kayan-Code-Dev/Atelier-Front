export type TInvoiceTemplate = "premium" | "classic" | "compact";

export type TInvoiceRuleKey =
  | "rental_customer"
  | "rental_workshop"
  | "sale_customer"
  | "sale_workshop"
  | "tailoring_customer"
  | "tailoring_workshop";

export type TCompanySettings = {
  name: string;
  phone: string;
  email: string;
  address: string;
  tax_number: string;
  commercial_register: string;
};

export type TInvoiceSettings = {
  show_tax: boolean;
  show_logo: boolean;
  show_discount: boolean;
  show_customer_rules: boolean;
  show_workshop_notes: boolean;
  template: TInvoiceTemplate;
  footer_text: string;
  rules: Record<TInvoiceRuleKey, string[]>;
};

export type TAppSettings = {
  timezone: string;
  currency: string;
  currency_symbol?: string;
  currency_label?: string;
  locale: "ar" | "en" | string;
  date_format: string;
  company: TCompanySettings;
  invoice: TInvoiceSettings;
};

export type TUpdateAppSettingsRequest = {
  timezone?: string;
  currency?: string;
  locale?: string;
  date_format?: string;
  company?: Partial<TCompanySettings>;
  invoice?: Partial<Omit<TInvoiceSettings, "rules">> & {
    rules?: Partial<Record<TInvoiceRuleKey, string[]>>;
  };
};
