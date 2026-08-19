# Localization

`LocalizationService` + `MessageCatalog` support **ar** and **en**.

Keys include tool success templates (`CreateCustomer.success`, `CreateReservation.success`, `GenerateReport.success`, …) and error keys (`error.dress_unavailable`, …).

Unknown locales fall back to Arabic. Extend `MessageCatalog` for more languages later.
