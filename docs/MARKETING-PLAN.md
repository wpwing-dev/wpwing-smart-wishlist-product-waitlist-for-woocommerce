# Growth / Marketing Plan

Goal: grow from **fewer than 10 active installs, 0 reviews** (snapshot 11-09-2026) to an install base with enough reviews and search presence to make a Pro tier a real decision at the R14 checkpoint (06-04-2027).

Written alongside [COMPETITORS.md](COMPETITORS.md) and [UPDATE-PLAN.md](UPDATE-PLAN.md). Review monthly with the competitor snapshot.

## Targets

wordpress.org shows installs in brackets (10+, 20+, ... 100+, 200+, ...) and early growth compounds slowly. Honest targets:

| Milestone | Target date | Leading indicators |
|---|---|---|
| 10+ installs, **first 3 reviews** | Oct 2026 | downloads/day > 5, biweekly cadence holding |
| 50+ installs, 10 reviews | Dec 2026 | first-page directory rank for one long-tail keyword |
| 200+ installs, 20 reviews | Feb 2027 | comparison content ranking in Google |
| 500+ installs, 35 reviews | Apr 2027 | organic installs consistently exceed churn |

Track weekly: active install bracket, downloads/day (wordpress.org advanced stats), review count and average, directory search position for "wishlist", "waitlist", "back in stock", support threads answered and resolved.

The single most important number in this table is **reviews**, not installs. TI has 501, BISN 122, MoreConvert 120, we have 0 - and rating count is both a ranking input and the first thing a merchant looks at.

---

## 1. Win the wordpress.org directory (the only channel that matters below 100 installs)

Nearly every install at this scale comes from directory search. The ranking algorithm weights readme keyword relevance, active install growth rate, rating count and quality, support thread resolution, freshness / tested-up-to, and translations. Every action below targets one of those.

### 1a. Listing hygiene (this week, near-zero effort)

- [ ] Audit the 5 tags. Current: `wishlist, waitlist, back in stock, woocommerce, save for later`. "save for later" is low volume - test swapping it for **"back in stock notifier"** or **"restock notification"**, which are the phrases merchants actually type.
- [ ] Keep the short description built around both halves in the first sentence - "wishlist" and "back in stock" are two different search intents and we are one of the few plugins that can honestly claim both.
- [ ] Hold **Tested up to: 7.1**. TI, WPC, XootiX and MoreConvert are all stuck at 7.0.4 - our listing card says "tested with your version" where theirs does not. Bump within a week of every WordPress release.
- [ ] Add an explicit "**Replaces two plugins**" section near the top of the readme description, naming the pattern (wishlist plugin + back-in-stock plugin) rather than naming competitors.

### 1b. Live preview button (this month, high leverage, nobody in this niche does it)

- [ ] Ship a WordPress Playground **blueprint** (`assets/blueprints/blueprint.json` in SVN) so the listing gets a "Live Preview" button: WooCommerce pre-installed, a sample in-stock product and a sample out-of-stock product, wishlist and waitlist pages created, wizard pre-completed.
- A visitor clicks once and sees a wishlist button and a waitlist form working. **None of the tracked competitors offer this** - it converts undecided visitors and signals quality to the directory team.

### 1c. Screenshots and media (this month)

- [ ] Rebuild the screenshot set. Lead with the **customer-facing** surfaces, not settings. Order: wishlist page with items and total, waitlist form on an out-of-stock product, the restock email as received, admin waitlist subscriber screen, wishlist button on a shop archive, setup wizard, settings.
- [ ] Annotated captions on every screenshot - captions are indexed by directory search.
- [ ] Banner (1544x500) and icon that do not look homemade beside YITH and TI.
- [ ] 60-90 second demo video: activate -> wizard -> wishlist saved -> product goes out of stock -> waitlist joined -> restock -> email arrives. That full loop *is* the pitch. Embed in the readme, seed the YouTube channel (section 3).

### 1d. Review engine (continuous - the single biggest lever we have)

Zero reviews is the number that most hurts us. Mechanisms, in order of expected yield:

- [ ] **In-plugin review prompt** at a moment of delivered value: a dismissible admin notice after the store's **first successful restock notification batch** ("Your store just notified 12 waiting customers - would you leave a review?"). Once, dismissible forever, never blocking. That moment is far stronger than a time-based or install-count-based prompt.
- [ ] **Setup wizard finish screen** links to the review page (the wizard already exists - `src/app/Admin/SetupWizard.php`).
- [ ] **Support-to-review pipeline:** every resolved forum thread ends with one polite sentence asking for a review. Resolved-thread users convert best of any segment.
- [ ] **Personally ask the first 20 installers.** At this scale, individual outreach through the support forum and GitHub is feasible and is the fastest path from 0 to 5 reviews.
- [ ] Never gate features on a review, never nag twice. YITH's 3.9 rating is largely upsell-nag backlash - do not repeat it.

### 1e. Support excellence (continuous)

- [ ] Answer every .org support topic within 24 hours; the resolved ratio is public and is a ranking input. **YITH currently shows 0 of 2 resolved** in two months - and they are the 400,000-install market leader. Beating that is nearly free at our volume.
- [ ] Watch specifically for: theme conflicts around the button render hooks, form CSS collisions, and hosts with broken SPF/DKIM blaming us for undelivered restock emails. Those three account for most predictable early tickets.

### 1f. Translations (this quarter, formalized at R12)

Translated plugins surface in localized directories - a far less competitive search landscape - and translation contributions feed ranking.

