#!/usr/bin/env python3
"""
Skilora Multi-Agent Automation System.

Supports two workflows:
- Browser QA crawling for skilora.dev
- PHPStan remediation with staged level progression
"""

import argparse
import json
import os
import re
import subprocess
import sys
import time
import traceback
from datetime import datetime
from pathlib import Path

from dotenv import load_dotenv
from rich.console import Console
from rich.panel import Panel
from rich.markdown import Markdown
from rich.table import Table

load_dotenv()
console = Console()

# ── Paths ──
BASE_DIR = Path(__file__).parent
PROJECT_ROOT = BASE_DIR.parent.parent  # /Users/.../ESPRIT/WEB
MEMORY_FILE = BASE_DIR / "memory.json"
FINDINGS_DIR = BASE_DIR / "findings"
FINDINGS_DIR.mkdir(exist_ok=True)
PHPSTAN_DIR = FINDINGS_DIR / "phpstan"
PHPSTAN_DIR.mkdir(exist_ok=True)
PHPSTAN_CONFIG_FILE = PROJECT_ROOT / "phpstan.dist.neon"
PHPSTAN_LEVEL_SEQUENCE = [3, 5, 8]
DEFAULT_MAX_FIX_ATTEMPTS = 6
DEFAULT_MAX_FILES_PER_PATCH = 3
DEFAULT_MAX_ERRORS_PER_PATCH = 12

# ── URL → Local Source File Map ──
SOURCE_MAP = {
    "/login": [
        "templates/security/login.html.twig",
        "src/Controller/SecurityController.php",
    ],
    "/workspace": [
        "templates/dashboard/freelancer.html.twig",
        "src/Controller/DashboardController.php",
    ],
    "/offres": [
        "templates/recruitment/candidate/job_offer/index.html.twig",
        "src/Recruitment/Controller/CandidateJobOfferController.php",
        "src/Recruitment/Service/AnetiService.php",
        "templates/recruitment/candidate/base.html.twig",
    ],
    "/settings": [
        "templates/user/settings/index.html.twig",
        "src/Controller/SettingsController.php",
    ],
    "/profile": [
        "templates/user/profile/index.html.twig",
        "src/Controller/ProfileController.php",
    ],
    "/community": [
        "templates/community/posts/index.html.twig",
        "src/Controller/Community/CommunityController.php",
        "src/Repository/CommunityPostRepository.php",
    ],
    "/community/posts": [
        "templates/community/posts/index.html.twig",
        "src/Controller/Community/CommunityController.php",
        "src/Repository/CommunityPostRepository.php",
    ],
    "/workspace/wallet": [
        "templates/finance/wallet/index.html.twig",
        "src/Controller/Finance/WalletController.php",
        "src/Service/Finance/WalletService.php",
    ],
    "/notifications": [
        "templates/notification/index.html.twig",
        "src/Controller/NotificationController.php",
    ],
    "/formations": [
        "templates/formation/catalogue.html.twig",
        "src/Controller/FormationController.php",
    ],
    "/mon-espace/candidatures": [
        "templates/recruitment/candidate/applications/index.html.twig",
        "src/Recruitment/Controller/CandidateAreaController.php",
    ],
    "/mon-espace/offres": [
        "src/Recruitment/Controller/CandidateHireOfferController.php",
    ],
    "/mon-espace/saved-jobs": [
        "src/Recruitment/Controller/CandidateSavedJobsController.php",
    ],
    "/mon-espace/preferences-emploi": [
        "src/Recruitment/Controller/CandidateJobPreferencesController.php",
    ],
    "/my-formations": [
        "src/Controller/Formation/LearningController.php",
    ],
    "/": [
        "templates/landing/index.html.twig",
        "templates/base.html.twig",
        "templates/layouts/platform.html.twig",
    ],
}


class SourceReader:
    """Read local project source files for a given route."""

    @staticmethod
    def get_source_context(page_path: str, max_lines_per_file: int = 120) -> str:
        route = page_path.split("#")[0].rstrip("/")
        if not route:
            route = "/"

        files = SOURCE_MAP.get(route, [])
        if not files:
            for prefix, flist in SOURCE_MAP.items():
                if route.startswith(prefix) and len(prefix) > 1:
                    files = flist
                    break

        if not files:
            return "[No local source files mapped for this route]"

        chunks = []
        for rel_path in files:
            full = PROJECT_ROOT / rel_path
            if not full.exists():
                chunks.append(f"--- {rel_path} [NOT FOUND] ---")
                continue
            try:
                content = full.read_text(errors="replace")
                lines = content.splitlines()
                if len(lines) > max_lines_per_file:
                    head = "\n".join(lines[:max_lines_per_file])
                    chunks.append(f"--- {rel_path} (first {max_lines_per_file}/{len(lines)} lines) ---\n{head}")
                else:
                    chunks.append(f"--- {rel_path} ({len(lines)} lines) ---\n{content}")
            except Exception as e:
                chunks.append(f"--- {rel_path} [READ ERROR: {e}] ---")

        return "\n\n".join(chunks)

