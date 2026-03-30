# Design Evaluation Criteria

> Copy this file into your target repo, rename it `design-criteria.md`,
> and fill in the Site Context section before starting a Claude Code session.
> The four scoring criteria below are repo-agnostic — do not change them.

---

## Site Context

**Site:** [[URL or local name]](https://upload-drive-in.ddev.site/)

**Owner/Brand:** Upload Drive-In

**Audience:** The most important pages are the home page, which clients will see, and the upload page, which you can see by logging in with this link https://upload-drive-in.ddev.site/login/token/4?expires=1775479185&signature=dee35d460119d78012091de45163c17cef190b1c834361630767f345df2216de. by the way, this link logs you in as a client user.

**Differentiator:** This app is a uniquely useful utility for a business owner or person to invite clients to upload a file or collection of files to the business owner by circumventing the typical problems associated with uploading file, such as size limits and the inability to attache notes to files as they are uploaded.

**Aesthetic direction:** Modern, sleek, sophisticated

**What to avoid: "don't go dark and moody," "avoid corporate blue," "not playful.", think the elegance of a well designed iPhone

---

## Scoring Criteria

### 1. Design Quality (HIGH WEIGHT — score /10)

Does the design feel like a coherent whole with a distinct identity —
not a collection of parts? Colors, typography, layout, and imagery should
combine into a recognizable mood. Ask: would someone screenshot this and
share it as an example of good design?

Generic = automatic fail. Museum quality = the target.

### 2. Originality (HIGH WEIGHT — score /10)

Evidence of deliberate, custom creative choices. A human designer should
recognize intentional decisions, not defaults.

**Penalize immediately:**
- Purple or blue gradients over white cards
- Neutral gray card grids
- Numbered service lists (01 02 03 04)
- Default Tailwind utility patterns used without customization
- Generic sans-serif fonts: Inter, Roboto, Arial, system-ui
- Hero layout: text left, image right, CTA button below
- Anything a Bootstrap or Tailwind starter theme produces unmodified

**Award points for:**
- Unexpected layout logic: asymmetry, overlap, grid-breaking elements
- A font pairing with genuine personality
- Visual details that reward closer inspection
- A color palette that is limited and intentional, not "whatever works"
- Something that would make a designer stop scrolling

### 3. Craft (score /10)

Technical execution of the design fundamentals:
- Typography hierarchy: clear, intentional type scale with personality
- Spacing: consistent rhythm, not arbitrary padding values
- Color: limited palette applied with discipline
- Contrast ratios that meet WCAG AA minimums
- Responsive behavior that preserves the aesthetic on mobile

### 4. Functionality (score /10)

Can a first-time visitor answer these in under 5 seconds:
- Who is this person or company?
- What do they offer?
- What should I do next?

Can they navigate, find work samples or key content, and contact without
hunting?

---

## Scoring Thresholds

| Criterion | Threshold | Action if below |
|---|---|---|
| Design Quality | 7/10 | Mandatory revision or full aesthetic pivot |
| Originality | 7/10 | Mandatory revision or full aesthetic pivot |
| Craft | 6/10 | Fix fundamentals before continuing |
| Functionality | 6/10 | Stop aesthetics work, fix usability first |

**Do not just tweak.** If Design Quality or Originality fall below threshold,
make a real strategic change — different fonts, different palette, different
layout logic — not a minor adjustment to what is already there.
