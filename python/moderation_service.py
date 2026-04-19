"""
Content Moderation Microservice (Flask)
- POST /moderate/text   → OpenAI Moderation API
- POST /moderate/image  → NudeNet local model
"""

import os
import io
import tempfile
import requests
from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

# ── NudeNet (chargé une seule fois au démarrage) ──────────────────────────
try:
    from nudenet import NudeDetector
    detector = NudeDetector()
    NUDENET_AVAILABLE = True
    print("[INFO] NudeNet loaded successfully")
except Exception as e:
    detector = None
    NUDENET_AVAILABLE = False
    print(f"[WARN] NudeNet not available: {e}. Image moderation disabled.")

# Classes NSFW considérées comme +18
NSFW_CLASSES = {
    "FEMALE_BREAST_EXPOSED",
    "FEMALE_GENITALIA_EXPOSED",
    "MALE_GENITALIA_EXPOSED",
    "BUTTOCKS_EXPOSED",
    "ANUS_EXPOSED",
    "FEMALE_BREAST_COVERED",      # optionnel – retirez si trop strict
    "BELLY_EXPOSED",              # optionnel – retirez si trop strict
}

# Seuil de confiance minimum pour considérer une détection
NSFW_THRESHOLD = 0.45


# ── OpenAI Moderation API ─────────────────────────────────────────────────
OPENAI_API_KEY = os.environ.get("OPENAI_API_KEY", "")


@app.route("/moderate/text", methods=["POST"])
def moderate_text():
    """Analyse le texte via l'API OpenAI Moderation (gratuit)."""
    data = request.get_json(silent=True) or {}
    text = (data.get("text") or "").strip()

    if not text:
        return jsonify({"safe": True, "reason": None})

    # ── Si pas de clé OpenAI → fallback liste de mots ─────────────────
    if not OPENAI_API_KEY:
        return _fallback_text_moderation(text)

    try:
        resp = requests.post(
            "https://api.openai.com/v1/moderations",
            headers={
                "Authorization": f"Bearer {OPENAI_API_KEY}",
                "Content-Type": "application/json",
            },
            json={"input": text},
            timeout=10,
        )
        resp.raise_for_status()
        result = resp.json()["results"][0]

        if result["flagged"]:
            # Trouver la catégorie la plus haute
            scores = result["category_scores"]
            top_cat = max(scores, key=scores.get)
            return jsonify({
                "safe": False,
                "reason": f"Contenu inapproprié détecté ({top_cat})",
                "categories": {k: v for k, v in result["categories"].items() if v},
            })

        return jsonify({"safe": True, "reason": None})

    except Exception as e:
        # En cas d'erreur API → fallback
        print(f"[WARN] OpenAI API error: {e}")
        return _fallback_text_moderation(text)


def _fallback_text_moderation(text: str):
    """Fallback simple avec liste de mots interdits (FR + EN)."""
    import re
    
    # Mots qui doivent correspondre exactement (word boundary)
    bad_words_exact = [
        # Français
        "putain", "merde", "connard", "connasse", "salope", "enculé", "enculer",
        "nique", "niquer", "niqué", "ntm", "pute", "bordel", "bite", "couille",
        "couilles", "branleur", "branler", "foutre", "fdp", "tg", "pd",
        "salaud", "bâtard", "batard", "taré", "abruti", "dégueulasse",
        "chier", "chiasse", "cul", "trouduc", "encule",
        # Anglais
        "fuck", "fucked", "fucking", "fucker", "fck", "fuk",
        "shit", "shitty", "bullshit",
        "bitch", "asshole", "dick", "pussy",
        "nigger", "nigga", "faggot", "cunt", "whore", "bastard",
        "damn", "damnit", "motherfucker", "wtf", "stfu",
        # Sexuel explicite
        "porn", "porno", "xxx", "nude", "nudes", "naked", "hentai",
        "onlyfans", "nsfw",
    ]
    
    # Expressions qui peuvent apparaître comme substring
    bad_substrings = [
        "18+", "+18", "sex", 
    ]
    
    text_lower = text.lower()
    # Remove accents for matching
    text_normalized = text_lower
    for a, b in [("é", "e"), ("è", "e"), ("ê", "e"), ("ë", "e"),
                  ("à", "a"), ("â", "a"), ("ù", "u"), ("û", "u"),
                  ("î", "i"), ("ï", "i"), ("ô", "o"), ("ç", "c")]:
        text_normalized = text_normalized.replace(a, b)
    
    # Check exact word matches (with word boundaries)
    for word in bad_words_exact:
        pattern = r'\b' + re.escape(word) + r'\b'
        if re.search(pattern, text_lower) or re.search(pattern, text_normalized):
            return jsonify({
                "safe": False,
                "reason": f"Mot interdit détecté dans le texte",
            })
    
    # Check substrings
    for sub in bad_substrings:
        if sub in text_lower:
            return jsonify({
                "safe": False,
                "reason": f"Contenu inapproprié détecté dans le texte",
            })
    
    return jsonify({"safe": True, "reason": None})


@app.route("/moderate/image", methods=["POST"])
def moderate_image():
    """Analyse une image uploadée (fichier) via NudeNet local."""

    # ── Accepter un fichier uploadé ──
    file = request.files.get("file")
    if not file:
        return jsonify({"safe": True, "reason": None})

    if not NUDENET_AVAILABLE:
        return jsonify({"safe": True, "reason": None, "warning": "NudeNet not loaded, image moderation skipped"})

    try:
        content = file.read()

        # Limiter la taille à 10 Mo
        if len(content) > 10 * 1024 * 1024:
            return jsonify({"safe": False, "reason": "Image trop volumineuse (max 10 Mo)"})

        # Déterminer l'extension
        ext = os.path.splitext(file.filename or "")[1].lower() or ".jpg"
        if ext not in (".jpg", ".jpeg", ".png", ".gif", ".webp", ".bmp"):
            ext = ".jpg"

        # Écrire dans un fichier temp pour NudeNet
        with tempfile.NamedTemporaryFile(suffix=ext, delete=False) as tmp:
            tmp.write(content)
            tmp_path = tmp.name

        # Analyse NudeNet
        detections = detector.detect(tmp_path)

        # Nettoyage
        os.unlink(tmp_path)

        # Vérifier les détections NSFW
        nsfw_found = []
        for det in detections:
            label = det.get("class", "")
            score = det.get("score", 0)
            if label in NSFW_CLASSES and score >= NSFW_THRESHOLD:
                nsfw_found.append({"class": label, "score": round(score, 3)})

        if nsfw_found:
            return jsonify({
                "safe": False,
                "reason": "Contenu visuel inapproprié (+18) détecté",
                "detections": nsfw_found,
            })

        return jsonify({"safe": True, "reason": None})

    except Exception as e:
        print(f"[ERROR] Image moderation failed: {e}")
        return jsonify({
            "safe": True,
            "reason": f"Erreur modération image: {e}",
            "warning": True,
        })


@app.route("/health", methods=["GET"])
def health():
    services = ["text_moderation"]
    if NUDENET_AVAILABLE:
        services.append("image_moderation")
    return jsonify({"status": "ok", "services": services})


if __name__ == "__main__":
    port = int(os.environ.get("MODERATION_PORT", 5000))
    print(f"🛡️  Content Moderation Service starting on port {port}")
    print(f"   OpenAI API Key: {'configured' if OPENAI_API_KEY else 'NOT SET (using fallback)'}")
    print(f"   NudeNet: {'loaded' if NUDENET_AVAILABLE else 'NOT AVAILABLE'}")
    app.run(host="0.0.0.0", port=port, debug=False)