# ── Agent Configs ──
AGENTS = [
    {
        "name": "Navigator",
        "provider": "openai",
        "model": "gpt-5.4",
        "color": "cyan",
        "system": (
            "You are Navigator, a QA agent for the Skilora web platform (skilora.dev). "
            "Your role: Browse pages, identify broken links, missing assets, 500 errors, "
            "slow pages, UI glitches. You receive page HTML/screenshots and report findings. "
            "Be extremely concise. Format: bullet list of issues found or 'CLEAN' if none. "
            "Always suggest the next page to check."
        ),
    },
    {
        "name": "Analyst",
        "provider": "openai",
        "model": "gpt-5.4",
        "color": "green",
        "system": (
            "You are Analyst, a code/UX analysis agent for Skilora. "
            "You receive findings from Navigator AND the actual local source code for the route. "
            "Cross-reference the browser findings with the real code to identify root causes. "
            "Reference exact file paths and line numbers from the source. "
            "Prioritize by severity: CRITICAL > HIGH > MEDIUM > LOW. "
            "Be extremely concise. No fluff."
        ),
    },
    {
        "name": "Architect",
        "provider": "openai",
        "model": "gpt-5.4",
        "color": "yellow",
        "system": (
            "You are Architect, a system design agent for Skilora. "
            "You review findings from Navigator, analysis from Analyst, and local source code. "
            "Suggest architectural improvements, performance optimizations, "
            "security fixes, and UX patterns. Reference actual code when possible. "
            "Be concise. Output actionable items only."
        ),
    },
    {
        "name": "Verifier",
        "provider": "openai",
        "model": "gpt-5.4",
        "color": "magenta",
        "system": (
            "You are Verifier, a fact-checking agent for the Skilora QA team. "
            "You receive the LOCAL SOURCE CODE for each route and the findings from Navigator, Analyst, and Architect. "
            "Your job: confirm or deny each finding by referencing the actual code. "
            "Output a verdict table:\n"
            "- CONFIRMED: finding matches code evidence\n"
            "- PARTIAL: finding is partially correct but needs nuance\n"
            "- FALSE POSITIVE: finding does not match the actual code\n"
            "- ALREADY FIXED: the code already handles this\n"
            "Be factual. Quote exact lines when confirming."
        ),
    },
]

PHPSTAN_AGENT_SYSTEMS = {
    "Navigator": (
        "You are Navigator, the static-analysis triage agent for Skilora. "
        "You receive PHPStan output and the relevant local PHP source. "
        "Identify the smallest safe batch of issues to fix first, focusing on blockers and high-signal root causes. "
        "Be concise. If you propose code changes, include one valid unified diff in a fenced ```diff block."
    ),
    "Analyst": (
        "You are Analyst, the PHPStan remediation agent for Skilora. "
        "You receive PHPStan findings and local source code. "
        "Diagnose root causes and produce the smallest correct code changes to fix the reported issues. "
        "Prefer typed fixes, null-safety, and precise return/value handling over suppressions. "
        "If edits are needed, include one valid unified diff in a fenced ```diff block."
    ),
    "Architect": (
        "You are Architect, the code-quality agent for Skilora. "
        "Review the PHPStan findings and the proposed fixes, and improve them if they are brittle or over-engineered. "
        "Keep fixes minimal, maintainable, and aligned with Symfony/PHP best practices. "
        "If you change the proposal, include one valid unified diff in a fenced ```diff block."
    ),
    "Verifier": (
        "You are Verifier, the final gate for PHPStan fixes. "
        "Check whether the proposed edits directly address the reported PHPStan findings without introducing unrelated changes. "
        "If the current proposal is good, restate it as a single valid unified diff in a fenced ```diff block. "
        "If it is wrong, output a corrected unified diff instead."
    ),
}


# ── LLM Clients ──
class LLMClient:
    def __init__(self, provider, model):
        self.provider = provider
        self.model = model
        self.alive = True

    def chat(self, system, messages):
        try:
            if self.provider == "openai":
                return self._openai(system, messages)
            elif self.provider == "groq":
                return self._groq(system, messages)
            elif self.provider == "gemini":
                return self._gemini(system, messages)
        except Exception as e:
            err = str(e)
            if "rate_limit" in err.lower() or "429" in err:
                console.print(f"[red]Rate limited on {self.provider}, waiting 30s...[/red]")
                time.sleep(30)
                return self.chat(system, messages)
            if "auth" in err.lower() or "401" in err or "invalid" in err.lower():
                console.print(f"[red]Key dead for {self.provider}: {err[:80]}[/red]")
                self.alive = False
                return None
            console.print(f"[red]Error ({self.provider}): {err[:120]}[/red]")
            return None

    def _openai(self, system, messages):
        from openai import OpenAI
        client = OpenAI(api_key=os.getenv("OPENAI_API_KEY"))
        msgs = [{"role": "system", "content": system}]
        msgs.extend(messages)
        resp = client.chat.completions.create(model=self.model, messages=msgs, max_completion_tokens=1500, temperature=0.3)
        return resp.choices[0].message.content

    def _groq(self, system, messages):
        from groq import Groq
        client = Groq(api_key=os.getenv("GROQ_API_KEY"))
        msgs = [{"role": "system", "content": system}]
        msgs.extend(messages)
        resp = client.chat.completions.create(model=self.model, messages=msgs, max_completion_tokens=1500, temperature=0.3)
        return resp.choices[0].message.content

    def _gemini(self, system, messages):
        import google.generativeai as genai
        genai.configure(api_key=os.getenv("GEMINI_API_KEY"))
        model = genai.GenerativeModel(self.model, system_instruction=system)
        chat_history = []
        for m in messages:
            role = "user" if m["role"] == "user" else "model"
            chat_history.append({"role": role, "parts": [m["content"]]})
        if chat_history and chat_history[-1]["role"] == "model":
            chat_history.append({"role": "user", "parts": ["Continue your analysis."]})
        elif not chat_history:
            chat_history.append({"role": "user", "parts": ["Begin analysis."]})
        chat = model.start_chat(history=chat_history[:-1])
        resp = chat.send_message(chat_history[-1]["parts"][0])
        return resp.text


