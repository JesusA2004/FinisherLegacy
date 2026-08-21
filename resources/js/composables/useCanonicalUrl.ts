/**
 * Best-effort absolute URL of the current page, for `<link rel="canonical">`
 * / `og:url`. There's no `app.url` shared from the backend to the frontend
 * (would need a middleware change, out of scope here), so this reads
 * `window.location` instead — correct for the actual visitor and for any
 * crawler that executes JS (Google, Bing), but not present in the very
 * first byte of HTML a non-JS social-share bot fetches. `og:image`/
 * `og:title`/`og:description` — set directly per page, no such gap — are
 * what matter most for how a shared link actually previews; this covers
 * canonical/og:url as the best available approximation.
 *
 * Query strings are stripped by default (pagination/filter params
 * shouldn't be treated as separate canonical documents) — pass
 * `keepQuery: true` for pages where the query IS the content (e.g. a
 * filtered event list you want indexed as-is).
 */
export function useCanonicalUrl(options?: { keepQuery?: boolean }) {
    if (typeof window === 'undefined') {
        return undefined;
    }

    return options?.keepQuery
        ? window.location.href
        : `${window.location.origin}${window.location.pathname}`;
}
