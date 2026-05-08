---
description: How to design perfect Spatial UI / Neo-Glassmorphism like RonDesignLab
---

# 🚀 Spatial UI / Neo-Glassmorphism Design Standard

This document is the absolute source of truth for creating premium, spatial, "RonDesignLab" / "Apple Vision Pro" style dark mode UIs.

## 🧠 Why Does This Style Feel "Perfect"?
The secret to this aesthetic is **Materiality & Physicality**. It does not treat the screen as a 2D canvas of pixels. It treats it as a 3D physical environment.
- **Translucency over Color:** Elements aren't painted grey; they are painted white at 2% opacity. This allows the environment to "bleed" through everything.
- **Refraction & Edge Lighting:** Physical glass catches light on its edges. This UI simulates that using sub-pixel inner shadows and pseudo-element borders.
- **Depth of Field:** Backgrounds use heavy blur, while foreground data is impossibly crisp.
- **High-Fidelity Typography:** Extreme contrast in font weights and tracking creates a sense of scale and precision.

## 🎯 1. The Core Typography
To achieve the "premium" feel, you must use geometric or neo-grotesque sans-serif fonts precisely.
- **Fonts:** `SF Pro Display` (Apple), `Inter`, `Plus Jakarta Sans`, or `Geist`.
- **The "Data" Rule:** Numbers and primary values must be `font-light` or `font-medium` but massive in size, with `tracking-tight` or `tracking-tighter` (-0.02em to -0.04em).
- **The "Label" Rule:** Tiny, `text-[10px]` or `text-[12px]`, `uppercase`, `tracking-widest` (0.05em to 0.1em), and heavily muted (`text-white/40`).

## 🪟 2. The "Liquid Glass" Recipe (Advanced)
A simple `backdrop-blur` is not enough. You must combine multiple shadows to simulate the *thickness* of the glass and the *light hitting its edges*.

### Tailwind Implementation
```html
<div class="relative overflow-hidden rounded-[2rem] 
            bg-white/[0.02] backdrop-blur-[24px] saturate-[120%]
            border border-white/[0.04]
            shadow-[inset_0_1px_1px_rgba(255,255,255,0.1),0_8px_32px_rgba(0,0,0,0.4)]
            p-6">
    <!-- Liquid Edge Highlight (The Secret Sauce) -->
    <div class="absolute inset-0 rounded-[2rem] pointer-events-none 
                shadow-[inset_-10px_-8px_0px_-11px_rgba(255,255,255,0.15),inset_0px_-9px_0px_-8px_rgba(255,255,255,0.1)] 
                mix-blend-overlay"></div>
    
    <!-- Content goes here -->
</div>
```
*Why this works:* The outer `box-shadow` drops a dark shadow on the environment. The inner `box-shadow` simulates the top edge catching light. The `inset` shadow on the absolute div creates a thick "liquid" pool of light gathering at the bottom edge.

## � 3. The Environment (Backgrounds & Lighting)
The background is a void populated by light sources.
- **Base Color:** Deepest black (`bg-[#050505]` or `bg-zinc-950`).
- **Texture:** A subtle grid or noise overlay prevents color banding.
  ```html
  <div class="fixed inset-0 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[length:24px_24px] pointer-events-none"></div>
  ```
- **Ambient Light Orbs:** Soft, huge, highly-blurred orbs placed *behind* glass panels. They must use `mix-blend-screen` or `mix-blend-color-dodge` to interact properly with the glass.
  ```html
  <div class="absolute top-0 left-0 w-[500px] h-[500px] bg-emerald-500/10 blur-[120px] rounded-full pointer-events-none mix-blend-screen -z-10"></div>
  ```

## 🎨 4. Accents & Holographic Data
Data points are the light sources of the foreground.
- Use extreme, saturated neons (e.g., `#00FF66`, `#007AFF`).
- **Glowing Nodes:** Important nodes in a graph or status dots must cast their own light.
  ```html
  <span class="relative flex h-2.5 w-2.5">
    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.8)]"></span>
  </span>
  ```

## 📐 5. Rules of Execution
1. **Never use pure white (#FFFFFF) for backgrounds.** Maximum background lightness is `#111`.
2. **Never use solid colored borders.** Borders must always be translucent (`border-white/[0.05]`).
3. **Z-Index Matters.** Layer elements physically: Void -> Texture -> Ambient Glow -> Liquid Glass Panel -> Glowing Content.
4. **Padding & Whitespace.** Premium UI requires breathing room. Double your standard padding. If you usually use `p-4`, use `p-8`.
5. **Micro-interactions.** Panels should gently react to hover (`hover:bg-white/[0.04]`, slightly stronger border `hover:border-white/[0.08]`, and a subtle `transition-all duration-500 ease-out`).
