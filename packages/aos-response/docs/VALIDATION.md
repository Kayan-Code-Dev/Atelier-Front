# Validation

| Scenario | Covered |
|----------|---------|
| Single tool success | ✓ |
| Multiple tools aggregated | ✓ |
| Tool failure → friendly AR | ✓ |
| Empty results | ✓ |
| Arabic / English | ✓ |
| Multi-step E2E (book / sales / create customer) | ✓ |
| Policy filters secrets | ✓ |

Tests: `tests/Unit/ResponseEngineTest.php`  
Smoke: `php scripts/aos-response-smoke.php`