class PhpStanRunner:
    def __init__(self, config_path: Path):
        self.config_path = config_path

    def set_level(self, level: int) -> None:
        content = self.config_path.read_text()
        updated, count = re.subn(
            r"^(\s*level:\s*)\d+\s*$",
            rf"\g<1>{level}",
            content,
            count=1,
            flags=re.MULTILINE,
        )
        if count != 1:
            raise RuntimeError(f"Could not update PHPStan level in {self.config_path}")
        self.config_path.write_text(updated)

    def analyse(self, level: int) -> dict:
        command = [
            "vendor/bin/phpstan",
            "analyse",
            "src/",
            "--configuration",
            str(self.config_path),
            "--no-progress",
            "--error-format=json",
        ]
        result = subprocess.run(
            command,
            cwd=PROJECT_ROOT,
            capture_output=True,
            text=True,
        )
        payload = self._decode_json(result.stdout)
        return {
            "level": level,
            "exit_code": result.returncode,
            "stdout": result.stdout,
            "stderr": result.stderr,
            "totals": payload.get("totals", {}),
            "errors": self._extract_errors(payload),
            "raw": payload,
        }

    def _decode_json(self, raw_output: str) -> dict:
        text = raw_output.strip()
        if not text:
            return {}
        try:
            return json.loads(text)
        except json.JSONDecodeError:
            start = text.find("{")
            if start >= 0:
                return json.loads(text[start:])
        return {}

    def _extract_errors(self, payload: dict) -> list[dict]:
        items = []
        for file_path, details in payload.get("files", {}).items():
            for message in details.get("messages", []):
                items.append({
                    "file": to_project_relative(file_path),
                    "line": message.get("line"),
                    "message": message.get("message", ""),
                    "identifier": message.get("identifier", ""),
                    "tip": message.get("tip", ""),
                })

        for message in payload.get("errors", []):
            items.append({
                "file": None,
                "line": None,
                "message": str(message),
                "identifier": "",
                "tip": "",
            })

        return items


def to_project_relative(path_str: str | None) -> str | None:
    if not path_str:
        return None
    try:
        return Path(path_str).resolve().relative_to(PROJECT_ROOT.resolve()).as_posix()
    except Exception:
        return path_str


def truncate(text: str, limit: int) -> str:
    if len(text) <= limit:
        return text
    return text[:limit] + "\n...[truncated]"


def build_clients() -> dict[str, LLMClient]:
    clients = {}
    for agent in AGENTS:
        clients[agent["name"]] = LLMClient(agent["provider"], agent["model"])
    return clients


def validate_clients(clients: dict[str, LLMClient]) -> list[dict]:
    console.print("\n[bold]Validating API keys...[/bold]")
    alive_agents = []
    for agent in AGENTS:
        client = clients[agent["name"]]
        test = client.chat("Say OK", [{"role": "user", "content": "Say OK"}])
        if test and client.alive:
            console.print(f"  [green]✓[/green] {agent['name']} ({agent['provider']}/{agent['model']})")
            alive_agents.append(agent)
        else:
            console.print(f"  [red]✗[/red] {agent['name']} ({agent['provider']}) — DEAD, will skip")
    return alive_agents


def group_phpstan_errors(errors: list[dict], max_files: int, max_errors: int) -> dict[str, list[dict]]:
    grouped: dict[str, list[dict]] = {}
    for error in errors:
        file_path = error.get("file") or "[global]"
        grouped.setdefault(file_path, []).append(error)

    ordered_files = sorted(
        grouped.items(),
        key=lambda item: (-len(item[1]), item[0]),
    )

    selected: dict[str, list[dict]] = {}
    error_count = 0
    for file_path, file_errors in ordered_files:
        if len(selected) >= max_files or error_count >= max_errors:
            break
        remaining = max_errors - error_count
        selected[file_path] = file_errors[:remaining]
        error_count += len(selected[file_path])
    return selected


def format_phpstan_batch(batch: dict[str, list[dict]]) -> str:
    parts = []
    for file_path, errors in batch.items():
        parts.append(f"{file_path} ({len(errors)} issues)")
        for error in errors:
            line = f":{error['line']}" if error.get("line") else ""
            identifier = f" [{error['identifier']}]" if error.get("identifier") else ""
            parts.append(f"- {file_path}{line}{identifier}: {error['message']}")
            if error.get("tip"):
                parts.append(f"  tip: {error['tip']}")
    return "\n".join(parts)


def read_php_error_context(rel_path: str, errors: list[dict], radius: int = 20, max_lines: int = 220) -> str:
    if rel_path == "[global]":
        return "[Global PHPStan error with no file-specific context]"

    full_path = PROJECT_ROOT / rel_path
    if not full_path.exists():
        return f"[{rel_path} not found]"

    lines = full_path.read_text(errors="replace").splitlines()
    if not lines:
        return f"[{rel_path} is empty]"

    ranges = []
    for error in errors:
        line = error.get("line")
        if not isinstance(line, int):
            continue
        start = max(1, line - radius)
        end = min(len(lines), line + radius)
        ranges.append((start, end))

    if not ranges:
        ranges = [(1, min(len(lines), max_lines))]

    merged = []
    for start, end in sorted(ranges):
        if not merged or start > merged[-1][1] + 1:
            merged.append([start, end])
        else:
            merged[-1][1] = max(merged[-1][1], end)

    rendered = []
    rendered_lines = 0
    for start, end in merged:
        block = []
        for number in range(start, end + 1):
            block.append(f"{number:4}: {lines[number - 1]}")
        rendered.append(f"--- {rel_path}:{start}-{end} ---\n" + "\n".join(block))
        rendered_lines += end - start + 1
        if rendered_lines >= max_lines:
            break

    return "\n\n".join(rendered)


