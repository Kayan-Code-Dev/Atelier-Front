/** Backend expects `Y-m-d H:i:s` for MySQL datetime fields. */
export function toMysqlDateTime(dateYmd: string, time = "12:00:00"): string {
  const parts = dateYmd.split("-").map((p) => p.trim());
  if (parts.length !== 3) return `${dateYmd} ${time}`;
  const [y, m, d] = parts;
  return `${y.padStart(4, "0")}-${m.padStart(2, "0")}-${d.padStart(2, "0")} ${time}`;
}

export function nowMysqlDateTime(): string {
  const t = new Date();
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${t.getFullYear()}-${pad(t.getMonth() + 1)}-${pad(t.getDate())} ${pad(t.getHours())}:${pad(t.getMinutes())}:${pad(t.getSeconds())}`;
}

/** Display as YYYY/MM/DD for UI that used mock slashes */
export function ymdToDisplaySlashes(ymd: string): string {
  if (!ymd) return "";
  const s = ymd.slice(0, 10);
  const [y, m, d] = s.split("-");
  if (!y || !m || !d) return ymd;
  return `${y}/${m}/${d}`;
}

export function ymdToIsoDateInput(ymd: string): string {
  return ymd.slice(0, 10);
}
