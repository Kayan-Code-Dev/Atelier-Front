# Versioning

`ToolVersion` is `major.minor.patch`.

## Compatibility rule

A registered tool version `A` is compatible with required `R` when:

- `A.major == R.major`
- `(A.minor, A.patch) >= (R.minor, R.patch)`

Major mismatches are always rejected.

## Registry behavior

`ToolResolver` with `minimumVersion` emits `ToolVersionIncompatible` and refuses resolution when incompatible — Planner must not invoke the tool.