def build_phpstan_source_context(batch: dict[str, list[dict]]) -> str:
    sections = []
    for file_path, errors in batch.items():
        sections.append(read_php_error_context(file_path, errors))
    return "\n\n".join(sections)


def extract_diff_block(text: str) -> str | None:
    match = re.search(r"```(?:diff|patch)?\n(.*?)```", text, re.DOTALL)
    if match:
        return match.group(1).strip()
    stripped = text.strip()
    if stripped.startswith("diff --git"):
        return stripped
    return None


def choose_patch(responses: dict[str, str]) -> tuple[str | None, str | None]:
    priority = ["Verifier", "Architect", "Analyst", "Navigator"]
    for agent_name in priority:
        response = responses.get(agent_name)
        if not response:
            continue
        patch_text = extract_diff_block(response)
        if patch_text:
            return patch_text, agent_name
    return None, None


def apply_unified_diff(patch_text: str) -> tuple[bool, str]:
    result = subprocess.run(
        ["git", "apply", "--reject", "--whitespace=nowarn", "-"],
        cwd=PROJECT_ROOT,
        capture_output=True,
        text=True,
        input=patch_text,
    )
    output = (result.stdout + "\n" + result.stderr).strip()
    return result.returncode == 0, output


def render_phpstan_summary(analysis: dict, attempt: int) -> None:
    table = Table(title=f"PHPStan level {analysis['level']} — attempt {attempt}")
    table.add_column("Metric")
    table.add_column("Value", justify="right")
    totals = analysis.get("totals", {})
    table.add_row("Exit code", str(analysis.get("exit_code", "?")))
    table.add_row("File errors", str(totals.get("file_errors", len(analysis.get("errors", [])))))
    table.add_row("Errors", str(totals.get("errors", 0)))
    console.print(table)


def save_phpstan_report(entries: list[dict], last_level: int | None) -> Path:
    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    report_path = PHPSTAN_DIR / f"phpstan_report_{ts}.md"
    lines = [
        f"# PHPStan Remediation Report — {datetime.now().strftime('%Y-%m-%d %H:%M')}",
        f"",
        f"- Last attempted level: {last_level if last_level is not None else 'n/a'}",
        f"- Recorded events: {len(entries)}",
        "",
    ]
    for entry in entries:
        lines.append(f"## {entry['title']}")
        lines.append(entry["body"])
        lines.append("")
    report_path.write_text("\n".join(lines))
    console.print(f"[green]PHPStan report saved: {report_path}[/green]")
    return report_path


def build_phpstan_prompt(level: int, attempt: int, analysis: dict, batch: dict[str, list[dict]], source_ctx: str, config_text: str, conversation: list[dict]) -> str:
    prompt = [
        f"PHPStan target level: {level}",
        f"Attempt: {attempt}",
        "",
        "Current PHPStan summary:",
        json.dumps(analysis.get("totals", {}), indent=2, default=str),
        "",
        "Current error batch:",
        format_phpstan_batch(batch),
        "",
        "Relevant local source:",
        truncate(source_ctx, 12000),
        "",
        "Current phpstan.dist.neon:",
        config_text,
        "",
        "Response rules:",
        "- Fix only the listed PHPStan findings or their direct root cause.",
        "- Do not lower the PHPStan level.",
        "- Do not add ignore rules unless absolutely unavoidable.",
        "- Keep the patch minimal and valid for git apply.",
        "- If you propose edits, include exactly one unified diff in a fenced ```diff block using repo-relative paths.",
        "",
        "Conversation so far this round:",
    ]
    for message in conversation[-8:]:
        prompt.append(f"[{message['agent']}]: {truncate(message['content'], 500)}")
    prompt.append("\nYour turn.")
    return "\n".join(prompt)


