export interface JournalLine {
  id: string;
  account: string;
  accountCode: string;
  description: string;
  debit: number;
  credit: number;
}

export interface JournalEntry {
  id: string;
  entryNumber: string;
  date: string;
  description: string;
  reference: string;
  branch: string;
  type: "عادي" | "افتتاحي" | "تسوية" | "إقفال";
  status: "مسودة" | "معتمد" | "ملغي";
  lines: JournalLine[];
  createdBy: string;
  approvedBy: string;
  notes: string;
  totalDebit: number;
  totalCredit: number;
}
