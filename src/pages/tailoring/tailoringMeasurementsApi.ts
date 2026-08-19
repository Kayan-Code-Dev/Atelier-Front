/**
 * Tailoring order measurements use the same attribute names as order-item tailoring fields (Laravel).
 */

export type UiMeasurementState = {
  height: string;
  shoulder: string;
  chest: string;
  waist: string;
  hips: string;
  sleeveLength: string;
  sleeveWidth: string;
  dressLength: string;
  neckline: string;
  notes: string;
};

export function uiMeasurementsToApiPayload(
  m: UiMeasurementState,
): Record<string, string> | undefined {
  const out: Record<string, string> = {};
  const put = (apiKey: string, val: string) => {
    const t = val.trim();
    if (t) out[apiKey] = t;
  };

  put("total_length", m.height);
  put("shoulder_width", m.shoulder);
  put("chest_length", m.chest);
  put("waist", m.waist);
  put("hinch", m.hips);
  put("sleeve_length", m.sleeveLength);
  put("cuffs", m.sleeveWidth);
  put("dress_size", m.dressLength);

  const noteParts: string[] = [];
  if (m.neckline.trim()) noteParts.push(`فتحة الرقبة: ${m.neckline.trim()}`);
  if (m.notes.trim()) noteParts.push(m.notes.trim());
  if (noteParts.length) out.measurement_notes = noteParts.join("\n");

  return Object.keys(out).length ? out : undefined;
}
