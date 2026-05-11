"""
Skilora ML API — FastAPI
All endpoints consumed by the Symfony SkiloraMlClient.
Deploy: uvicorn main:app --host 0.0.0.0 --port 8000
"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional
import os, re, json, httpx, random

app = FastAPI(title="Skilora ML API", version="1.0.0")
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

LLM_API_KEY = os.getenv("LLM_API_KEY", os.getenv("OPENAI_API_KEY", ""))
LLM_API_URL = os.getenv("LLM_API_URL", "https://api.groq.com/openai/v1/chat/completions")
LLM_MODEL = os.getenv("LLM_MODEL", os.getenv("OPENAI_MODEL", "llama-3.3-70b-versatile"))


# ─────────────────────────── Helpers ───────────────────────────

async def ask_llm(system: str, user: str, max_tokens: int = 900) -> Optional[dict]:
    if not LLM_API_KEY:
        return None
    try:
        async with httpx.AsyncClient(timeout=60) as client:
            resp = await client.post(
                LLM_API_URL,
                headers={"Authorization": f"Bearer {LLM_API_KEY}", "Content-Type": "application/json"},
                json={
                    "model": LLM_MODEL,
                    "messages": [{"role": "system", "content": system}, {"role": "user", "content": user}],
                    "temperature": 0.35,
                    "max_tokens": max_tokens,
                },
            )
            resp.raise_for_status()
            content = resp.json()["choices"][0]["message"]["content"]
            try:
                return json.loads(content)
            except json.JSONDecodeError:
                return {"reply": content}
    except Exception as e:
        print(f"LLM error: {e}")
        return None


# ─────────────────────────── Health ───────────────────────────

@app.get("/")
def health():
    return {"status": "ok", "service": "skilora-ml", "version": "1.0.0"}

@app.get("/health")
def health_check():
    return {"status": "ok"}


# ═══════════════════════════════════════════════════════════════
#  SUPPORT
# ═══════════════════════════════════════════════════════════════

FAQ_KNOWLEDGE = {
    "wallet": "To top up your wallet, go to Finance → Wallet and use the Stripe payment form. Enter the amount in TND and click 'Pay with card'. Your balance will be credited once payment is confirmed.",
    "top up": "To top up your wallet, go to Finance → Wallet and use the Stripe payment form. Enter the amount in TND and click 'Pay with card'. Your balance will be credited once payment is confirmed.",
    "enroll": "To enroll in a formation, go to Formations, click on the course you want, and click 'Enroll'. If it's a paid course, the amount will be deducted from your wallet. Make sure you have sufficient balance.",
    "course": "To enroll in a formation, go to Formations, click on the course you want, and click 'Enroll'. If it's a paid course, the amount will be deducted from your wallet.",
    "certificate": "Certificates are generated automatically once you complete all modules and pass the final quiz of a formation. You can download your certificate from the Learning section.",
    "password": "To change your password, go to Settings → Security. Enter your current password and your new password, then click Save.",
    "application": "To track your application status, go to Applications in the top navigation. You'll see all your submitted applications with their current status (pending, reviewed, interview, hired, rejected).",
    "ticket": "To create a support ticket, go to Support and click '+ New ticket'. Fill in the subject, description, category, and priority. Our team will respond as soon as possible.",
    "interview": "Interview details are shown in your Applications section. You'll see the date, time, format (video/phone/in-person), and any notes from the employer.",
    "profile": "To update your profile, click your avatar in the top right and select 'My Profile'. You can edit your bio, skills, experience, and upload a CV.",
    "job": "To find jobs, go to the Jobs page. You can search by keyword, filter by work type (remote/hybrid/onsite), source, and sort by match score, freshness, or salary.",
    "escrow": "When an employer publishes a job offer with a salary, the amount is held in escrow from their wallet. The freelancer receives payment when the contract is completed.",
    "payment": "Payments on Skilora use a wallet system. Top up via Stripe, and funds are used for formation enrollment and job offer escrow. All transactions are tracked in Finance → Wallet.",
    "company": "To create a company profile, go to 'Publier une offre' (Post an offer) in the top navigation. Your company profile is automatically created when you post your first job offer.",
}

PLATFORM_INFO = """Skilora is a professional platform with these modules:
- Formations: Online courses with enrollment, quizzes, and certificates
- Recruitment: Job listings from Skilora employers + external feeds (ANETI, Reddit, LinkedIn, RSS)
- Finance: Wallet system (Stripe top-ups), escrow for job offers, transaction history
- Community: Social feed, blog posts, and discussions
- Support: Ticket system with AI-powered help center
- AI Tools: Various AI-powered features for productivity"""


class SmartReplyRequest(BaseModel):
    subject: str = ""
    description: str = ""

class TriageRequest(BaseModel):
    subject: str = ""
    description: str = ""
    category: Optional[str] = None


def find_faq_answer(question: str) -> Optional[str]:
    q = question.lower()
    best_match = None
    best_score = 0
    for keyword, answer in FAQ_KNOWLEDGE.items():
        words = keyword.split()
        score = sum(1 for w in words if w in q)
        if score > best_score:
            best_score = score
            best_match = answer
    if best_score > 0:
        return best_match
    return None


@app.post("/support/smart-reply")
async def smart_reply(req: SmartReplyRequest):
    question = req.description or req.subject

    faq = find_faq_answer(question)

    ai_result = await ask_llm(
        system=f"""You are Skilora AI, a helpful assistant for the Skilora platform.
{PLATFORM_INFO}

