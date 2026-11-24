# Frontend & User Interface Architecture

The NextPixel frontend is a Single-Page Application (SPA) powered by **Vue 3** (Composition API with `<script setup lang="ts">`), **Inertia.js 3**, and **Tailwind CSS 4**, utilizing UI components inspired by **shadcn-vue**.

---

## Technology Stack

- **Framework:** Vue 3 (Composition API, `<script setup lang="ts">`)
- **Bridge:** Inertia.js 3 (Seamless Laravel server-side routing with Vue component rendering)
- **Styling:** Tailwind CSS 4 with CSS variables for semantic theme tokens
- **Component Primitives:** Reka UI (formerly Radix Vue) / shadcn-vue
- **Icons:** Lucide Icons (`lucide-vue-next`)
- **Build Tool:** Vite with TypeScript

---

## Layout Architecture

```
┌────────────────────────────────────────────────────────────────────────┐
│ AppSidebarLayout.vue                                                   │
│ ┌───────────────┬────────────────────────────────────────────────────┐ │
│ │ Main Sidebar  │ Header (AppHeader.vue)                             │ │
│ │               │  [Toggle] [Page Title]    [Theme Toggle] [User ▼]  │ │
│ │ • NextPixel   ├────────────────────────────────────────────────────┤ │
│ │   Logo        │ Main Content Area                                  │ │
│ │ • Dashboard   │                                                    │ │
│ │ • Settings    │                                                    │ │
│ │               │                                                    │ │
│ │               │                                                    │ │
│ └───────────────┴────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────────────┘
```

### 1. Main Sidebar (`AppSidebar.vue`)

- Displays NextPixel brand logo and active navigation items (`Dashboard`, `Settings`).
- Supports responsive collapsing on mobile and desktop.
- Unnecessary boilerplate widgets have been removed to preserve a distraction-free experience.

### 2. Header & User Account Dropdown (`AppHeader.vue`)

The user account dropdown is positioned in the top-right header:

- Left: Sidebar collapse toggle button and dynamic page title.
- Right:
    - **Theme Toggle:** Switch between Light and Dark modes.
    - **User Dropdown Trigger:** Displays user avatar, full name, and dropdown chevron. Entire trigger is clickable.
    - **Dropdown Menu:**
        - **Settings:** Opens account settings (`Settings` icon).
        - **Log Out:** Terminates session / revokes tokens (`LogOut` icon).

---

## Account Settings (`resources/js/pages/settings/`)

The settings interface is consolidated into two horizontal tabs using shadcn-vue `Tabs`:

### 1. General Tab (`Profile.vue`)

- **Profile Information:** Name and Email address fields with live validation.
- **Save Changes:** Asynchronous form submission preserving page scroll.
- **Delete Account:** High-visibility confirmation dialog. When confirmed, dispatches account deletion, which unlinks generations (`user_id = null`) to maintain provider audit history.

### 2. Security Tab (`Security.vue`)

- **Current Password:** Secure verification of the existing credential.
- **New Password & Confirmation:** Password inputs equipped with toggleable visibility eye icons.
- **Validation & Error Handling:** Real-time feedback for mismatched confirmations or insufficient password complexity.

---

## Self-Hosted Typography & Offline Fonts

NextPixel operates with **zero external font or CDN dependencies** for privacy, performance, and offline compliance.

- **Fonts:**
    - `Bricolage Grotesque`: Used for distinctive headings and branding.
    - `Inter`: Used for clean, legible body text and interface elements.
- **Storage:** Local `.woff2` font files are located in `public/fonts/`.
- **CSS Definitions:** Locally defined via `@font-face` in `resources/css/fonts.css`:

```css
@font-face {
    font-family: 'Inter';
    font-style: normal;
    font-weight: 100 900;
    font-display: swap;
    src: url('/fonts/inter-latin.woff2') format('woff2');
}

@font-face {
    font-family: 'Bricolage Grotesque';
    font-style: normal;
    font-weight: 200 800;
    font-display: swap;
    src: url('/fonts/bricolage-grotesque-latin.woff2') format('woff2');
}
```

---

## Dark & Light Theme System

Theme switching is implemented via CSS class toggling (`dark`) on the root HTML document element.

- **Tailwind Tokens:** Semantic colors such as `bg-background`, `text-foreground`, `border-border`, `bg-card`, and `text-muted-foreground` adapt automatically between themes.
- **Persistence:** User theme selection is stored in `localStorage` and restored before initial DOM paint to prevent flash of unstyled theme (FOUT).

---

## Development & Building

```bash
# Run Vite development server with Hot Module Replacement (HMR)
npm run dev

# Run TypeScript type-checking
npm run type-check

# Compile production assets
npm run build
```
