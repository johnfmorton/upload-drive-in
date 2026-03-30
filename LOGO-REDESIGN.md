# Logo Redesign Direction

## Brand

**Name:** Upload Drive-in
**Product:** A file-upload utility that lets business owners invite clients to upload files without friction (no size limits, notes can be attached, files go directly to cloud storage).
**Audience:** Business owners (admin) and their clients (visitors uploading files). The logo appears primarily in the navigation bar of a web app.

---

## Design System Context

The logo must work within an existing design system. These are the established design tokens:

### Typography
- **Display font:** Instrument Serif (400, 400i) — used for page-level headings at 24px+. Elegant, modern serif with genuine personality.
- **Body font:** DM Sans (400, 500, 600, 700) — clean geometric sans-serif for all UI text.
- The contrast between these two fonts defines the brand's typographic identity: sophisticated serif meets functional sans.

### Color Palette
| Token | Hex | Usage |
|---|---|---|
| warm-900 | `#1C1917` | Primary text, dark buttons |
| warm-800 | `#292524` | Hover states |
| warm-700 | `#57534E` | Secondary headings |
| warm-500 | `#A8A29E` | Muted text |
| warm-400 | `#C4BEB2` | Placeholder text, borders |
| cream-50 | `#FAFAF8` | Page backgrounds |
| cream-100 | `#F5F4F0` | Card backgrounds |
| cream-200 | `#ECEAE4` | Borders, dividers |
| **accent-500** | **`#B08D57`** | **Brass/gold accent** — the signature color. Used for decorative lines, active nav underlines, stat card borders, trust icons, and interactive links. |
| accent-600 | `#8B6F3A` | Accent hover/dark |

### Visual Signature Elements
- **Brass accent line:** A short horizontal line (`w-8 h-px`) in accent-500 appears above major headings as a decorative motif.
- **Uppercase-tracked micro-labels:** 10px, `letter-spacing: 0.15em`, used for metadata labels.
- **Rounded corners:** `border-radius: 1rem` (cards), `1.5rem` (larger containers).
- **Left-border accent:** Stat cards use a 4px left border in accent-500 as a visual marker.

### Aesthetic Direction
Modern, sleek, sophisticated. Think the elegance of a well-designed iPhone. Conveys trust and professionalism (clients upload potentially sensitive files) while feeling effortless and premium. Not corporate, not playful, not dark and moody. Intentional restraint with confident details.

---

## Current Logo (to replace)

The existing logo is a detailed, multicolor SVG (~1300x280px) featuring:
- A blue/green cloud icon with a green upward arrow
- The wordmark "Upload Drive-in" in a generic sans-serif
- Illustrative style with multiple colors (green, blue, white, gray)

**Problems with the current logo:**
1. The illustrative/clipart style clashes with the refined, minimal design system
2. Too many colors — the design system uses only warm neutrals + one brass accent
3. The cloud icon is generic (could be any cloud service)
4. The green/blue palette has no relationship to the warm stone/brass system
5. At nav-bar size (h-9, roughly 36px tall), the detail is lost
6. It looks like a SaaS startup logo from 2018, not a premium utility

---

## Logo Requirements

### Format & Sizes
- **Primary:** Horizontal lockup (icon + wordmark) for the navigation bar
- **Icon-only:** Square mark for favicons (16, 32, 96, 180, 192, 512px), app icons, and compact contexts
- **File formats:** SVG (primary), PNG at 2x for `public/images/app-icon.png` (the app loads this path)
- **Nav bar constraint:** Must read clearly at `h-9` (36px height) in the navigation

### Color Constraints
The logo must work in these contexts:
1. **On cream-50 background** (`#FAFAF8`) — the primary app background
2. **On white** (`#FFFFFF`) — inside cards and the guest layout
3. **On white with backdrop-blur** — the sticky navigation bar

Use only colors from the design system:
- `warm-900` (`#1C1917`) for primary forms
- `accent-500` (`#B08D57`) for an accent detail (optional but encouraged — ties the logo to the brass accent motif)
- A single-color version in `warm-900` must exist for simplified contexts

**Do not use:** Blue, green, indigo, bright orange, gradients, or any color outside the palette above.

### Typographic Direction
- The wordmark "Upload Drive-in" should reference the brand's typographic identity. Consider:
  - Instrument Serif for the wordmark (matches display headings) — either straight or italic
  - A serif/sans hybrid: "Upload" in DM Sans, "Drive-in" in Instrument Serif (or vice versa) to echo the system's typographic contrast
  - Custom lettering inspired by Instrument Serif's proportions
- The hyphen in "Drive-in" is part of the brand name; preserve it
- Letter-spacing should be intentional — tight tracking for large serif, wider for any uppercase elements

### Icon/Mark Direction
The icon should communicate "file upload" or "receiving files" without being literal (no generic cloud icon, no upward arrow cliche). Consider:
- An abstract letterform (monogram from U, D, or I)
- A geometric shape that suggests movement, transfer, or an opening/portal
- A mark that references the "drive-in" metaphor — the idea of a place you pull up to and hand something off
- Something that could stand alone as a favicon at 16px and still be recognizable

### Style
- **Minimal:** Few strokes, confident geometry. Should feel like it belongs next to the brass accent lines and rounded cards of the UI.
- **Not illustrative:** No literal depictions of clouds, arrows, or file icons. The old logo's problem was being too literal.
- **Weight:** Should feel substantial enough at 36px height but not heavy. Think Dieter Rams, not Paul Rand.
- **Timeless over trendy:** No 3D effects, no complex gradients, no shadow/glow. The logo should age like the serif/sans typography pairing — classically modern.

---

## Examples of the Right Feel (for reference, not to copy)

These brands share the aesthetic territory we're targeting:
- **Linear** (linear.app) — minimal geometric mark, confident wordmark
- **Notion** — simple icon that works at all sizes, clean type
- **Arc browser** — a single distinctive shape as the brand mark
- **Aesop** — serif wordmark, restrained palette, premium utility

The common thread: a mark that is *simple enough to draw from memory* paired with typography that has genuine personality.

---

## Deliverables

1. **Primary horizontal lockup** (SVG) — icon + "Upload Drive-in" wordmark
2. **Icon-only mark** (SVG) — for favicon and compact contexts
3. **Single-color version** in `warm-900` (`#1C1917`)
4. **PNG exports** of the horizontal lockup at 2x resolution for `public/images/app-icon.png`
5. **Favicon set:** 16x16, 32x32, 96x96 PNG, 180x180 Apple touch icon, and an SVG favicon

---

## Implementation Notes

- The app loads a custom logo from `public/images/app-icon.png` — if this file exists, it's used in the nav bar and home page. If not, the inline SVG fallback is used.
- The nav bar renders the logo at `class="block h-9 w-auto"` (36px height, auto width).
- The home page (guest layout) renders it at `class="w-auto h-10"` (40px height).
- The logo sits on a sticky nav with `bg-white/80 backdrop-blur-md` — test legibility against this.