Answer the user's question concisely and accurately. If you know a specific feature or page they should visit, mention it.
Keep answers under 3 sentences. Be friendly but professional.""",
        user=question,
        max_tokens=300,
    )

    if ai_result and "reply" in ai_result:
        return {"reply": ai_result["reply"]}
    if ai_result and "choices" not in ai_result:
        text = ai_result.get("reply") or next(iter(ai_result.values()), None)
        if text and isinstance(text, str):
            return {"reply": text}

    if faq:
        return {"reply": faq}

    return {"reply": "I can help with questions about formations, recruitment, wallet payments, support tickets, and more. Could you be more specific about what you need help with?"}


@app.post("/support/triage")
async def triage_ticket(req: TriageRequest):
    text = f"{req.subject} {req.description}".lower()
    if any(w in text for w in ["pay", "wallet", "money", "billing", "invoice", "charge"]):
        cat, pri = "billing", "high"
    elif any(w in text for w in ["bug", "error", "crash", "broken", "not work"]):
        cat, pri = "technical", "high"
    elif any(w in text for w in ["account", "login", "password", "access"]):
        cat, pri = "account", "medium"
    elif any(w in text for w in ["feature", "suggest", "improve", "request"]):
        cat, pri = "feature_request", "low"
    else:
        cat, pri = "general", "medium"
    return {"category": req.category or cat, "priority": pri, "confidence": 0.85}


# ═══════════════════════════════════════════════════════════════
#  RECRUITMENT
# ═══════════════════════════════════════════════════════════════

class MatchRequest(BaseModel):
    user_skills: list[str] = []
    job_skills: list[str] = []
    user_experience: Optional[str] = None
    job_experience: Optional[str] = None

class SemanticMatchRequest(BaseModel):
    user_profile: str = ""
    job_description: str = ""

class CvAnalysisRequest(BaseModel):
    cv_text: str = ""
    job_title: Optional[str] = None

class SalaryPredictRequest(BaseModel):
    title: str = ""
    skills: list[str] = []
    experience_years: int = 0
    location: Optional[str] = None

class InterviewQuestionsRequest(BaseModel):
    job_title: str = ""
    skills: list[str] = []
    level: str = "mid"


@app.post("/recruitment/match")
async def match_skills(req: MatchRequest):
    if not req.user_skills or not req.job_skills:
        return {"score": 0, "matched": [], "missing": req.job_skills}
    user_set = {s.lower().strip() for s in req.user_skills}
    job_set = {s.lower().strip() for s in req.job_skills}
    matched = user_set & job_set
    missing = job_set - user_set
    score = round(len(matched) / max(len(job_set), 1) * 100)
    return {"score": score, "matched": list(matched), "missing": list(missing)}


@app.post("/recruitment/semantic-match")
async def semantic_match(req: SemanticMatchRequest):
    ai = await ask_llm(
        "Score how well this user profile matches the job. Return JSON: {score: 0-100, reasons: [str], gaps: [str]}",
        f"Profile: {req.user_profile}\n\nJob: {req.job_description}",
    )
    if ai and "score" in ai:
        return ai
    words_profile = set(req.user_profile.lower().split())
    words_job = set(req.job_description.lower().split())
    overlap = len(words_profile & words_job)
    score = min(100, int(overlap / max(len(words_job), 1) * 150))
    return {"score": score, "reasons": ["keyword overlap analysis"], "gaps": []}


class CvJobFitRequest(BaseModel):
    cv_text: str = ""
    job_title: Optional[str] = None
    job_description: Optional[str] = None
    job_skills: list[str] = []


@app.post("/recruitment/analyze-cv")
async def analyze_cv(req: CvAnalysisRequest):
    ai = await ask_llm(
        """You are a professional CV reviewer for Skilora, a freelancing and recruitment platform.
