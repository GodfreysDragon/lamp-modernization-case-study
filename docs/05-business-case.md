# 5. The business case

> **Who this is for:** the person who signs off the budget, not the person who writes the code.
> Figures below are a **worked example with stated assumptions**. Swap in your own numbers; the structure of the argument is what transfers.

## The situation

Many companies have an app like HelpDesk Pro: written a decade ago, business-critical, maintained by whoever is brave enough. It "works", so the cost of keeping it stays invisible until something forces the issue: a breach, a failed audit, a PHP version the host stops supporting, or the one developer who understood it leaving.

The question isn't whether to modernize. It's **whether to pay a known, fixed cost now or an unknown, growing cost later.**

## What the audit found, in business terms

| Technical finding | Business exposure |
|---|---|
| SQL injection on the login page | Anyone on the internet can log in as the administrator. Every customer's name, email, phone and support history can be taken |
| Unauthenticated endpoint that edits tickets | One request can close every open ticket. SLA reporting becomes meaningless |
| MD5 passwords | If the database leaks, staff passwords are cracked in minutes, and people reuse them on email and banking |
| PHP 7.4 (EOL Nov 2022), jQuery 1.8 | No security patches. Fails PCI-DSS 6.3.3 and the vulnerability-management controls in ISO 27001 and SOC 2. Cyber insurers increasingly ask about this |
| SLA bugs (urgent = "+1 day", overdue breaks in December) | **Customer-facing SLA reports have been wrong.** For a business that sells support contracts, that's a contractual problem, not a technical one |
| No tests | Every change is a gamble, so changes are avoided, so the business works around the software instead of improving it |

## Cost of doing nothing (annual, illustrative)

| Item | Assumption | Annual cost |
|---|---|---:|
| Developer "fear tax" | Small changes take 3× longer without tests or structure. 120 dev-hours/yr of changes → 360 hrs at $95/hr; 240 hrs wasted | $22,800 |
| Incident handling | 2 production incidents/yr × 16 hrs (diagnosis on undocumented code, hotfix, manual data repair) | $3,040 |
| Agent time lost to slow pages | 6 agents × 15 min/day waiting or refreshing × 230 days at $30/hr | $10,350 |
| Hosting a legacy runtime | Extended-support PHP or a pinned, unpatched VM | $1,200 |
| **Recurring total** | | **≈ $37,000/yr** |
| **Breach risk (expected value)** | Average SMB breach cost ~$120k–$250k. With an internet-facing SQL injection, assume a 10–20% annual probability | **$12,000–$50,000/yr** |

Even the most conservative reading (no breach) puts the cost of standing still at **$37k a year, every year, rising** as the runtime ages and the people who understand it leave.

## Cost of modernizing (this project)

Estimated effort for an app of this size (~10 screens, 4 tables, one integration-free codebase), for an experienced Laravel team:

| Phase | Days |
|---|---:|
| Audit + characterization tests | 2 |
| Schema, domain model, import command | 3 |
| Feature parity (backend + Vue UI) | 6 |
| Test hardening, security regression suite | 2 |
| Parallel run, cutover, hypercare | 2 |
| **Total** | **15 days ≈ $11,400** at $95/hr |

**Payback: about 4 months** on recurring costs alone, before counting any breach risk. A larger legacy system scales roughly with the number of screens and integrations. The method doesn't change.

## What the client gets beyond "it's not broken any more"

| Capability | Before | After |
|---|---|---|
| Ship a change safely | Days of manual clicking and crossed fingers | 111 automated tests in 7 seconds on every commit |
| Hire for it | "PHP 5-era procedural, no framework": a small, shrinking pool | Laravel + Vue: one of the largest PHP/JS talent pools |
| Onboard a new developer | Weeks reading the source | Standard Laravel conventions; `composer setup` and go |
| Pass a security questionnaire | Fail | Bound queries, CSRF, bcrypt, rate limiting, escaped output, supported runtime |
| Trust the SLA numbers | No | Yes, one tested rule |
| Add features (email-to-ticket, customer portal, API, reporting) | Effectively a rewrite each time | Incremental: the models, policies and resources already exist |

## Why this approach lowers the risk of the project itself

Rewrites have a bad reputation because they fail in predictable ways. This method is built around each of them:

| How rewrites usually fail | How this approach prevents it |
|---|---|
| "The new one doesn't do what the old one did" | **Characterization tests** capture real behaviour before any code is written, and the client signs them off |
| Data migration surprises on launch day | **Idempotent, dry-runnable import**, rehearsed nightly for two weeks with every anomaly reported |
| Users locked out or retraining needed | Same workflows 1:1. **Passwords migrate invisibly** on first login |
| No way back | Legacy stays read-only for 14 days; **rollback is a DNS change** |
| Scope creep | Feature *parity* first. New features come after cutover, from a stable base |

## How to read this repo as a buyer

1. **[Audit](01-legacy-audit.md)**: what we'd find in your codebase, and how we report it.
2. **[Strategy](02-migration-strategy.md)**: how we'd get from there to here without downtime.
3. **[Before/after](03-before-after.md)**: what the code actually looks like on each side.
4. **[Testing](04-testing-strategy.md)**: how we prove nothing broke.
5. **The code**: [`legacy/`](../legacy) and [`modern/`](../modern), side by side, both runnable.
