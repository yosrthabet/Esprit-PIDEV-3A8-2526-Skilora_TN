"""
Chat Summarization Microservice (Flask + Google Gemini API)
- POST /summarize  → Summarize a list of chat messages using Gemini AI
"""

import os
import time
import requests as http_requests
from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

# Configure Gemini API
GEMINI_API_KEY = os.environ.get("GEMINI_API_KEY", "")
if not GEMINI_API_KEY:
    print("[ERROR] GEMINI_API_KEY not set! Set it in .env or as environment variable.")

MODELS = ["gemini-2.5-flash", "gemini-2.0-flash", "gemini-2.0-flash-lite"]
print(f"[INFO] Gemini API configured with models: {MODELS}")

GEMINI_API_BASE = "https://generativelanguage.googleapis.com/v1/models"


def call_gemini(prompt, retries=2):
    """Try each model via REST v1 API, with retry on rate limit."""
    for model_name in MODELS:
        for attempt in range(retries + 1):
            try:
                url = f"{GEMINI_API_BASE}/{model_name}:generateContent?key={GEMINI_API_KEY}"
                payload = {
                    "contents": [{"parts": [{"text": prompt}]}],
                    "generationConfig": {"temperature": 0.7, "maxOutputTokens": 512}
                }
                resp = http_requests.post(url, json=payload, timeout=30)
                
                if resp.status_code == 200:
                    data = resp.json()
                    text = data["candidates"][0]["content"]["parts"][0]["text"]
                    return text.strip()
                elif resp.status_code == 429:
                    # Rate limited - check retry delay
                    err_data = resp.json().get("error", {})
                    retry_delay = 5
                    for d in err_data.get("details", []):
                        if d.get("@type", "").endswith("RetryInfo"):
                            delay_str = d.get("retryDelay", "5s").replace("s", "")
                            try:
                                retry_delay = min(int(float(delay_str)), 30)
                            except:
                                retry_delay = 5
                    
                    if attempt < retries:
                        print(f"[WARN] {model_name} rate limited, retrying in {retry_delay}s (attempt {attempt+1}/{retries})...")
                        time.sleep(retry_delay)
                        continue
                    else:
                        print(f"[WARN] {model_name} rate limited, trying next model...")
                        break
                else:
                    err_msg = resp.json().get("error", {}).get("message", resp.text[:200])
                    print(f"[ERROR] {model_name} failed ({resp.status_code}): {err_msg}")
                    break
                    
            except Exception as e:
                print(f"[ERROR] {model_name} exception: {e}")
                break
    return None


@app.route("/summarize", methods=["POST"])
def summarize():
    """
    Expects JSON:
    {
        "messages": [
            {"sender": "Alice", "body": "Hello!"},
            {"sender": "Bob", "body": "Hi, how are you?"},
            ...
        ],
        "lang": "fr"  // optional, default "fr"
    }
    Returns:
    {
        "summary": "Résumé de la conversation..."
    }
    """
    data = request.get_json(silent=True) or {}
    messages = data.get("messages", [])
    lang = data.get("lang", "fr")

    if not messages or len(messages) == 0:
        return jsonify({"error": "Aucun message à résumer."}), 400

    # Build conversation text from messages
    conversation_text = "\n".join(
        f"{msg.get('sender', 'Inconnu')}: {msg.get('body', '')}"
        for msg in messages
        if msg.get("body", "").strip()
    )

    if not conversation_text.strip():
        return jsonify({"error": "Aucun contenu textuel à résumer."}), 400

    # Truncate to model max input (~1024 tokens ≈ ~3000 chars for safety)
    max_chars = 3000
    if len(conversation_text) > max_chars:
        conversation_text = conversation_text[-max_chars:]

    try:
        prompt = f"""Tu es un assistant expert en résumé de conversations.
Analyse cette conversation et produis un résumé structuré en français.

Règles :
- Écris un résumé clair de 2-4 phrases maximum.
- Mentionne les participants par prénom.
- Identifie le sujet principal, les décisions prises et les actions à faire.
- Ne commence PAS par "Résumé :" ni par un emoji.
- Utilise un ton professionnel mais naturel.
- Si la conversation est courte ou triviale, fais un résumé d'une seule phrase.

Conversation :
{conversation_text}"""

        summary = call_gemini(prompt)

        if summary is None:
            return jsonify({"error": "Quota API épuisé. Réessayez dans quelques minutes."}), 429

        return jsonify({"summary": summary})

    except Exception as e:
        print(f"[ERROR] Summarization failed: {e}")
        return jsonify({"error": "Erreur lors du résumé. Réessayez plus tard."}), 500


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok", "model": "gemini-2.0-flash"})


if __name__ == "__main__":
    port = int(os.environ.get("SUMMARIZE_PORT", 5001))
    print(f"[INFO] Summarization service running on port {port}")
    app.run(host="0.0.0.0", port=port, debug=False)