Analyze this CV thoroughly. Return JSON with:
- skills: list of technical and soft skills found
- experience_years: estimated years of experience
- education: highest education level
- summary: 2-sentence professional summary of the candidate
- strengths: top 3-5 strengths (specific, actionable)
- improvements: top 3-5 areas to improve (specific, actionable tips)
- overall_score: 0-100 quality score
- profile_type: "junior" / "mid" / "senior" / "lead"
Be specific and constructive in feedback.""",
        req.cv_text[:4000],
    )
    if ai and "skills" in ai:
        return ai
    skills = re.findall(r'\b(?:Python|Java|PHP|Symfony|React|Angular|SQL|Docker|Git|AWS|Node|TypeScript|JavaScript|Laravel|Spring|Vue|Next|FastAPI|Flask|Django|Go|Rust|C\+\+|Kubernetes|Terraform|CI/CD|Figma|Tailwind)\b', req.cv_text, re.I)
    return {"skills": list(set(skills)), "experience_years": 0, "education": "unknown", "summary": "Upload a detailed CV for AI analysis.", "strengths": [], "improvements": ["Add more details to your CV for better analysis"], "overall_score": 30, "profile_type": "junior"}


@app.post("/recruitment/cv-job-fit")
async def cv_job_fit(req: CvJobFitRequest):
    """Analyze how well a CV fits a specific job — gives tailored advice to the freelancer."""
    job_context = f"Job: {req.job_title or 'Unknown'}\n"
    if req.job_description:
        job_context += f"Description: {req.job_description[:1500]}\n"
    if req.job_skills:
        job_context += f"Required skills: {', '.join(req.job_skills)}\n"

    ai = await ask_llm(
        """You are a career coach on Skilora, a freelancing platform. A freelancer wants to apply for a job.
