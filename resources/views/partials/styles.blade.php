{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    One stylesheet for the panel and the site. Every colour is a variable of
    the panel's themes (--atrium-*), then of the site theme (--bg, --text…),
    then a fallback, so the pages read well in all of them. Measured pairs
    (WCAG): see docs/two-factor.md, "Contrast".
    Nothing is told by colour alone: errors have a bar, a mark and words;
    the chosen factor is bold and has aria-current.
--}}
<style @if ($nonce = \Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ $nonce }}" @endif>
.a2f {
    --a2f-bg: var(--atrium-page, var(--surface, #f3f4f6));
    --a2f-card: var(--atrium-surface, var(--bg, #ffffff));
    --a2f-fg: var(--atrium-fg, var(--text, #16181d));
    --a2f-muted: var(--atrium-muted, var(--muted, #4f5662));
    --a2f-line: var(--atrium-line, var(--line, #e2e5ea));
    --a2f-field: var(--atrium-field, var(--control, #6b7280));
    --a2f-accent: var(--atrium-accent, var(--accent, #1d4ed8));
    --a2f-on-accent: var(--atrium-on-accent, var(--bg, #ffffff));
    --a2f-focus: var(--atrium-focus, var(--focus, #1d4ed8));
    --a2f-hover: var(--atrium-hover, var(--surface, #f3f4f6));
    --a2f-danger: var(--atrium-danger, var(--text, #16181d));
    --a2f-ok: var(--atrium-success, var(--text, #16181d));
    color: var(--a2f-fg);
    font: inherit;
    line-height: 1.5;
}
.a2f *, .a2f *::before, .a2f *::after { box-sizing: border-box; }
.a2f-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; background: var(--a2f-bg); }
.a2f-site { display: flex; justify-content: center; padding: 2rem 1rem; }
.a2f-card { width: 100%; max-width: 28rem; background: var(--a2f-card); border: 1px solid var(--a2f-line); border-radius: .5rem; padding: 2rem; }
.a2f-wide { max-width: 64rem; }
.a2f h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 .5rem; }
.a2f h2 { font-size: 1.125rem; font-weight: 600; margin: 0 0 .5rem; }
.a2f p { margin: 0 0 1rem; }
.a2f-muted { color: var(--a2f-muted); }
.a2f-small { font-size: .875rem; }
.a2f-stack > * + * { margin-top: 1rem; }
.a2f-row { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; }
.a2f-section { border-top: 1px solid var(--a2f-line); padding-top: 1.5rem; margin-top: 1.5rem; }
.a2f label { display: block; font-weight: 600; font-size: .875rem; margin-bottom: .25rem; }
.a2f input[type=text], .a2f input[type=email], .a2f input[type=tel], .a2f input[type=search], .a2f select {
    display: block; width: 100%; padding: .5rem .75rem; font: inherit; color: var(--a2f-fg);
    background: var(--a2f-card); border: 1px solid var(--a2f-field); border-radius: .375rem;
}
.a2f input.a2f-code { font-size: 1.5rem; letter-spacing: .3em; text-align: center; font-variant-numeric: tabular-nums; }
.a2f input:focus-visible, .a2f select:focus-visible, .a2f button:focus-visible, .a2f a:focus-visible {
    outline: 3px solid var(--a2f-focus); outline-offset: 2px;
}
.a2f-check { display: flex; gap: .5rem; align-items: center; font-weight: 400 !important; }
.a2f-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; padding: .5rem 1rem;
    font: inherit; font-weight: 600; font-size: .875rem; border-radius: .375rem; cursor: pointer; text-decoration: none;
    border: 1px solid var(--a2f-field); background: var(--a2f-card); color: var(--a2f-fg);
}
.a2f-btn:hover { background: var(--a2f-hover); }
.a2f-btn[disabled] { cursor: not-allowed; opacity: .7; }
.a2f-primary { background: var(--a2f-accent); border-color: var(--a2f-accent); color: var(--a2f-on-accent); }
.a2f-primary:hover { background: var(--atrium-accent-hover, var(--a2f-accent)); text-decoration: underline; }
.a2f-block { width: 100%; }
.a2f-link { background: none; border: 0; padding: 0; font: inherit; color: var(--a2f-fg); text-decoration: underline; cursor: pointer; }
.a2f-alert { border: 1px solid var(--a2f-line); border-left-width: 4px; border-radius: .375rem; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .875rem; }
.a2f-alert::before { font-weight: 700; margin-right: .25rem; }
.a2f-error { border-left-color: var(--a2f-danger); color: var(--a2f-danger); }
.a2f-error::before { content: "!"; }
.a2f-ok { border-left-color: var(--a2f-ok); }
.a2f-ok::before { content: "✓"; }
.a2f-field-error { color: var(--a2f-danger); font-size: .875rem; margin-top: .25rem; }
.a2f-field-error::before { content: "! "; font-weight: 700; }
.a2f-list { list-style: none; padding: 0; margin: 0; }
.a2f-list > li { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .75rem 0; border-bottom: 1px solid var(--a2f-line); }
.a2f-codes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .25rem 1rem; font-family: ui-monospace, monospace; font-size: 1rem; padding: 1rem; border: 1px dashed var(--a2f-field); border-radius: .375rem; }
.a2f-qr { display: inline-block; padding: .5rem; background: #ffffff; border-radius: .375rem; line-height: 0; }
.a2f-secret { font-family: ui-monospace, monospace; word-break: break-all; }
.a2f-tabs { display: flex; flex-wrap: wrap; gap: .5rem; }
.a2f-tabs [aria-current] { font-weight: 700; border-width: 2px; border-color: var(--a2f-fg); }
.a2f-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
.a2f-table th, .a2f-table td { text-align: left; padding: .5rem; border-bottom: 1px solid var(--a2f-line); vertical-align: top; }
.a2f-table th { font-weight: 600; color: var(--a2f-muted); }
.a2f-divider { display: flex; align-items: center; gap: .75rem; color: var(--a2f-muted); font-size: .875rem; margin: 1.5rem 0 1rem; }
.a2f-divider::before, .a2f-divider::after { content: ""; flex: 1; border-top: 1px solid var(--a2f-line); }
.a2f-methods { list-style: none; padding: 0; margin: 0; display: grid; gap: .5rem; }
.a2f-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
</style>
