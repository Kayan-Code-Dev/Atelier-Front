/**
 * One-off: remove comments that contain Arabic script from source (excludes project/).
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import ts from "typescript";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, "..");

const ARABIC =
  /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;

const SKIP = new Set([
  "node_modules",
  "dist",
  "project",
  ".git",
  "coverage",
  "build",
]);

const EXTS = new Set([
  ".ts",
  ".tsx",
  ".js",
  ".jsx",
  ".mts",
  ".cts",
  ".css",
  ".scss",
]);

function walk(dir, out = []) {
  if (!fs.existsSync(dir)) return out;
  for (const name of fs.readdirSync(dir)) {
    if (SKIP.has(name)) continue;
    const p = path.join(dir, name);
    let st;
    try {
      st = fs.statSync(p);
    } catch {
      continue;
    }
    if (st.isDirectory()) walk(p, out);
    else if (EXTS.has(path.extname(name))) out.push(p);
  }
  return out;
}

function removeSortedRanges(text, ranges) {
  const uniq = [];
  const seen = new Set();
  for (const r of ranges) {
    const k = `${r.start}:${r.end}`;
    if (seen.has(k)) continue;
    seen.add(k);
    uniq.push(r);
  }
  uniq.sort((a, b) => b.start - a.start);
  let out = text;
  for (const { start, end } of uniq) {
    if (start < 0 || end > out.length || start >= end) continue;
    out = out.slice(0, start) + out.slice(end);
  }
  return out;
}

function collectTsCommentRanges(text, fileName) {
  const kind = fileName.endsWith(".tsx")
    ? ts.ScriptKind.TSX
    : fileName.endsWith(".jsx")
      ? ts.ScriptKind.JSX
      : ts.ScriptKind.TS;
  const sf = ts.createSourceFile(
    fileName,
    text,
    ts.ScriptTarget.Latest,
    true,
    kind
  );
  const ranges = [];
  const add = (pos, end) => {
    const slice = text.slice(pos, end);
    if (!ARABIC.test(slice)) return;
    ranges.push({ start: pos, end });
  };
  function visit(node) {
    const lead = ts.getLeadingCommentRanges(text, node.getFullStart());
    if (lead) for (const r of lead) add(r.pos, r.end);
    const trail = ts.getTrailingCommentRanges(text, node.end);
    if (trail) for (const r of trail) add(r.pos, r.end);
    ts.forEachChild(node, visit);
  }
  visit(sf);
  return ranges;
}

function stripJsxArabicBlockComments(text) {
  return text.replace(/\{\/\*[\s\S]*?\*\/\}/g, (m) =>
    ARABIC.test(m) ? "" : m
  );
}

function stripCssArabicBlockComments(text) {
  return text.replace(/\/\*[\s\S]*?\*\//g, (m) =>
    ARABIC.test(m) ? "" : m
  );
}

function stripFullLineSlashSlashArabic(text) {
  return text
    .split("\n")
    .map((line) => {
      const t = line.trimStart();
      if (t.startsWith("//") && ARABIC.test(line)) return "";
      return line;
    })
    .join("\n");
}

function stripInlineSlashSlashArabic(text) {
  const lines = text.split("\n");
  return lines
    .map((line) => {
      if (!ARABIC.test(line)) return line;
      let i = 0;
      let state = "code";
      while (i < line.length - 1) {
        const c = line[i];
        const n = line[i + 1];
        if (state === "code") {
          if (c === "'" || c === '"') state = c;
          else if (c === "`") state = "`";
          else if (c === "/" && n === "/") {
            const tail = line.slice(i);
            if (ARABIC.test(tail))
              return line.slice(0, i).replace(/\s+$/, "");
            return line;
          }
        } else if (state === "'" || state === '"') {
          if (c === "\\") {
            i += 1;
          } else if (c === state) state = "code";
        } else if (state === "`") {
          if (c === "\\") {
            i += 1;
          } else if (c === "`") state = "code";
        }
        i += 1;
      }
      return line;
    })
    .join("\n");
}

function processHtml(text) {
  return text.replace(/<!--[\s\S]*?-->/g, (m) =>
    ARABIC.test(m) ? "" : m
  );
}

function processFile(filePath, ext) {
  let text = fs.readFileSync(filePath, "utf8");
  const orig = text;

  if (ext === ".html") {
    text = processHtml(text);
  } else if (ext === ".css" || ext === ".scss") {
    text = stripCssArabicBlockComments(text);
    text = stripFullLineSlashSlashArabic(text);
  } else if (
    ext === ".ts" ||
    ext === ".tsx" ||
    ext === ".js" ||
    ext === ".jsx" ||
    ext === ".mts" ||
    ext === ".cts"
  ) {
    const rel = path.relative(root, filePath).split(path.sep).join("/");
    const ranges = collectTsCommentRanges(text, rel);
    text = removeSortedRanges(text, ranges);
    text = stripJsxArabicBlockComments(text);
    text = stripFullLineSlashSlashArabic(text);
    text = stripInlineSlashSlashArabic(text);
  }

  text = text.replace(/\n{3,}/g, "\n\n");
  if (text !== orig) fs.writeFileSync(filePath, text, "utf8");
  return text !== orig;
}

function main() {
  const dirs = [path.join(root, "src"), path.join(root, "scripts")].filter(
    (d) => fs.existsSync(d)
  );
  const files = [];
  for (const d of dirs) walk(d, files);

  for (const base of ["vite.config.ts", "vitest.config.ts", "eslint.config.js"]) {
    const f = path.join(root, base);
    if (fs.existsSync(f)) files.push(f);
  }

  const htmlPath = path.join(root, "index.html");
  if (fs.existsSync(htmlPath)) files.push(htmlPath);

  let n = 0;
  for (const f of files) {
    const ext = path.extname(f);
    if (!EXTS.has(ext) && ext !== ".html") continue;
    if (processFile(f, ext)) {
      console.log("updated", path.relative(root, f));
      n += 1;
    }
  }
  console.log("files updated:", n);
}

main();