Analyze their CV against the job requirements. Return JSON with:
- fit_score: 0-100 how well they match
- matching_skills: skills from CV that match the job
- missing_skills: skills the job needs but CV lacks
- cover_letter_tips: 2-3 specific tips for their cover letter
- interview_prep: 3 likely interview questions for this specific role
- application_advice: 1-2 sentences of honest advice (should they apply? what to highlight?)
- profile_improvements: what to add to their profile before applying
Be honest but encouraging. Help them succeed.""",
        f"{job_context}\n\nCandidate CV:\n{req.cv_text[:3000]}",
        max_tokens=1200,
    )
    if ai and "fit_score" in ai:
        return ai

    cv_skills = set(s.lower() for s in re.findall(r'\b(?:Python|Java|PHP|Symfony|React|Angular|SQL|Docker|Git|AWS|Node|TypeScript|JavaScript|Laravel|Spring|Vue|FastAPI)\b', req.cv_text, re.I))
    job_skill_set = set(s.lower() for s in req.job_skills)
    matched = cv_skills & job_skill_set
    missing = job_skill_set - cv_skills
    score = round(len(matched) / max(len(job_skill_set), 1) * 100) if job_skill_set else 50

    return {
        "fit_score": score,
        "matching_skills": list(matched),
        "missing_skills": list(missing),
        "cover_letter_tips": ["Highlight your relevant experience", "Mention specific projects"],
        "interview_prep": [f"Tell us about your experience with {list(matched)[0]}" if matched else "Why are you interested in this role?"],
        "application_advice": "Review the missing skills and consider upskilling before applying." if score < 50 else "You're a reasonable fit — apply and highlight your matching skills!",
        "profile_improvements": ["Add more skills to your profile", "Include project links or portfolio"],
    }


@app.post("/recruitment/salary-predict")
async def predict_salary(req: SalaryPredictRequest):
    base = 1500
    if req.experience_years > 5:
        base = 4000
    elif req.experience_years > 2:
        base = 2500
    skill_bonus = len(req.skills) * 100
    return {"min_salary": base + skill_bonus, "max_salary": base + skill_bonus + 1500, "currency": "TND", "confidence": 0.65}


@app.post("/recruitment/interview-questions")
async def interview_questions(req: InterviewQuestionsRequest):
    ai = await ask_llm(
        f"Generate 5 interview questions for a {req.level}-level {req.job_title} position. Skills: {', '.join(req.skills)}. Return JSON: {{questions: [{{question: str, category: str, difficulty: str}}]}}",
        f"Job: {req.job_title}, Level: {req.level}",
    )
    if ai and "questions" in ai:
        return ai
    return {"questions": [
        {"question": f"Tell me about your experience with {req.skills[0] if req.skills else req.job_title}.", "category": "experience", "difficulty": req.level},
        {"question": "Describe a challenging project you worked on recently.", "category": "behavioral", "difficulty": "mid"},
        {"question": "How do you handle tight deadlines and conflicting priorities?", "category": "behavioral", "difficulty": "mid"},
        {"question": f"What interests you about this {req.job_title} role?", "category": "motivation", "difficulty": "easy"},
        {"question": "Where do you see yourself in 3 years?", "category": "growth", "difficulty": "easy"},
    ]}


# ═══════════════════════════════════════════════════════════════
#  FORMATION
# ═══════════════════════════════════════════════════════════════

from formation_review import router as formation_router
app.include_router(formation_router)

class FormationRecommendRequest(BaseModel):
    user_skills: list[str] = []
    completed_courses: list[str] = []
    interests: list[str] = []
    career_goal: str = ""
    experience_level: str = "beginner"

class CompletionPredictRequest(BaseModel):
    user_id: int = 0
    formation_id: int = 0
    progress_percent: float = 0
    days_enrolled: int = 0
    quiz_scores: list[float] = []
    modules_completed: int = 0
    total_modules: int = 1
    avg_time_per_module: float = 30
    login_frequency: float = 1.0

class PersonalizedRecommendRequest(BaseModel):
    user_skills: list[str] = []
    completed_courses: list[str] = []
    career_goal: str = ""
    experience_level: str = "beginner"
    available_formations: list[dict] = []


@app.post("/formation/recommend")
async def recommend_formations(req: FormationRecommendRequest):
    skills_str = ", ".join(req.user_skills) if req.user_skills else "none specified"
    completed_str = ", ".join(req.completed_courses) if req.completed_courses else "none"
    interests_str = ", ".join(req.interests) if req.interests else "general"

    ai = await ask_llm(
        """You are a course recommendation engine for Skilora, a professional learning platform.
Based on the user's skills, completed courses, interests, career goal, and experience level,
recommend 3-5 course topics they should take next.

