---
inclusion: always
---

# Frontend Development Guidelines

This document outlines the frontend development standards and best practices for this project.

## Tailwind CSS 4.x

This project uses Tailwind CSS v4.x which has significant changes from previous versions.

### Group Variant Changes

In Tailwind CSS v4.0+, the `group` class is no longer a utility class but a variant modifier:

- **DO NOT** add `class="group"` to parent elements
- **DO** use `[group]: '';` as a property in your CSS
- **DO** use `[group]:hover` instead of `.group:hover` for group hover effects

```css
/* ❌ Old Tailwind 3.x syntax - DO NOT USE */
.parent-element {
    @apply flex items-center group;
}
.parent-element:hover .child-element {
    @apply bg-blue-500;
}

/* ✅ Correct Tailwind 4.x syntax */
.parent-element {
    @apply flex items-center;
    [group]: '';
}
[group]:hover .child-element {
    @apply bg-blue-500;
}
```

### Other Tailwind 4.x Changes

- **Arbitrary properties** now use square brackets: `[--my-var:20px]` instead of `[--my-var:20px]`
- **Color opacity** now uses slash syntax: `bg-blue-500/50` instead of `bg-blue-500/50`
- **Important modifier** now uses bang syntax: `!mt-0` instead of `!mt-0`

## CSS Best Practices

- Use Tailwind utility classes whenever possible
- Avoid writing custom CSS unless absolutely necessary
- Follow mobile-first responsive design principles
- Use semantic HTML elements with appropriate ARIA attributes
- Ensure all interactive elements are keyboard accessible

## Alpine.js Usage

- Use Alpine.js for interactive components
- Keep component logic simple and focused
- Use `x-data` to define component state
- Use `x-init` for initialization logic
- Prefer Alpine.js over jQuery or vanilla JavaScript

## Accessibility Standards

- Ensure proper color contrast (WCAG AA compliance)
- Use semantic HTML elements
- Include proper ARIA attributes
- Ensure keyboard navigation works for all interactive elements
- Test with screen readers

## Performance Considerations

- Minimize unused CSS with PurgeCSS (built into Tailwind)
- Lazy load images and heavy components
- Use responsive images with appropriate sizes
- Minimize JavaScript bundle size
- Use code splitting for large applications