def run_phpstan_mode(levels: list[int], max_attempts: int, max_files: int, max_errors: int) -> None:
    console.print(Panel.fit(
        "[bold]Skilora PHPStan Remediation[/bold]\n"
        "4 agents • staged levels • unified diff patching",
        border_style="cyan",
    ))

    clients = build_clients()
    alive_agents = validate_clients(clients)
    if not alive_agents:
        console.print("[red]No alive agents. Exiting.[/red]")
        return

    runner = PhpStanRunner(PHPSTAN_CONFIG_FILE)
    report_entries: list[dict] = []
    last_level = None

    for level in levels:
        last_level = level
        runner.set_level(level)
        console.print(f"\n[bold cyan]PHPStan level {level}[/bold cyan]")
        level_cleared = False

        for attempt in range(1, max_attempts + 1):
            analysis = runner.analyse(level)
            render_phpstan_summary(analysis, attempt)

            if not analysis["errors"] and analysis.get("exit_code", 1) == 0:
                console.print(f"[green]Level {level} is clean.[/green]")
                report_entries.append({
                    "title": f"Level {level} clean",
                    "body": f"PHPStan level {level} completed successfully on attempt {attempt}.",
                })
                level_cleared = True
                break

            batch = group_phpstan_errors(analysis["errors"], max_files=max_files, max_errors=max_errors)
            if not batch:
                console.print("[red]PHPStan returned errors, but none could be parsed into a fix batch.[/red]")
                report_entries.append({
                    "title": f"Level {level} parsing failure",
                    "body": truncate(analysis.get("stdout", "") + "\n" + analysis.get("stderr", ""), 4000),
                })
                save_phpstan_report(report_entries, last_level)
                return

            source_ctx = build_phpstan_source_context(batch)
            config_text = PHPSTAN_CONFIG_FILE.read_text()
            conversation = []
            responses: dict[str, str] = {}

            for agent in alive_agents:
                system_prompt = PHPSTAN_AGENT_SYSTEMS.get(agent["name"], agent["system"])
                prompt = build_phpstan_prompt(level, attempt, analysis, batch, source_ctx, config_text, conversation)

                console.print(f"\n[{agent['color']}]{'─' * 40}[/{agent['color']}]")
                console.print(f"[bold {agent['color']}]{agent['name']}[/bold {agent['color']}] ({agent['provider']})")

                response = clients[agent["name"]].chat(system_prompt, [{"role": "user", "content": prompt}])
                if not response:
                    continue

                console.print(Panel(Markdown(response[:1000]), border_style=agent["color"], title=agent["name"]))
                responses[agent["name"]] = response
                conversation.append({"agent": agent["name"], "content": response})
                report_entries.append({
                    "title": f"Level {level} attempt {attempt} — {agent['name']}",
                    "body": truncate(response, 4000),
                })
                time.sleep(1)

            patch_text, patch_author = choose_patch(responses)
            if not patch_text:
                console.print("[red]No agent produced an applicable unified diff. Stopping.[/red]")
                report_entries.append({
                    "title": f"Level {level} attempt {attempt} — no patch",
                    "body": "No fenced unified diff was produced by the active agents.",
                })
                save_phpstan_report(report_entries, last_level)
                return

            patch_path = PHPSTAN_DIR / f"level_{level}_attempt_{attempt}.diff"
            patch_path.write_text(patch_text)
            applied, apply_output = apply_unified_diff(patch_text)
            if not applied:
                console.print("[red]Patch application failed.[/red]")
                console.print(Panel(truncate(apply_output or "git apply failed with no output.", 2000), border_style="red", title="git apply"))
                report_entries.append({
                    "title": f"Level {level} attempt {attempt} — patch failed",
                    "body": truncate(apply_output or "git apply failed with no output.", 4000),
                })
                save_phpstan_report(report_entries, last_level)
                return

            console.print(f"[green]Applied patch from {patch_author}.[/green]")
            report_entries.append({
                "title": f"Level {level} attempt {attempt} — patch applied",
                "body": f"Patch author: {patch_author}\n\nSaved to {patch_path}",
            })

            validation = runner.analyse(level)
            render_phpstan_summary(validation, attempt)
            report_entries.append({
                "title": f"Level {level} attempt {attempt} — validation",
                "body": truncate(validation.get("stdout", "") + "\n" + validation.get("stderr", ""), 4000),
            })

            if not validation["errors"] and validation.get("exit_code", 1) == 0:
                console.print(f"[green]Level {level} is now clean.[/green]")
                level_cleared = True
                break

        if not level_cleared:
            console.print(f"[yellow]Stopping at level {level}; unresolved PHPStan errors remain.[/yellow]")
            break

    report = save_phpstan_report(report_entries, last_level)
    console.print(f"\n[bold]Report: {report}[/bold]")


# ── Browser ──
class Browser:
    def __init__(self):
        self.driver = None

    def start(self):
        from selenium import webdriver
        from selenium.webdriver.chrome.options import Options
        from selenium.webdriver.chrome.service import Service
        from webdriver_manager.chrome import ChromeDriverManager

        opts = Options()
        opts.add_argument("--window-size=1440,900")
        # NOT headless — user wants to see it
        self.driver = webdriver.Chrome(
            service=Service(ChromeDriverManager().install()),
            options=opts,
        )
        console.print("[green]Browser started[/green]")

    def goto(self, url):
        self.driver.get(url)
        time.sleep(2)
        return self.get_page_info()

    def get_page_info(self):
        title = self.driver.title
        url = self.driver.current_url
        # Get console errors
        logs = []
        try:
            for entry in self.driver.get_log("browser"):
                if entry["level"] in ("SEVERE", "WARNING"):
                    logs.append(f"[{entry['level']}] {entry['message'][:200]}")
        except Exception:
            pass
        # Get page metrics
        perf = self.driver.execute_script(
            "var p = performance.getEntriesByType('navigation')[0]; "
            "return p ? {ttfb: Math.round(p.responseStart), dom: Math.round(p.domContentLoadedEventEnd), "
            "load: Math.round(p.loadEventEnd), size: Math.round(p.transferSize/1024)} : null;"
        )
        # Get visible text summary (first 2000 chars)
        body_text = self.driver.execute_script(
            "return document.body ? document.body.innerText.substring(0, 2000) : '';"
        )
        # Get HTTP status via fetch
        status = self.driver.execute_script(
            "try { var x = new XMLHttpRequest(); x.open('HEAD', window.location.href, false); "
            "x.send(); return x.status; } catch(e) { return -1; }"
        )
        # Get all links on page (generous limit for crawling)
        links = self.driver.execute_script(
            "return [...new Set([...document.querySelectorAll('a[href]')].map(a => a.href))]"
            ".filter(h => h.startsWith(window.location.origin) && !h.includes('#') "
            "&& !h.match(/\\.(jpg|png|gif|svg|css|js|pdf|zip)(\\?|$)/i))"
            ".slice(0, 100);"
        )
        # Get forms on page
        forms = self.driver.execute_script("""
            return [...document.querySelectorAll('form')].map(f => ({
                action: f.action || window.location.href,
                method: (f.method || 'GET').toUpperCase(),
                fields: [...f.querySelectorAll('input,select,textarea')].map(el => ({
                    tag: el.tagName.toLowerCase(),
                    type: (el.type || '').toLowerCase(),
                    name: el.name || '',
                    required: el.required,
                    value: el.value || '',
                    options: el.tagName === 'SELECT' ? [...el.options].map(o => o.value) : []
                })).filter(el => el.name && el.type !== 'hidden')
            })).filter(f => f.fields.length > 0).slice(0, 10);
        """)

        return {
            "url": url,
            "title": title,
            "status": status,
            "perf": perf,
            "console_errors": logs[:10],
            "body_preview": body_text[:1500],
            "links": links,
            "forms": forms or [],
        }

    def login(self, url, username, password):
        if not password:
            console.print("[yellow]No password set — skipping login, checking public pages only[/yellow]")
            return
        console.print(f"[cyan]Logging in as '{username}'...[/cyan]")
        self.goto(url + "/login")
        time.sleep(1)
        try:
            from selenium.webdriver.common.by import By
            fields = self.driver.find_elements(By.CSS_SELECTOR, "input[type='text'], input[type='email'], input[name='_username'], input[name='email']")
            if fields:
                fields[0].clear()
                fields[0].send_keys(username)
            pass_field = self.driver.find_element(By.CSS_SELECTOR, "input[type='password']")
            pass_field.clear()
            pass_field.send_keys(password)
            submit = self.driver.find_element(By.CSS_SELECTOR, "button[type='submit'], input[type='submit']")
            submit.click()
            time.sleep(3)
            console.print(f"[green]Logged in as {username} → {self.driver.current_url}[/green]")
        except Exception as e:
            console.print(f"[yellow]Login failed: {e}[/yellow]")

    def stop(self):
        if self.driver:
            self.driver.quit()