Return JSON:
{
  "recommendations": [
    {"title": "Course title", "reason": "Why this course", "priority": "high/medium/low", "estimated_hours": 10, "level": "beginner/intermediate/advanced"}
  ],
  "learning_path": "Brief 1-2 sentence suggested learning path",
  "skill_gaps": ["skill1", "skill2"]
}""",
        f"Skills: {skills_str}\nCompleted: {completed_str}\nInterests: {interests_str}\nCareer goal: {req.career_goal or 'not specified'}\nLevel: {req.experience_level}",
        max_tokens=800,
    )
    if ai and "recommendations" in ai:
        return ai

    recs = []
    if req.experience_level == "beginner":
        recs = [
            {"title": "Introduction to Web Development", "reason": "Foundation for tech career", "priority": "high", "estimated_hours": 20, "level": "beginner"},
            {"title": "Git & Version Control", "reason": "Essential for all developers", "priority": "high", "estimated_hours": 8, "level": "beginner"},
        ]
    elif req.experience_level == "intermediate":
        recs = [
            {"title": "Advanced Backend Development", "reason": "Level up your server-side skills", "priority": "high", "estimated_hours": 30, "level": "intermediate"},
            {"title": "Database Design Patterns", "reason": "Optimize data architecture", "priority": "medium", "estimated_hours": 15, "level": "intermediate"},
        ]
    else:
        recs = [
            {"title": "System Design & Architecture", "reason": "Essential for senior roles", "priority": "high", "estimated_hours": 25, "level": "advanced"},
            {"title": "Cloud Infrastructure & DevOps", "reason": "Scale your deployments", "priority": "medium", "estimated_hours": 20, "level": "advanced"},
        ]
    return {"recommendations": recs, "learning_path": "Start with the high-priority courses, then progress to medium.", "skill_gaps": []}


@app.post("/formation/completion-predict")
async def predict_completion(req: CompletionPredictRequest):
    progress = req.progress_percent
    modules_ratio = req.modules_completed / max(req.total_modules, 1)
    avg_quiz = sum(req.quiz_scores) / max(len(req.quiz_scores), 1) if req.quiz_scores else 50.0

    base_likelihood = 0.0
    if progress >= 90:
        base_likelihood = 0.97
    elif progress >= 70:
        base_likelihood = 0.85
    elif progress >= 50:
        base_likelihood = 0.70
    elif progress >= 30:
        base_likelihood = 0.50
    elif progress >= 10:
        base_likelihood = 0.30
    else:
        base_likelihood = 0.15

    quiz_factor = 0.0
    if avg_quiz >= 80:
        quiz_factor = 0.10
    elif avg_quiz >= 60:
        quiz_factor = 0.05
    elif avg_quiz < 40:
        quiz_factor = -0.10

    freq_factor = 0.0
    if req.login_frequency >= 5:
        freq_factor = 0.08
    elif req.login_frequency >= 3:
        freq_factor = 0.04
    elif req.login_frequency < 1:
        freq_factor = -0.08

    pace_factor = 0.0
    if req.days_enrolled > 0 and modules_ratio > 0:
        daily_rate = modules_ratio / req.days_enrolled
        if daily_rate > 0.1:
            pace_factor = 0.05
        elif daily_rate < 0.02:
            pace_factor = -0.05

    likelihood = max(0.0, min(1.0, base_likelihood + quiz_factor + freq_factor + pace_factor))

    remaining_modules = max(0, req.total_modules - req.modules_completed)
    avg_time = req.avg_time_per_module if req.avg_time_per_module > 0 else 30
    estimated_minutes = remaining_modules * avg_time
    estimated_days = max(1, int(estimated_minutes / (req.login_frequency * 60))) if req.login_frequency > 0 else max(1, remaining_modules * 2)

    risk = "low"
    risk_reasons = []
    if likelihood < 0.4:
        risk = "high"
    elif likelihood < 0.65:
        risk = "medium"
    if avg_quiz < 50 and req.quiz_scores:
        risk_reasons.append("Low quiz scores — consider revisiting module materials")
    if req.login_frequency < 1:
        risk_reasons.append("Low login frequency — try setting a daily learning schedule")
    if req.days_enrolled > 30 and progress < 30:
        risk_reasons.append("Slow progress relative to enrollment duration")

    return {
        "completion_likelihood": round(likelihood, 2),
        "estimated_days_remaining": estimated_days,
        "estimated_minutes_remaining": estimated_minutes,
        "risk_level": risk,
        "risk_factors": risk_reasons,
        "avg_quiz_score": round(avg_quiz, 1),
        "modules_remaining": remaining_modules,
        "tips": [
            "Complete at least one module per session to maintain momentum",
            "Review quiz answers to reinforce learning",
            "Set a consistent daily study time",
        ] if risk != "low" else [],
    }


@app.post("/formation/personalized-recommend")
async def personalized_recommend(req: PersonalizedRecommendRequest):
    if not req.available_formations:
        return {"recommendations": [], "message": "No formations catalog provided."}

    formations_text = "\n".join(
        f"- ID:{f.get('id','?')} \"{f.get('title','?')}\" (level:{f.get('level','?')}, category:{f.get('category','?')}, rating:{f.get('rating',0)}, price:{f.get('price','free')})"
        for f in req.available_formations[:30]
    )

    ai = await ask_llm(
        """You are a personalized learning advisor for Skilora.
