const SLUG_TO_AR: Record<string, string> = {
  rent: "إيجار",
  utilities: "المرافق (كهرباء، مياه، غاز)",
  utilities_electricity_water_gas: "المرافق (كهرباء، مياه، غاز)",
  electricity_water_gas: "المرافق (كهرباء، مياه، غاز)",
  supplies: "لوازم ومواد",
  supplies_materials: "لوازم ومواد",
  supplies_and_materials: "لوازم ومواد",
  maintenance: "صيانة وإصلاحات",
  maintenance_repairs: "صيانة وإصلاحات",
  maintenance_and_repairs: "صيانة وإصلاحات",
  salaries: "رواتب وأجور",
  salaries_wages: "رواتب وأجور",
  salaries_and_wages: "رواتب وأجور",
  marketing: "تسويق وإعلان",
  marketing_advertising: "تسويق وإعلان",
  marketing_and_advertising: "تسويق وإعلان",
  transportation: "نقل ومواصلات",
  transport: "نقل ومواصلات",
  cleaning: "خدمات تنظيف",
  cleaning_services: "خدمات تنظيف",
  other: "أخرى",
  miscellaneous: "أخرى",
  misc: "أخرى",
};

const EN_LABEL_TO_AR: Record<string, string> = {
  rent: "إيجار",
  "utilities (electricity, water, gas)": "المرافق (كهرباء، مياه، غاز)",
  "supplies & materials": "لوازم ومواد",
  "maintenance & repairs": "صيانة وإصلاحات",
  "salaries & wages": "رواتب وأجور",
  "marketing & advertising": "تسويق وإعلان",
  transportation: "نقل ومواصلات",
  "cleaning services": "خدمات تنظيف",
  other: "أخرى",
};

function normalizeEnKey(s: string): string {
  return s.trim().toLowerCase().replace(/\s+/g, " ");
}

export function expenseCategoryOptionLabelAr(slug: string, apiLabel: string): string {
  const sl = slug.trim().toLowerCase();
  if (SLUG_TO_AR[sl]) return SLUG_TO_AR[sl];

  const key = normalizeEnKey(apiLabel);
  if (EN_LABEL_TO_AR[key]) return EN_LABEL_TO_AR[key];

  return apiLabel;
}
