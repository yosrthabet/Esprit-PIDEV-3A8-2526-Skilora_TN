/**
 * Skilora Hand Gesture Controller
 * Real-time hand tracking & gesture recognition using Google MediaPipe Hands
 *
 * Gestures:
 *   ☝️  POINT     → Index finger only   → move virtual cursor
 *   🤏  PINCH     → Thumb meets index   → click at cursor position
 *   ✋  PALM      → All fingers open    → scroll (hand up/down)
 *   ✊  FIST      → All fingers closed  → pause gesture tracking
 *   ✌️  PEACE     → V-sign              → zoom (spread/close fingers)
 *   👍  THUMBS_UP → Thumb up only       → confirm / approve
 *   👋  SWIPE     → Fast palm movement  → navigate back/forward
 */
(function () {
    'use strict';

    /* ════════════════════════════════════════════════════════
       LANDMARK INDICES (MediaPipe 21-point hand model)
       ════════════════════════════════════════════════════════ */
    const LM = {
        WRIST: 0,
        THUMB_CMC: 1, THUMB_MCP: 2, THUMB_IP: 3, THUMB_TIP: 4,
        INDEX_MCP: 5, INDEX_PIP: 6, INDEX_DIP: 7, INDEX_TIP: 8,
        MIDDLE_MCP: 9, MIDDLE_PIP: 10, MIDDLE_DIP: 11, MIDDLE_TIP: 12,
        RING_MCP: 13, RING_PIP: 14, RING_DIP: 15, RING_TIP: 16,
        PINKY_MCP: 17, PINKY_PIP: 18, PINKY_DIP: 19, PINKY_TIP: 20
    };

    /* ════════════════════════════════════════════════════════
       GESTURE TYPES
       ════════════════════════════════════════════════════════ */
    const G = {
        NONE: 'none',
        POINT: 'point',
        PINCH: 'pinch',
        PALM: 'palm',
        FIST: 'fist',
        PEACE: 'peace',
        THUMBS_UP: 'thumbs_up',
        SWIPE_LEFT: 'swipe_left',
        SWIPE_RIGHT: 'swipe_right'
    };

    /* ════════════════════════════════════════════════════════
       DEFAULT CONFIGURATION
       ════════════════════════════════════════════════════════ */
    const DEFAULT_CONFIG = {
        maxHands: 1,
        modelComplexity: 1,
        minDetectionConfidence: 0.7,
        minTrackingConfidence: 0.5,
        pinchThreshold: 0.11,
        pinchReleaseThreshold: 0.15,
        pinchFramesRequired: 1,
        scrollSpeed: 18,
        swipeThreshold: 0.14,
        swipeTimeWindow: 350,
        cursorSmoothing: 0.25,
        clickCooldown: 300,
        zoomSensitivity: 1.5,
        fps: 24
    };

    /* ════════════════════════════════════════════════════════
       UTILITIES
       ════════════════════════════════════════════════════════ */
    function dist(a, b) {
        return Math.sqrt((a.x - b.x) ** 2 + (a.y - b.y) ** 2);
    }

    function lerp(a, b, t) {
        return a + (b - a) * t;
    }

    function injectScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector('script[src="' + src + '"]')) { resolve(); return; }
            const s = document.createElement('script');
            s.src = src;
            s.crossOrigin = 'anonymous';
            s.onload = resolve;
            s.onerror = () => reject(new Error('Failed to load: ' + src));
            document.head.appendChild(s);
        });
    }

    /* ════════════════════════════════════════════════════════
       GESTURE ANALYZER — Converts landmarks → gesture type
       ════════════════════════════════════════════════════════ */
    class GestureAnalyzer {
        constructor(cfg) {
            this.cfg = cfg;
            this.prevWrist = null;
            this.prevWristTime = 0;
            this.prevPeaceDist = null;
            this.pinchFrameCount = 0;
            this.wasPinching = false;
            this.prevGesture = G.NONE;
        }

        isFingerExtended(lm, tipIdx, pipIdx) {
            return dist(lm[tipIdx], lm[LM.WRIST]) > dist(lm[pipIdx], lm[LM.WRIST]) * 0.85;
        }

        isThumbExtended(lm) {
            return dist(lm[LM.THUMB_TIP], lm[LM.INDEX_MCP]) >
                   dist(lm[LM.THUMB_IP], lm[LM.INDEX_MCP]) * 1.1;
        }

        fingers(lm) {
            return {
                thumb:  this.isThumbExtended(lm),
                index:  this.isFingerExtended(lm, LM.INDEX_TIP,  LM.INDEX_PIP),
                middle: this.isFingerExtended(lm, LM.MIDDLE_TIP, LM.MIDDLE_PIP),
                ring:   this.isFingerExtended(lm, LM.RING_TIP,   LM.RING_PIP),
                pinky:  this.isFingerExtended(lm, LM.PINKY_TIP,  LM.PINKY_PIP)
            };
        }

        analyze(lm) {
            const f = this.fingers(lm);
            const now = Date.now();
            const ext = [f.thumb, f.index, f.middle, f.ring, f.pinky].filter(Boolean).length;

            /* Pinch — thumb tip near index tip
               Fires INSTANTLY when distance is below threshold.
               Ignores pinch during palm (4+ fingers extended). */
            const pinchDist = dist(lm[LM.THUMB_TIP], lm[LM.INDEX_TIP]);
            const isPalmOpen = ext >= 4;

            if (pinchDist < this.cfg.pinchThreshold && !isPalmOpen) {
                this.prevPeaceDist = null;
                return {
                    gesture: G.PINCH, fingers: f, landmarks: lm, pinchDist,
                    cursorX: lm[LM.INDEX_TIP].x,
                    cursorY: lm[LM.INDEX_TIP].y
                };
            }

            /* Fist — nothing extended */
            if (ext === 0) {
                this.prevPeaceDist = null;
                this.prevWrist = null;
                return { gesture: G.FIST, fingers: f, landmarks: lm };
            }

            /* Thumbs up — only thumb, pointing upward */
            if (f.thumb && !f.index && !f.middle && !f.ring && !f.pinky) {
                if (lm[LM.THUMB_TIP].y < lm[LM.WRIST].y - 0.08) {
                    this.prevPeaceDist = null;
                    return { gesture: G.THUMBS_UP, fingers: f, landmarks: lm };
                }
            }

            /* Point — only index extended */
            if (f.index && !f.middle && !f.ring && !f.pinky) {
                this.prevPeaceDist = null;
                return {
                    gesture: G.POINT, fingers: f, landmarks: lm,
                    cursorX: lm[LM.INDEX_TIP].x,
                    cursorY: lm[LM.INDEX_TIP].y
                };
            }

            /* Peace / V-sign — index + middle only */
            if (f.index && f.middle && !f.ring && !f.pinky) {
                const pd = dist(lm[LM.INDEX_TIP], lm[LM.MIDDLE_TIP]);
                const zoomDelta = this.prevPeaceDist !== null ? pd - this.prevPeaceDist : 0;
                this.prevPeaceDist = pd;
                return { gesture: G.PEACE, fingers: f, landmarks: lm, peaceDist: pd, zoomDelta };
            }
            this.prevPeaceDist = null;

            /* Palm — 4+ fingers extended → scroll or swipe */
            if (ext >= 4) {
                const wrist = lm[LM.WRIST];
                if (this.prevWrist && (now - this.prevWristTime) < this.cfg.swipeTimeWindow) {
                    const dx = wrist.x - this.prevWrist.x;
                    if (Math.abs(dx) > this.cfg.swipeThreshold) {
                        this.prevWrist = { x: wrist.x, y: wrist.y };
                        this.prevWristTime = now;
                        return { gesture: dx > 0 ? G.SWIPE_RIGHT : G.SWIPE_LEFT, fingers: f, landmarks: lm };
                    }
                }
                this.prevWrist = { x: wrist.x, y: wrist.y };
                this.prevWristTime = now;
                return { gesture: G.PALM, fingers: f, landmarks: lm, palmY: wrist.y, palmX: wrist.x };
            }

            return { gesture: G.NONE, fingers: f, landmarks: lm };
        }
    }

    /* ════════════════════════════════════════════════════════
       ACTION MAPPER — Converts gestures → DOM interactions
       ════════════════════════════════════════════════════════ */
    class ActionMapper {
        constructor(cfg) {
            this.cfg = cfg;
            this.cursorX = window.innerWidth / 2;
            this.cursorY = window.innerHeight / 2;
            this.lastClickTime = 0;
            this.prevPalmY = null;
            this.scrollAcc = 0;
        }

        execute(result, cursorEl) {
            switch (result.gesture) {
                case G.POINT:   return this._point(result, cursorEl);
                case G.PINCH:   return this._pinch(result, cursorEl);
                case G.PALM:    return this._scroll(result);
                case G.PEACE:   return this._zoom(result);
                case G.SWIPE_LEFT:  window.history.back();    return true;
                case G.SWIPE_RIGHT: window.history.forward(); return true;
                case G.THUMBS_UP:
                    document.dispatchEvent(new CustomEvent('gesture:thumbsup'));
                    return true;
                case G.FIST:
                    this.prevPalmY = null;
                    if (cursorEl) cursorEl.style.opacity = '0.3';
                    return true;
                default:
                    return false;
            }
        }

        _point(result, cursorEl) {
            const tx = (1 - result.cursorX) * window.innerWidth;
            const ty = result.cursorY * window.innerHeight;
            this.cursorX = lerp(this.cursorX, tx, this.cfg.cursorSmoothing);
            this.cursorY = lerp(this.cursorY, ty, this.cfg.cursorSmoothing);
            if (cursorEl) {
                cursorEl.style.transform = 'translate(' + this.cursorX + 'px,' + this.cursorY + 'px)';
                cursorEl.style.opacity = '1';
            }
            return true;
        }

        _pinch(result, cursorEl) {
            /* Update cursor position during pinch */
            if (result.cursorX !== undefined) {
                const tx = (1 - result.cursorX) * window.innerWidth;
                const ty = result.cursorY * window.innerHeight;
                this.cursorX = lerp(this.cursorX, tx, this.cfg.cursorSmoothing);
                this.cursorY = lerp(this.cursorY, ty, this.cfg.cursorSmoothing);
                if (cursorEl) {
                    cursorEl.style.transform = 'translate(' + this.cursorX + 'px,' + this.cursorY + 'px)';
                    cursorEl.style.opacity = '1';
                }
            }

            /* Cooldown — one click per 300ms */
            const now = Date.now();
            if (now - this.lastClickTime < this.cfg.clickCooldown) return true;
            this.lastClickTime = now;

            /* Visual feedback */
            if (cursorEl) {
                cursorEl.classList.add('gesture-cursor--click');
                setTimeout(() => cursorEl.classList.remove('gesture-cursor--click'), 250);
            }

            /* Fire full mouse event chain */
            const el = document.elementFromPoint(this.cursorX, this.cursorY);
            if (el) {
                const opts = { bubbles: true, cancelable: true, clientX: this.cursorX, clientY: this.cursorY };
                el.dispatchEvent(new MouseEvent('mousedown', opts));
                el.dispatchEvent(new MouseEvent('mouseup', opts));
                el.dispatchEvent(new MouseEvent('click', opts));
                return true;
            }
            return false;
        }

        _scroll(result) {
            if (this.prevPalmY !== null) {
                const dy = result.palmY - this.prevPalmY;
                this.scrollAcc += dy * this.cfg.scrollSpeed * 80;
                if (Math.abs(this.scrollAcc) > 2) {
                    window.scrollBy({ top: this.scrollAcc, behavior: 'auto' });
                    this.scrollAcc = 0;
                }
            }
            this.prevPalmY = result.palmY;
            return true;
        }

        _zoom(result) {
            if (result.zoomDelta && Math.abs(result.zoomDelta) > 0.004) {
                const delta = result.zoomDelta * this.cfg.zoomSensitivity;
                const cur = parseFloat(document.body.style.zoom || '1');
                document.body.style.zoom = Math.max(0.5, Math.min(2.0, cur + delta));
            }
            return true;
        }
    }

    /* ════════════════════════════════════════════════════════
       OVERLAY RENDERER — Draws hand skeleton on canvas
       ════════════════════════════════════════════════════════ */
    class OverlayRenderer {
        constructor(canvas) {
            this.canvas = canvas;
            this.ctx = canvas.getContext('2d');
        }

        resize(w, h) { this.canvas.width = w; this.canvas.height = h; }

        clear() { this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height); }

        draw(landmarks) {
            this.clear();
            if (!landmarks) return;

            const ctx = this.ctx;
            const w = this.canvas.width;
            const h = this.canvas.height;

            /* Draw connections */
            if (window.HAND_CONNECTIONS && window.drawConnectors) {
                window.drawConnectors(ctx, landmarks, window.HAND_CONNECTIONS, {
                    color: 'rgba(99,102,241,0.5)', lineWidth: 2
                });
            } else {
                /* Fallback: draw lines manually */
                const pairs = [
                    [0,1],[1,2],[2,3],[3,4],
                    [0,5],[5,6],[6,7],[7,8],
                    [0,9],[9,10],[10,11],[11,12],
                    [0,13],[13,14],[14,15],[15,16],
                    [0,17],[17,18],[18,19],[19,20],
                    [5,9],[9,13],[13,17]
                ];
                ctx.strokeStyle = 'rgba(99,102,241,0.5)';
                ctx.lineWidth = 2;
                for (const [a, b] of pairs) {
                    ctx.beginPath();
                    ctx.moveTo(landmarks[a].x * w, landmarks[a].y * h);
                    ctx.lineTo(landmarks[b].x * w, landmarks[b].y * h);
                    ctx.stroke();
                }
            }

            /* Draw landmark dots */
            if (window.drawLandmarks) {
                window.drawLandmarks(ctx, landmarks, {
                    color: 'rgba(168,85,247,0.7)', lineWidth: 1, radius: 3
                });
                const tips = [LM.THUMB_TIP, LM.INDEX_TIP, LM.MIDDLE_TIP, LM.RING_TIP, LM.PINKY_TIP];
                window.drawLandmarks(ctx, tips.map(i => landmarks[i]), {
                    color: '#22d3ee', lineWidth: 1, radius: 5
                });
            } else {
                /* Fallback dots */
                for (let i = 0; i < landmarks.length; i++) {
                    const tips = [4, 8, 12, 16, 20];
                    ctx.beginPath();
                    ctx.arc(landmarks[i].x * w, landmarks[i].y * h, tips.includes(i) ? 5 : 3, 0, Math.PI * 2);
                    ctx.fillStyle = tips.includes(i) ? '#22d3ee' : 'rgba(168,85,247,0.7)';
                    ctx.fill();
                }
            }
        }
    }

    /* ════════════════════════════════════════════════════════
       GESTURE LABELS — Icon / label / color for each gesture
       ════════════════════════════════════════════════════════ */
    const GESTURE_META = {
        [G.NONE]:        { icon: '—',  label: 'Waiting…',     color: '#64748b' },
        [G.POINT]:       { icon: '☝️', label: 'Pointing',     color: '#3b82f6' },
        [G.PINCH]:       { icon: '🤏', label: 'Click!',       color: '#ef4444' },
        [G.PALM]:        { icon: '✋', label: 'Scrolling',    color: '#22c55e' },
        [G.FIST]:        { icon: '✊', label: 'Paused',       color: '#f59e0b' },
        [G.PEACE]:       { icon: '✌️', label: 'Zoom',         color: '#a855f7' },
        [G.THUMBS_UP]:   { icon: '👍', label: 'Confirmed!',   color: '#10b981' },
        [G.SWIPE_LEFT]:  { icon: '👈', label: 'Back',         color: '#6366f1' },
        [G.SWIPE_RIGHT]: { icon: '👉', label: 'Forward',      color: '#6366f1' }
    };

    /* ════════════════════════════════════════════════════════
       MAIN CONTROLLER — Orchestrates everything
       ════════════════════════════════════════════════════════ */
    window.SkiloraGesture = {
        _loaded: false,
        _active: false,
        _hands: null,
        _camera: null,
        _analyzer: null,
        _mapper: null,
        _overlay: null,
        _lastFrame: 0,
        config: Object.assign({}, DEFAULT_CONFIG),
        G: G,
        META: GESTURE_META,

        async loadDependencies() {
            if (this._loaded) return;
            const cdn = 'https://cdn.jsdelivr.net/npm/@mediapipe';
            await Promise.all([
                injectScript(cdn + '/hands@0.4.1675469240/hands.js'),
                injectScript(cdn + '/camera_utils@0.3.1675466862/camera_utils.js'),
                injectScript(cdn + '/drawing_utils@0.3.1675466124/drawing_utils.js')
            ]);
            this._loaded = true;
        },

        async start(videoEl, canvasEl, cursorEl, onGesture) {
            await this.loadDependencies();

            this._analyzer = new GestureAnalyzer(this.config);
            this._mapper   = new ActionMapper(this.config);
            this._overlay  = new OverlayRenderer(canvasEl);

            this._hands = new window.Hands({
                locateFile: (f) => 'https://cdn.jsdelivr.net/npm/@mediapipe/hands@0.4.1675469240/' + f
            });
            this._hands.setOptions({
                maxNumHands: this.config.maxHands,
                modelComplexity: this.config.modelComplexity,
                minDetectionConfidence: this.config.minDetectionConfidence,
                minTrackingConfidence: this.config.minTrackingConfidence
            });

            this._hands.onResults((results) => {
                this._overlay.resize(videoEl.videoWidth, videoEl.videoHeight);

                if (results.multiHandLandmarks && results.multiHandLandmarks.length > 0) {
                    const lm = results.multiHandLandmarks[0];
                    this._overlay.draw(lm);

                    const gestureResult = this._analyzer.analyze(lm);
                    this._mapper.execute(gestureResult, cursorEl);
                    if (onGesture) onGesture(gestureResult);
                } else {
                    this._overlay.clear();
                    if (cursorEl) cursorEl.style.opacity = '0';
                    if (onGesture) onGesture({ gesture: G.NONE });
                }
            });

            this._camera = new window.Camera(videoEl, {
                onFrame: async () => {
                    const now = Date.now();
                    if (now - this._lastFrame < 1000 / this.config.fps) return;
                    this._lastFrame = now;
                    await this._hands.send({ image: videoEl });
                },
                width: 640,
                height: 480
            });

            await this._camera.start();
            this._active = true;
        },

        stop() {
            if (this._camera) { try { this._camera.stop(); } catch(_) {} }
            if (this._overlay) this._overlay.clear();
            /* Release camera tracks so next page can access camera */
            var videoEl = document.getElementById('gesture-video');
            if (videoEl && videoEl.srcObject) {
                videoEl.srcObject.getTracks().forEach(function(t) { t.stop(); });
            }
            this._active = false;
        },

        /* ════════════════════════════════════════════════════════
           STANDBY MODE — Low-power gesture activation detector
           Runs camera at ~3 FPS looking for open palm held 1.5s
           to activate, and fist held 1.5s to deactivate.
           ════════════════════════════════════════════════════════ */
        _standby: false,
        _standbyHands: null,
        _standbyCamera: null,
        _standbyVideo: null,
        _activationStart: 0,
        _deactivationStart: 0,
        _standbyAnalyzer: null,
        _onStandbyActivate: null,
        _onStandbyDeactivate: null,
        _onStandbyStatus: null,

        async startStandby(options) {
            if (this._standby) return;

            this._onStandbyActivate   = options.onActivate   || null;
            this._onStandbyDeactivate = options.onDeactivate || null;
            this._onStandbyStatus     = options.onStatus     || null;

            await this.loadDependencies();

            this._standbyAnalyzer = new GestureAnalyzer(this.config);

            /* Create a hidden video element for standby camera */
            if (!this._standbyVideo) {
                this._standbyVideo = document.createElement('video');
                this._standbyVideo.setAttribute('playsinline', '');
                this._standbyVideo.setAttribute('autoplay', '');
                this._standbyVideo.muted = true;
                this._standbyVideo.style.cssText = 'position:fixed;width:1px;height:1px;opacity:0;pointer-events:none;z-index:-1;';
                document.body.appendChild(this._standbyVideo);
            }

            this._standbyHands = new window.Hands({
                locateFile: (f) => 'https://cdn.jsdelivr.net/npm/@mediapipe/hands@0.4.1675469240/' + f
            });
            this._standbyHands.setOptions({
                maxNumHands: 1,
                modelComplexity: 0,  /* Lite model for standby */
                minDetectionConfidence: 0.6,
                minTrackingConfidence: 0.4
            });

            var self = this;
            var lastStandbyFrame = 0;
            var STANDBY_FPS = 3;
            var HOLD_TIME = 1500; /* ms to hold gesture for activation */

            this._standbyHands.onResults(function(results) {
                if (!self._standby) return;

                if (results.multiHandLandmarks && results.multiHandLandmarks.length > 0) {
                    var lm = results.multiHandLandmarks[0];
                    var result = self._standbyAnalyzer.analyze(lm);
                    var now = Date.now();

                    if (!self._active) {
                        /* STANDBY → looking for PALM to activate */
                        if (result.gesture === G.PALM) {
                            if (self._activationStart === 0) self._activationStart = now;
                            var progress = Math.min(1, (now - self._activationStart) / HOLD_TIME);
                            if (self._onStandbyStatus) self._onStandbyStatus('activating', progress);
                            if (progress >= 1) {
                                self._activationStart = 0;
                                if (self._onStandbyActivate) self._onStandbyActivate();
                            }
                        } else {
                            if (self._activationStart !== 0 && self._onStandbyStatus) {
                                self._onStandbyStatus('standby', 0);
                            }
                            self._activationStart = 0;
                        }
                    } else {
                        /* ACTIVE → looking for FIST to deactivate */
                        if (result.gesture === G.FIST) {
                            if (self._deactivationStart === 0) self._deactivationStart = now;
                            var dprogress = Math.min(1, (now - self._deactivationStart) / HOLD_TIME);
                            if (self._onStandbyStatus) self._onStandbyStatus('deactivating', dprogress);
                            if (dprogress >= 1) {
                                self._deactivationStart = 0;
                                if (self._onStandbyDeactivate) self._onStandbyDeactivate();
                            }
                        } else {
                            self._deactivationStart = 0;
                        }
                    }
                } else {
                    /* No hand detected */
                    self._activationStart = 0;
                    self._deactivationStart = 0;
                    if (!self._active && self._onStandbyStatus) {
                        self._onStandbyStatus('standby', 0);
                    }
                }
            });

            this._standbyCamera = new window.Camera(this._standbyVideo, {
                onFrame: async function() {
                    var now = Date.now();
                    if (now - lastStandbyFrame < 1000 / STANDBY_FPS) return;
                    lastStandbyFrame = now;
                    if (self._standbyHands && self._standby) {
                        await self._standbyHands.send({ image: self._standbyVideo });
                    }
                },
                width: 320,
                height: 240
            });

            await this._standbyCamera.start();
            this._standby = true;
            if (this._onStandbyStatus) this._onStandbyStatus('standby', 0);
        },

        stopStandby() {
            this._standby = false;
            if (this._standbyCamera) { try { this._standbyCamera.stop(); } catch(_) {} this._standbyCamera = null; }
            if (this._standbyHands)  { try { this._standbyHands.close(); } catch(_) {} this._standbyHands = null; }
            if (this._standbyVideo && this._standbyVideo.parentNode) {
                /* Stop any active tracks before removing */
                if (this._standbyVideo.srcObject) {
                    this._standbyVideo.srcObject.getTracks().forEach(function(t) { t.stop(); });
                }
                this._standbyVideo.parentNode.removeChild(this._standbyVideo);
                this._standbyVideo = null;
            }
            this._activationStart = 0;
            this._deactivationStart = 0;
        },

        pauseStandbyDetection() {
            /* Temporarily pause standby detection while full tracking is active */
            this._activationStart = 0;
        },

        resumeStandbyDetection() {
            this._deactivationStart = 0;
        }
    };

})();
