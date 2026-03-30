# Design Log

## Iteration 1 — 2026-03-30
**Strategy:** Replaced Outfit with Instrument Serif for display headings to create a serif/sans contrast pairing. Shifted the color palette from warm cream/orange to refined stone neutrals with brass/gold accent (#B08D57). Redesigned the home page to include trust indicators (Encrypted, Secure, No Size Limit). Updated the upload page dropzone with an icon, rounded corners, and warmer input styling. Buttons changed from brand-color to warm-900 (near-black) for a more premium feel.
**Design Quality:** 5/10
**Originality:** 5/10
**Craft:** 6/10
**Functionality:** 7/10
**Notes:** The Instrument Serif heading immediately gives the page more personality than the previous DM Sans/Outfit combo. Trust indicators are a good addition. However, the overall layout is still a generic centered card on a flat background — there's nothing here that would make a designer stop scrolling. The page feels empty and underwhelming despite the improved typography. The color palette shift is subtle but good — less beige, more stone.
**Next move:** Pivot — the centered-card-on-empty-background layout is fundamentally limiting. Need to rethink the page structure: consider a split layout, add visual texture or geometric elements, use the full viewport more intentionally. The typography foundation is good; the layout and visual interest need a complete rethink.

## Iteration 2 — 2026-03-30
**Strategy:** Pivoted the guest layout to add subtle radial gradient accents on the background for visual depth. Enlarged the card container (max-w-lg), added backdrop-blur and translucent white bg. Home page heading enlarged to text-5xl with a brass accent line divider. Trust indicators redesigned with circular icon containers. Nav active state now uses brass accent underline. Labels switched to uppercase-tracked style. Page headers now use serif display font.
**Design Quality:** 6/10
**Originality:** 6/10
**Craft:** 7/10
**Functionality:** 8/10
**Notes:** Clear improvement from Iteration 1. The home page now has genuine presence — the large serif heading combined with the brass line and circular trust icons creates a more premium feel. The glass-morphism card with backdrop blur is subtle but effective. However, the dashboard and upload pages are still essentially unchanged structurally — standard white cards. The home page, while better, still follows a predictable centered-card paradigm. Need to push the envelope further on layout originality.
**Next move:** Pivot the interior pages (dashboard, upload). The upload page is the most important client-facing page — it needs to feel effortless and premium. Consider: tighter content width, stronger visual hierarchy, the dropzone should feel like an invitation rather than a technical component. Dashboard stat cards need more personality. Also need to add visual texture or geometric elements to interior pages, not just the guest layout.

## Iteration 3 — 2026-03-30
**Strategy:** Major overhaul of interior pages. Dashboard simplified — removed redundant inner heading, narrowed to max-w-5xl. Stat cards redesigned with left-border brass accent bars, serif numbers, and uppercase-tracked micro-labels. Recent uploads table restyled with warm borders and serif section heading. Upload page completely rebuilt: narrowed to max-w-3xl, added decorative brass line above heading, larger serif heading, spacious dropzone (p-12) with rounded-square icon that transitions on hover, progress overlay redesigned with brass accent spinner and backdrop blur. All labels switched to uppercase-tracked micro-label system. Button changed to full-width on mobile.
**Design Quality:** 7/10
**Originality:** 7/10
**Craft:** 7/10
**Functionality:** 8/10
**Notes:** This iteration crossed the threshold. The design now has a recognizable identity — the Instrument Serif / DM Sans pairing with brass accents, uppercase-tracked micro-labels, and the left-border accent pattern creates a coherent system. The upload page feels premium with its narrower width and generous spacing. The dashboard stat cards have genuine personality. The brass active-state nav underline ties everything together. The main remaining issues: (1) the page header bar still feels generic — it's a standard white bar with a serif heading, (2) the footer could use refinement, (3) the dropzone icon container could be more interesting on hover.
**Next move:** Refine — polish the remaining rough edges. Focus on the page header bar, footer, and overall consistency. Consider removing the redundant page header bar entirely since the upload page already has its own heading. Explore adding a subtle geometric element to interior pages.

## Iteration 4 — 2026-03-30
**Strategy:** Refinement pass. Removed redundant page header bar from upload page (had "Upload Files" then "File Upload" — was duplicative). Footer redesigned with split layout: left-aligned copyright, right-aligned Privacy/Terms links, smaller text. Added subtle radial gradient background accent to app layout (matching guest layout). Background changed from cream-100 to cream-50 for a cleaner feel.
**Design Quality:** 8/10
**Originality:** 7/10
**Craft:** 8/10
**Functionality:** 8/10
**Notes:** The design now feels genuinely polished. The upload page flows naturally from nav → brass line → serif heading → dropzone → message → submit. The footer split layout is professional and unobtrusive. The consistent brass accent motif (nav underline, stat card borders, decorative lines) ties everything together. The background gradient adds subtle depth without being obvious. The home page has a real presence with the large serif heading, trust indicators, and refined form styling.
**Next move:** Final iteration — look for any remaining inconsistencies, consider mobile responsiveness, and do a final quality check across all pages. The "UPLOADED" label color on the stat card could use the brass accent instead of the default warm color to reinforce the system.

## Iteration 5 — 2026-03-30
**Strategy:** Final polish pass. Redesigned login page to match the design system (brass accent line, serif heading, uppercase-tracked labels, full-width dark button, brass "Forgot your password?" link, warm-toned checkbox). Verified mobile responsiveness on all pages (390px viewport). All pages confirmed working well on mobile: home page heading scales nicely, trust indicators stack, upload page has full-width button, hamburger menu works.
**Design Quality:** 8/10
**Originality:** 7/10
**Craft:** 8/10
**Functionality:** 8/10
**Post-review fix:** Instrument Serif was being applied to small section headings (via CSS global override for `h2.text-lg.font-medium.text-gray-900`) making them thin and hard to read on admin/employee pages. Fixed by reverting the global override to DM Sans semibold — Instrument Serif now only appears via explicit `font-display` class at text-2xl+ sizes. Updated all 18+ page header slots to use `font-display text-2xl text-warm-900` for consistency.
**Notes:** The design system is now complete and consistent across all key pages: home (email validation), login, client dashboard, and file upload. The design identity is defined by: (1) Instrument Serif display headings contrasted with DM Sans body, (2) brass/gold accent color (#B08D57) used sparingly for visual signature — accent lines, nav underlines, stat card borders, trust icons, links, (3) uppercase-tracked micro-labels (10px, 0.15em tracking) for metadata, (4) stone neutral palette with near-black buttons, (5) generous spacing and rounded-2xl/3xl corners. Mobile responsiveness is solid. The main opportunity for further improvement would be adding more visual texture or unexpected layout elements to push Originality above 7, but that risks over-designing what is fundamentally a utility app.
