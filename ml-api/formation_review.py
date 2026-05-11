"""
Formation AI Review & Personalized Recommendations
Endpoints for the Skilora ML API (FastAPI)

Deploy to VPS: http://164.90.212.158:8000
Add these routes to the main FastAPI app.
"""

from fastapi import APIRouter
from pydantic import BaseModel
from typing import Optional
import os
import json
import httpx

router = APIRouter(prefix="/formation", tags=["formation"])

OPENAI_API_KEY = os.getenv("OPENAI_API_KEY", "")
OPENAI_MODEL = os.getenv("OPENAI_MODEL", "gpt-4o-mini")


# ── Models ──

class MaterialPayload(BaseModel):
    title: str
    kind: Optional[str] = None
    url: Optional[str] = None


class ModulePayload(BaseModel):
    title: str
    description: Optional[str] = None
    duration_minutes: int = 0
    materials: list[MaterialPayload] = []


class FormationReviewRequest(BaseModel):
    formation_id: int
    title: str
    description: Optional[str] = None
    category: Optional[str] = None
    level: str = "beginner"
    duration_hours: int = 0
    modules: list[ModulePayload] = []
    module_count: int = 0
    has_signature: bool = False


class FormationReviewResponse(BaseModel):
    confidence_score: float
    decision: str  # "approve" or "refuse"
    reason: Optional[str] = None
    criteria: dict = {}


class PersonalizedRecommendRequest(BaseModel):
    user_id: int
    skills: list[str] = []
    interests: list[str] = []
    completed_formations: list[int] = []
    role: Optional[str] = None
    experience_level: Optional[str] = None


class RecommendationItem(BaseModel):
    formation_id: int
    title: str
    match_score: float
    reason: str


class PersonalizedRecommendResponse(BaseModel):
    recommendations: list[RecommendationItem] = []
    strategy: str = ""


# ── Review Endpoint ──

REVIEW_SYSTEM_PROMPT = """You are an AI course quality reviewer for Skilora, an online learning platform.
Evaluate the submitted formation (course) and return a JSON object with:
- confidence_score: float 0.0–1.0 (quality rating)
- decision: "approve" if score >= 0.70, "refuse" otherwise
- reason: brief explanation if refused (1-2 sentences)
- criteria: object with sub-scores (content_quality, structure, completeness, materials_quality) each 0.0–1.0

Evaluation criteria:
1. Content quality: Is the title clear and professional? Is the description informative?
2. Structure: Are modules logically organized? Is there a progression?
3. Completeness: Enough modules for the duration? Reasonable depth?
4. Materials quality: Are resources provided? Do URLs look legitimate?
5. Has certificate signature (bonus for professionalism)

Be strict but fair. A basic but well-structured course with 3+ modules should pass.
A course with gibberish titles, no description, or 0 materials should fail."""


@router.post("/review", response_model=FormationReviewResponse)
async def review_formation(payload: FormationReviewRequest):
    """AI-powered formation quality review."""

    if not OPENAI_API_KEY:
        # Fallback heuristic when no API key
        return heuristic_review(payload)

    user_prompt = build_review_prompt(payload)

    try:
        async with httpx.AsyncClient(timeout=30) as client:
            resp = await client.post(
                "https://api.openai.com/v1/chat/completions",
                headers={
                    "Authorization": f"Bearer {OPENAI_API_KEY}",
                    "Content-Type": "application/json",
                },
                json={
                    "model": OPENAI_MODEL,
                    "messages": [
                        {"role": "system", "content": REVIEW_SYSTEM_PROMPT},
                        {"role": "user", "content": user_prompt},
                    ],
                    "response_format": {"type": "json_object"},
                    "temperature": 0.3,
                    "max_tokens": 500,
                },
            )
            resp.raise_for_status()
            data = resp.json()
            content = data["choices"][0]["message"]["content"]
            result = json.loads(content)

            score = max(0.0, min(1.0, float(result.get("confidence_score", 0))))
            decision = "approve" if score >= 0.70 else "refuse"

            return FormationReviewResponse(
                confidence_score=round(score, 2),
                decision=decision,
                reason=result.get("reason"),
                criteria=result.get("criteria", {}),
            )
    except Exception as e:
        # Fallback to heuristic on API failure
        return heuristic_review(payload)


def heuristic_review(payload: FormationReviewRequest) -> FormationReviewResponse:
    """Rule-based fallback when OpenAI is unavailable."""
    score = 0.0
    reasons = []

    # Title quality
    if len(payload.title) >= 5 and not payload.title.isdigit():
        score += 0.20
    else:
        reasons.append("Title is too short or invalid")

    # Description
    if payload.description and len(payload.description) >= 20:
        score += 0.20
    else:
        reasons.append("Description is missing or too short")

    # Modules
    if payload.module_count >= 3:
        score += 0.25
    elif payload.module_count >= 1:
        score += 0.15
    else:
        reasons.append("No modules added")

    # Materials
    total_materials = sum(len(m.materials) for m in payload.modules)
    if total_materials >= 3:
        score += 0.20
    elif total_materials >= 1:
        score += 0.10
    else:
        reasons.append("No learning materials provided")

    # Signature
    if payload.has_signature:
        score += 0.10

    # Duration coherence
    if payload.duration_hours > 0:
        score += 0.05

    score = min(1.0, score)
    decision = "approve" if score >= 0.70 else "refuse"
    reason = "; ".join(reasons) if reasons else None

    return FormationReviewResponse(
        confidence_score=round(score, 2),
        decision=decision,
        reason=reason if decision == "refuse" else None,
        criteria={
            "content_quality": 0.20 if len(payload.title) >= 5 else 0.0,
            "structure": min(0.25, payload.module_count * 0.08),
            "completeness": min(0.20, total_materials * 0.05),
            "materials_quality": min(0.20, total_materials * 0.07),
        },
    )


def build_review_prompt(payload: FormationReviewRequest) -> str:
    modules_text = ""
    for i, m in enumerate(payload.modules, 1):
        mats = ", ".join(f"{mat.title} ({mat.kind})" for mat in m.materials) or "none"
        modules_text += f"\n  {i}. {m.title} ({m.duration_minutes}min) - Materials: {mats}"
        if m.description:
            modules_text += f"\n     Notes: {m.description}"

    return f"""Formation to review:
- Title: {payload.title}
- Description: {payload.description or '(none)'}
- Category: {payload.category or '(none)'}
- Level: {payload.level}
- Duration: {payload.duration_hours}h
- Modules ({payload.module_count}):{modules_text}
- Has certificate signature: {payload.has_signature}

Return your evaluation as JSON."""


# ── Personalized Recommendations ──

RECOMMEND_SYSTEM_PROMPT = """You are a personalized learning recommendation engine for Skilora.
Given a user's profile (skills, interests, completed courses, role), suggest the best formations from the catalog.
Return JSON with:
- recommendations: array of {formation_id, title, match_score (0-1), reason}
- strategy: brief explanation of recommendation logic
Maximum 5 recommendations, sorted by match_score descending."""


@router.post("/personalized-recommend", response_model=PersonalizedRecommendResponse)
async def personalized_recommendations(payload: PersonalizedRecommendRequest):
    """Netflix-style personalized formation recommendations."""

    # For now return empty — will be connected to formation catalog DB
    return PersonalizedRecommendResponse(
        recommendations=[],
        strategy="Recommendation engine requires catalog integration. Connect to formation database for live suggestions.",
    )