- [ ] Seed translations at translate.wordpress.org for **Bengali** (native team language), Spanish, German, French, Dutch, Italian, Brazilian Portuguese, Hindi.
- [ ] Readme first (that is what localized directories index), then settings strings.
- [ ] R12 (09-03-2027) ships `wpml-config.xml` and the RTL stylesheet; the translation seeding should be running well before that.

---

## 2. Positioning: pick fights we can win

We cannot out-feature YITH or out-review TI this year. Three angles that work at our size:

- **"One plugin instead of two."** This is the whole thesis and no free competitor can copy it quickly. Merchants running TI + BISN are carrying two menus, two asset bundles, two update streams and two support vendors. Lead with this everywhere.
- **"The waitlist is free here."** MoreConvert (8,000+ installs, 4.9) sells back-in-stock as their premium upgrade, and their own reviews complain the waitlist backend is thin ("There is no place in the back end these customers go"). We give the waitlist away *and* ship a real subscriber admin screen. That is a direct, checkable comparison.
- **"Deliverability done right."** Genuine RFC 8058 one-click unsubscribe, Action Scheduler background sending, automatic retry on failure. No competitor in the tracked set advertises the List-Unsubscribe header. Gmail and Yahoo sender requirements make this a live merchant worry, not a technicality.
- **"Lightweight, no nags."** Custom database tables instead of postmeta bloat, one asset file, no dashboard banners or greyed-out upsell settings. Directly targets the loudest complaints in YITH's 3.9-star review pile.

---

## 3. Content marketing (starts now, compounds by Q1 2027)

Directory search caps out; Google long-tail brings the next tier. All content lives on wpwing.com with the plugin as the answer.

- [ ] **Comparison content** (highest commercial intent):
  - "Best free WooCommerce wishlist plugins (2026)" - honest roundup including TI, YITH, WPC.
  - "Best free back-in-stock notification plugins for WooCommerce (2026)" - separate post, separate intent, includes BISN and XootiX.
  - "Do you need two plugins for wishlist and back-in-stock?" - the thesis post, and the one every other page links to.
  - "X alternative" pages for each tracked competitor - low volume, near-100% intent.
- [ ] **Long-tail how-tos**, each mapped to a shipped feature:
  - "How to add a back-in-stock email alert to WooCommerce (free)"
  - "How to let guests use a WooCommerce wishlist without an account"
  - "How to see which products customers want most" (ships with R7 analytics)
  - "How to notify waitlist customers for a specific variation"
  - "How to stop bots signing up to your back-in-stock form" (ships with R1 captcha)
  - "Why your restock emails go to spam - and the List-Unsubscribe header"
- [ ] **YouTube:** screen-recorded versions of the top how-tos. Video results rank for "how to" WooCommerce queries against thin competition.
- [ ] Cadence: 2 posts/month minimum. Every release gets a matching how-to post plus a changelog-driven social mention - the biweekly release schedule doubles as a content calendar.

---

## 4. Community distribution (low cost, immediate)

- [ ] **Facebook groups:** WordPress Bangladesh (team's home community), WooCommerce Community, WooCommerce help groups. Answer wishlist/restock questions genuinely; mention the plugin only when it is the actual answer.
- [ ] **Reddit** r/woocommerce and r/wordpress - same rule.
- [ ] **The main WooCommerce .org support forum:** unanswered "how do I notify customers when a product is back in stock" threads are fair ground for a helpful answer. Never post in a competitor's own support forum.
- [ ] **GitHub presence:** the repo is public - add good-first-issue labels and a CONTRIBUTING note, and respond fast. Contributors become advocates and reviewers.
- [ ] **Roundup outreach:** pitch inclusion to existing "best wishlist plugin" and "best back-in-stock plugin" listicles - these get refreshed yearly and "new, free, does both halves, actively maintained" is a pitch editors accept. One placement can outdo a month of directory grinding.

---

## 5. Launch moments

Every release is small; a few are worth a real push:

- [ ] **R2 form presets + popup (06-10-2026)** - first visually demo-able release; good for the video seed.
- [ ] **R7 demand analytics (15-12-2026)** - the "free thing everyone else charges for" story. Blog post + video + community posts + newsletter pitch (wpMail.me, The Repository, Post Status).
- [ ] **R9 double opt-in + unsubscribe management (26-01-2027)** - the GDPR/deliverability story, pitched to WooCommerce-focused newsletters and any privacy-angle audience.
- [ ] **Pro launch, if it happens** - decided at R14 (06-04-2027), not before. Product Hunt plus newsletters, with a launch-week discount for urgency.
- [ ] Deal platforms (AppSumo and similar): **not** before Pro is stable. Good for cash and installs, hard on support load and review averages. Revisit after R14.

---

## 6. What we deliberately skip (for now)

- **Paid ads:** "woocommerce wishlist" CPCs are bid up by incumbents with LTVs we cannot match at fewer than 10 installs. Revisit only after Pro conversion data exists.
- **Affiliate program:** needs Pro revenue first.
- **Competing on integration count** (MoreConvert advertises 100+, TI 50+). We win on hooks and, from R8, a REST API - not on a logo wall we would have to maintain.
- **Any claim we cannot check.** No revenue-recovery math, no "increases sales by X%". Zero reviews means the first bad-faith claim costs more than it earns.

---

## Standing weekly marketing checklist

- Ship the biweekly release on schedule (freshness = ranking); on off-weeks, ship the matching blog post.
- Answer every support thread; ask resolved ones for a review.
- One genuinely helpful community answer (Facebook / Reddit / .org forum).
- Log the numbers: install bracket, downloads/day, reviews, directory rank for "wishlist" and "back in stock".
- Monthly: refresh [COMPETITORS.md](COMPETITORS.md), re-check [UPDATE-PLAN.md](UPDATE-PLAN.md) against reality, and review this plan's targets.