Given the user's profile and available courses, rank the top 5 most relevant courses.
Return JSON: {"ranked": [{"id": int, "score": 0-100, "reason": "why this course"}]}""",
        f"Skills: {', '.join(req.user_skills)}\nCompleted: {', '.join(req.completed_courses)}\nGoal: {req.career_goal}\nLevel: {req.experience_level}\n\nAvailable courses:\n{formations_text}",
        max_tokens=600,
    )
    if ai and "ranked" in ai:
        return ai

    scored = []
    for f in req.available_formations[:30]:
        score = 50
        f_level = (f.get("level") or "").lower()
        if f_level == req.experience_level.lower():
            score += 20
        title_lower = (f.get("title") or "").lower()
        for skill in req.user_skills:
            if skill.lower() in title_lower:
                score += 15
        if f.get("rating", 0) >= 4:
            score += 10
        scored.append({"id": f.get("id"), "score": min(100, score), "reason": "Keyword and level match"})
    scored.sort(key=lambda x: x["score"], reverse=True)
    return {"ranked": scored[:5]}


# ═══════════════════════════════════════════════════════════════
#  COMMUNITY
# ═══════════════════════════════════════════════════════════════

class ModerateRequest(BaseModel):
    content: str = ""
    context: Optional[str] = None

class ModerateImageRequest(BaseModel):
    image_base64: str = ""
    image_url: Optional[str] = None

class SentimentRequest(BaseModel):
    content: str = ""
    task: Optional[str] = None
    max_sentences: int = 3

class SummarizeMessagesRequest(BaseModel):
    messages: list[str] = []
    max_sentences: int = 3

class TranslateRequest(BaseModel):
    text: str = ""
    source_lang: str = "auto"
    target_lang: str = "en"

LLM_VISION_MODEL = os.getenv("LLM_VISION_MODEL", "llama-3.2-11b-vision-preview")


@app.post("/community/moderate")
async def moderate_content(req: ModerateRequest):
    flagged_words = ["spam", "scam", "hack", "attack", "exploit", "phishing"]
    text_lower = req.content.lower()
    flags = [w for w in flagged_words if w in text_lower]
    if flags:
        return {"safe": False, "flags": flags, "confidence": 0.8, "action": "review"}
    return {"safe": True, "flags": [], "confidence": 0.9, "action": "approve"}


@app.post("/community/moderate-image")
async def moderate_image(req: ModerateImageRequest):
    if not LLM_API_KEY:
        return {"safe": True, "reason": None, "confidence": 0.0, "note": "No API key configured"}

    image_content = None
    if req.image_base64:
        b64 = req.image_base64
        if not b64.startswith("data:"):
            b64 = f"data:image/jpeg;base64,{b64}"
        image_content = {"type": "image_url", "image_url": {"url": b64}}
    elif req.image_url:
        image_content = {"type": "image_url", "image_url": {"url": req.image_url}}
    else:
        return {"safe": True, "reason": None, "confidence": 0.0}

    try:
        async with httpx.AsyncClient(timeout=30) as client:
            resp = await client.post(
                LLM_API_URL,
                headers={"Authorization": f"Bearer {LLM_API_KEY}", "Content-Type": "application/json"},
                json={
                    "model": LLM_VISION_MODEL,
                    "messages": [
                        {"role": "system", "content": "You are a strict content moderation system for a professional platform. Analyze this image and determine if it contains NSFW content (nudity, sexual content, bikini/underwear, suggestive poses, violence, gore, drugs). Respond ONLY with valid JSON: {\"safe\": true/false, \"reason\": \"brief reason or null\", \"categories\": [\"nudity\",\"violence\",etc], \"confidence\": 0.0-1.0}. Be strict — any bikini, underwear, lingerie, or revealing clothing should be flagged as unsafe."},
                        {"role": "user", "content": [
                            {"type": "text", "text": "Analyze this image for NSFW content moderation:"},
                            image_content,
                        ]},
                    ],
                    "temperature": 0.1,
                    "max_tokens": 200,
                },
            )
            resp.raise_for_status()
            content = resp.json()["choices"][0]["message"]["content"]
            try:
                result = json.loads(content)
                return {
                    "safe": bool(result.get("safe", True)),
                    "reason": result.get("reason"),
                    "categories": result.get("categories", []),
                    "confidence": float(result.get("confidence", 0.5)),
                }
            except json.JSONDecodeError:
                low = content.lower()
                is_unsafe = any(w in low for w in ["unsafe", "nsfw", "nudity", "nude", "bikini", "sexual", "not safe"])
                return {"safe": not is_unsafe, "reason": content[:200], "categories": [], "confidence": 0.6}
    except Exception as e:
        print(f"Image moderation error: {e}")
        return {"safe": True, "reason": None, "confidence": 0.0, "error": str(e)}


@app.post("/community/summarize-messages")
async def summarize_messages(req: SummarizeMessagesRequest):
    if len(req.messages) < 2:
        return {"summary": None, "error": "Not enough messages"}
    text = "\n".join(req.messages[:50])
    ai = await ask_llm(
        f"Summarize this conversation in {req.max_sentences} concise sentences. Keep it neutral and informative. Respond in the same language as the messages. Return JSON: {{\"summary\": \"...\"}}",
        text,
        max_tokens=400,
    )
    if ai and "summary" in ai:
        return ai
    sentences = text.split(".")
    summary = ". ".join(s.strip() for s in sentences[:req.max_sentences] if s.strip())
    return {"summary": (summary + ".") if summary else text[:200]}


@app.post("/community/sentiment")
async def analyze_sentiment(req: SentimentRequest):
    if req.task == "summarize":
        ai = await ask_llm(
            f"Summarize this text in {req.max_sentences} sentences. Return JSON: {{summary: str}}",
            req.content[:2000],
        )
        if ai and "summary" in ai:
            return ai
        sentences = req.content.split(".")
        summary = ". ".join(s.strip() for s in sentences[:req.max_sentences] if s.strip())
        return {"summary": summary + "." if summary else req.content[:200]}

    positive = ["great", "love", "excellent", "amazing", "good", "helpful", "thanks", "awesome"]
    negative = ["bad", "terrible", "hate", "awful", "poor", "worst", "broken", "useless"]
    text_lower = req.content.lower()
    pos = sum(1 for w in positive if w in text_lower)
    neg = sum(1 for w in negative if w in text_lower)
    if pos > neg:
        return {"sentiment": "positive", "score": min(1.0, 0.5 + pos * 0.1), "confidence": 0.7}
    elif neg > pos:
        return {"sentiment": "negative", "score": max(0.0, 0.5 - neg * 0.1), "confidence": 0.7}
    return {"sentiment": "neutral", "score": 0.5, "confidence": 0.6}


@app.post("/community/translate")
async def translate_text(req: TranslateRequest):
    ai = await ask_llm(
        f"Translate the following text to {req.target_lang}. Return JSON: {{translated: str, source_lang: str}}",
        req.text,
    )
    if ai and "translated" in ai:
        return ai
    return {"translated": req.text, "source_lang": req.source_lang, "note": "Translation requires AI service."}


# ═══════════════════════════════════════════════════════════════
#  FINANCE
# ═══════════════════════════════════════════════════════════════

class AnomalyRequest(BaseModel):
    transactions: list[dict] = []
    user_id: int = 0

class SpendingRequest(BaseModel):
    transactions: list[dict] = []
    period: str = "month"


@app.post("/finance/anomaly-detect")
async def detect_anomalies(req: AnomalyRequest):
    anomalies = []
    for tx in req.transactions:
        amount = float(tx.get("amount", 0))
        if amount > 5000:
            anomalies.append({"transaction": tx, "reason": "Unusually high amount", "risk": "high"})
    return {"anomalies": anomalies, "total_checked": len(req.transactions), "risk_level": "high" if anomalies else "low"}


@app.post("/finance/spending-analysis")
async def analyze_spending(req: SpendingRequest):
    total = sum(float(tx.get("amount", 0)) for tx in req.transactions)
    categories = {}
    for tx in req.transactions:
        cat = tx.get("type", "other")
        categories[cat] = categories.get(cat, 0) + float(tx.get("amount", 0))
    return {"total": total, "by_category": categories, "period": req.period, "transaction_count": len(req.transactions)}


# ═══════════════════════════════════════════════════════════════

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
