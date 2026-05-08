# Skilora Multi-Agent QA System

3 AI agents collaboratively browse skilora.dev, find issues, and suggest fixes.

## Agents

| Agent | Provider | Role |
|-------|----------|------|
| **Navigator** | OpenAI GPT-4o | Browse pages, find broken links, 500s, UI bugs |
| **Analyst** | Groq Llama-3.1-70B | Analyze root causes, suggest code fixes |
| **Architect** | Gemini 1.5 Flash | Architectural improvements, perf, security |

## Setup

```bash
cd tools/skilora-agents
pip install -r requirements.txt
# Edit .env with your password
python agents.py
```

## Features

- **Selenium browser** — opens real Chrome, agents see what users see
- **Round-robin turns** — agents discuss each page, building on each other's analysis
- **Memory** — persists across sessions in `memory.json`
- **Auto-discovery** — agents find new pages from links on checked pages
- **Dead key handling** — if a key dies, that agent is removed from rotation
- **Reports** — saved as timestamped MD files in `findings/`

## Streaming the log

Watch the debug stream from VPS while agents browse:
```bash
ssh root@164.90.212.158 "cd /var/www/skilora && php bin/console app:debug:stream"
```

## Files

- `agents.py` — main orchestrator
- `.env` — API keys (never commit)
- `memory.json` — persistent memory across sessions
- `findings/` — generated reports