# ── Memory ──
class Memory:
    def __init__(self):
        self.data = {"pages_checked": [], "findings": [], "suggestions": [], "session_count": 0}
        if MEMORY_FILE.exists():
            self.data = json.loads(MEMORY_FILE.read_text())

    def save(self):
        MEMORY_FILE.write_text(json.dumps(self.data, indent=2, default=str))

    def add_finding(self, agent, page, finding):
        self.data["findings"].append({
            "agent": agent,
            "page": page,
            "finding": finding,
            "ts": datetime.now().isoformat(),
        })
        self.save()

    def add_page(self, url):
        if url not in self.data["pages_checked"]:
            self.data["pages_checked"].append(url)
            self.save()

    def get_context(self, max_findings=10):
        recent = self.data["findings"][-max_findings:]
        ctx = f"Pages checked so far: {len(self.data['pages_checked'])}\n"
        ctx += f"Total findings: {len(self.data['findings'])}\n\n"
        if recent:
            ctx += "Recent findings:\n"
            for f in recent:
                ctx += f"- [{f['agent']}] {f['page']}: {f['finding'][:150]}\n"
        return ctx


# ── Report Generator ──
def save_report(memory):
    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    report_path = FINDINGS_DIR / f"report_{ts}.md"

    lines = [
        f"# Skilora QA Report — {datetime.now().strftime('%Y-%m-%d %H:%M')}",
        f"\n## Summary",
        f"- Pages checked: {len(memory.data['pages_checked'])}",
        f"- Findings: {len(memory.data['findings'])}",
        f"- Suggestions: {len(memory.data['suggestions'])}",
        f"\n## Pages Checked",
    ]
    for p in memory.data["pages_checked"]:
        lines.append(f"- {p}")

    lines.append("\n## Findings")
    for f in memory.data["findings"]:
        lines.append(f"\n### [{f['agent']}] {f['page']}")
        lines.append(f"_{f['ts']}_\n")
        lines.append(f['finding'])

    lines.append("\n## Suggestions")
    for s in memory.data["suggestions"]:
        lines.append(f"\n### [{s.get('agent', '?')}]")
        lines.append(s.get("suggestion", ""))

    report_path.write_text("\n".join(lines))
    console.print(f"[green]Report saved: {report_path}[/green]")
    return report_path


# ── Auto-Crawler ──
SEED_PAGES = ["/workspace", "/"]  # Start points after login

ACCOUNTS = [
    {"username": "user", "password": "user123", "seeds": ["/workspace", "/offres", "/profile", "/community/posts", "/formations"]},
    {"username": "admin", "password": "Fyraszx232", "seeds": ["/admin", "/workspace", "/"]},
]

SKIP_PATTERNS = [
    "/logout", "/dev/login", "/oauth", "/webhook",
    "/api/", "/_wdt", "/_profiler", "/media/", "/uploads/",
    "/delete", "/remove", "/destroy",  # destructive actions
]

FAKE_DATA = {
    "text": "Test QA Input",
    "email": "qa-test@skilora.dev",
    "password": "QaTest1234!",
    "number": "42",
    "tel": "+21612345678",
    "url": "https://skilora.dev",
    "search": "developer",
    "textarea": "This is an automated QA test input for form validation.",
    "date": "2026-01-15",
    "datetime-local": "2026-01-15T10:00",
}


