# ADR-001: Channel-Agnostic Digital Employee

- **Status:** Accepted
- **Date:** 2026-08-06
- **Context:** DressnMore needs an AI worker inside ateliers. Stakeholders might confuse this with a WhatsApp chatbot.
- **Decision:** Build an **AI Agent Platform** as a Digital Employee OS. Channels are adapters only. Core never depends on WhatsApp APIs.
- **Consequences:** Adding Messenger/IG/Web/App/Telegram/Email requires new adapters implementing `ChannelPort`, not redesign of Orchestrator/Tools/Permissions.