class Crawler:
    """BFS auto-crawler: discovers links, detects forms, fills with fake data."""

    def __init__(self, base_url: str, max_pages: int = 60):
        self.base_url = base_url.rstrip("/")
        self.max_pages = max_pages
        self.visited: set[str] = set()
        self.queue: list[str] = []
        self.form_tested: set[str] = set()

    def seed(self, paths: list[str]):
        for p in paths:
            normalized = self._normalize(p)
            if normalized and normalized not in self.visited:
                self.queue.append(normalized)

    def next_page(self) -> str | None:
        while self.queue:
            path = self.queue.pop(0)
            if path not in self.visited:
                return path
        return None

    def mark_visited(self, path: str):
        self.visited.add(self._normalize(path))

    def add_discovered_links(self, links: list[str]):
        """Add newly discovered links from a page."""
        added = 0
        for link in links:
            path = self._normalize(link)
            if not path:
                continue
            if path in self.visited or path in self.queue:
                continue
            if len(self.visited) + len(self.queue) >= self.max_pages:
                break
            self.queue.append(path)
            added += 1
        return added

    def should_test_form(self, page_path: str, form_action: str) -> bool:
        """Decide if we should auto-fill and submit a form."""
        key = f"{page_path}::{form_action}"
        if key in self.form_tested:
            return False
        # Never submit destructive or auth forms on pages we shouldn't touch
        action_path = form_action.replace(self.base_url, "")
        if any(skip in action_path for skip in SKIP_PATTERNS):
            return False
        self.form_tested.add(key)
        return True

    def fill_form(self, browser: 'Browser', form_index: int, form_info: dict) -> dict | None:
        """Fill a form with fake data and submit. Returns page info after submit."""
        try:
            from selenium.webdriver.common.by import By
            forms = browser.driver.find_elements(By.TAG_NAME, "form")
            if form_index >= len(forms):
                return None
            form_el = forms[form_index]

            filled = []
            for field in form_info.get("fields", []):
                name = field["name"]
                ftype = field["type"] or "text"
                tag = field["tag"]

                try:
                    el = form_el.find_element(By.CSS_SELECTOR, f"[name='{name}']")
                except Exception:
                    continue

                if tag == "select" and field.get("options"):
                    from selenium.webdriver.support.ui import Select
                    sel = Select(el)
                    opts = [o for o in field["options"] if o]
                    if opts:
                        sel.select_by_value(opts[min(1, len(opts) - 1)])
                        filled.append(f"{name}={opts[min(1, len(opts)-1)]}")
                elif tag == "textarea":
                    el.clear()
                    el.send_keys(FAKE_DATA.get("textarea", "QA test"))
                    filled.append(f"{name}=[textarea]")
                elif ftype in ("checkbox", "radio"):
                    if not el.is_selected():
                        el.click()
                    filled.append(f"{name}=[checked]")
                elif ftype == "file":
                    continue  # skip file uploads
                else:
                    val = FAKE_DATA.get(ftype, FAKE_DATA["text"])
                    el.clear()
                    el.send_keys(val)
                    filled.append(f"{name}={val[:20]}")

            console.print(f"[magenta]  Form filled: {', '.join(filled[:6])}[/magenta]")

            # Submit
            try:
                submit_btn = form_el.find_element(By.CSS_SELECTOR, "button[type='submit'], input[type='submit']")
                submit_btn.click()
            except Exception:
                from selenium.webdriver.common.keys import Keys
                form_el.send_keys(Keys.RETURN)

            time.sleep(2)
            return browser.get_page_info()
        except Exception as e:
            console.print(f"[yellow]  Form fill failed: {e}[/yellow]")
            return None

    def _normalize(self, url_or_path: str) -> str | None:
        """Normalize to a relative path, filter out skip patterns and external URLs."""
        path = url_or_path
        if path.startswith("http"):
            if not path.startswith(self.base_url):
                return None
            path = path[len(self.base_url):]
        # Strip fragments and trailing slashes
        path = path.split("#")[0].split("?")[0].rstrip("/")
        if not path:
            path = "/"
        if any(skip in path for skip in SKIP_PATTERNS):
            return None
        return path

    @property
    def stats(self) -> str:
        return f"visited={len(self.visited)} queued={len(self.queue)} forms={len(self.form_tested)}"


def run_crawler():
    console.print(Panel.fit(
        "[bold]Skilora Multi-Agent QA System[/bold]\n"
        "4 agents • Selenium • Auto-crawl • Source-aware • Form fuzzing",
        border_style="cyan",
    ))

    # Init
    memory = Memory()
    memory.data["session_count"] += 1
    memory.save()

    clients = build_clients()
    alive_agents = validate_clients(clients)
    if not alive_agents:
        console.print("[red]No alive agents. Exiting.[/red]")
        return

    # Start browser
    browser = Browser()
    browser.start()

    base_url = os.getenv("SKILORA_URL", "https://skilora.dev")

    conversation = []
    round_num = 0

        try:
                for account in ACCOUNTS:
        acct_user = account["username"]
        acct_pass = account["password"]
        acct_seeds = account["seeds"]

        console.print(f"\n[bold cyan]{'═'*60}[/bold cyan]")
        console.print(f"[bold cyan]  CRAWLING AS: {acct_user}[/bold cyan]")
        console.print(f"[bold cyan]{'═'*60}[/bold cyan]")

        browser.login(base_url, acct_user, acct_pass)

        crawler = Crawler(base_url, max_pages=40)
        crawler.seed(acct_seeds)

        while True:
            page = crawler.next_page()
            if page is None:
                console.print(f"\n[bold green]All pages for '{acct_user}' crawled![/bold green]")
                break

            round_num += 1
            crawler.mark_visited(page)
            full_url = base_url + page if page.startswith("/") else page

            console.print(f"\n{'='*60}")
            console.print(f"[bold]Round {round_num} [{acct_user}] — Crawling: {page}[/bold]")
            console.print(f"[dim]{crawler.stats}[/dim]")
            console.print(f"{'='*60}")

            # Navigate
            info = browser.goto(full_url)
            memory.add_page(info["url"])

            # Feed discovered links back into crawl queue
            new_links = crawler.add_discovered_links(info["links"])
            if new_links:
                console.print(f"[dim]+{new_links} new links discovered[/dim]")

            # Build page context for agents
            forms_summary = ""
            if info.get("forms"):
                forms_summary = f"Forms detected: {len(info['forms'])}\n"
                for i, f in enumerate(info["forms"][:5]):
                    field_names = [fd['name'] for fd in f.get('fields', [])]
                    forms_summary += f"  Form {i}: {f['method']} → {f['action'][:80]} fields=[{', '.join(field_names[:8])}]\n"

            page_context = (
                f"PAGE: {info['url']}\n"
                f"Logged in as: {acct_user}\n"
                f"Title: {info['title']}\n"
                f"Status: {info['status']}\n"
                f"Perf: {json.dumps(info['perf'])}\n"
                f"Console errors: {json.dumps(info['console_errors'][:5])}\n"
                f"Body preview:\n{info['body_preview'][:800]}\n"
                f"Links on page: {len(info['links'])}\n"
                f"{forms_summary}"
            )

            # Read local source files for this route
            source_ctx = SourceReader.get_source_context(page)
            console.print(f"[dim]Local source: {len(source_ctx)} chars loaded[/dim]")

            # ── Auto-fill and test forms ──
            form_results = []
            for i, form in enumerate(info.get("forms", [])):
                if not crawler.should_test_form(page, form.get("action", "")):
                    continue
                console.print(f"\n[magenta]Testing form {i} on {page} ({form['method']})[/magenta]")
                if i > 0:
                    browser.goto(full_url)
                    time.sleep(1)
                result = crawler.fill_form(browser, i, form)
                if result:
                    form_results.append({
                        "form_index": i,
                        "method": form["method"],
                        "action": form.get("action", ""),
                        "result_url": result["url"],
                        "result_status": result["status"],
                        "result_errors": result["console_errors"][:3],
                        "result_preview": result["body_preview"][:300],
                    })
                    crawler.add_discovered_links(result.get("links", []))
                browser.goto(full_url)
                time.sleep(1)

            if form_results:
                page_context += f"\nFORM TEST RESULTS:\n{json.dumps(form_results, indent=2, default=str)[:2000]}\n"

            # Round-robin: each alive agent takes a turn
            for agent in alive_agents:
                client = clients[agent["name"]]
                if not client.alive:
                    continue

                mem_ctx = memory.get_context(5)
                prompt = (
                    f"SESSION MEMORY:\n{mem_ctx}\n\n"
                    f"CURRENT PAGE DATA:\n{page_context}\n\n"
                )

                if agent["name"] in ("Analyst", "Architect", "Verifier"):
                    prompt += f"LOCAL SOURCE CODE:\n{source_ctx[:6000]}\n\n"

                prompt += "CONVERSATION SO FAR THIS ROUND:\n"
                for msg in conversation[-8:]:
                    prompt += f"[{msg['agent']}]: {msg['content'][:400]}\n"

                prompt += f"\nYour turn, {agent['name']}. Analyze and respond."

                console.print(f"\n[{agent['color']}]{'─'*40}[/{agent['color']}]")
                console.print(f"[bold {agent['color']}]{agent['name']}[/bold {agent['color']}] ({agent['provider']})")

                response = client.chat(agent["system"], [{"role": "user", "content": prompt}])

                if response:
                    console.print(Panel(Markdown(response[:1000]), border_style=agent["color"], title=agent["name"]))
                    conversation.append({"agent": agent["name"], "content": response})
                    memory.add_finding(agent["name"], f"[{acct_user}] {page}", response[:500])

                time.sleep(1)

            # Save after each round
            memory.save()

        # Final report
        console.print(f"\n[bold green]Session complete! {round_num} pages crawled across {len(ACCOUNTS)} accounts.[/bold green]")
        report = save_report(memory)
        console.print(f"\n[bold]Report: {report}[/bold]")

    except KeyboardInterrupt:
        console.print(f"\n[yellow]Interrupted after {round_num} pages — saving report...[/yellow]")
        save_report(memory)
    finally:
        browser.stop()


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Skilora multi-agent automation")
    parser.add_argument(
        "mode",
        nargs="?",
        choices=["crawl", "phpstan-fix"],
        default=os.getenv("SKILORA_AGENT_MODE", "crawl"),
        help="Workflow to run",
    )
    parser.add_argument(
        "--max-attempts",
        type=int,
        default=int(os.getenv("PHPSTAN_MAX_ATTEMPTS", str(DEFAULT_MAX_FIX_ATTEMPTS))),
        help="Maximum fix attempts per PHPStan level",
    )
    parser.add_argument(
        "--max-files",
        type=int,
        default=int(os.getenv("PHPSTAN_MAX_FILES", str(DEFAULT_MAX_FILES_PER_PATCH))),
        help="Maximum files to include in each PHPStan fix batch",
    )
    parser.add_argument(
        "--max-errors",
        type=int,
        default=int(os.getenv("PHPSTAN_MAX_ERRORS", str(DEFAULT_MAX_ERRORS_PER_PATCH))),
        help="Maximum PHPStan errors to include in each fix batch",
    )
    parser.add_argument(
        "--levels",
        type=int,
        nargs="+",
        default=PHPSTAN_LEVEL_SEQUENCE,
        help="PHPStan levels to progress through in order",
    )
    return parser.parse_args()


def run():
    args = parse_args()
    if args.mode == "phpstan-fix":
        run_phpstan_mode(args.levels, args.max_attempts, args.max_files, args.max_errors)
        return
    run_crawler()


if __name__ == "__main__":
    run()
