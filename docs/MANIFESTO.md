# BugSneak — Product Clarity Manifesto

> Keep this before adding any feature.

## Core Identity

BugSneak is **NOT** a debugging toolbox.
BugSneak is **NOT** a performance monitor.
BugSneak is **NOT** a Query Monitor alternative.

**BugSneak is a Crash Intelligence System.**

Its only job: Help developers instantly understand **what broke**, **where it broke**, and **why it broke**.

---

## The Strategic Decision

We intentionally DO NOT include:

- Slow query monitoring
- Duplicate query detection
- Hook explorer
- Template hierarchy viewer
- HTTP request inspector
- Cron debugging
- REST debugging
- Object cache inspection
- Script/style inspection

Those belong to Query Monitor's category. We are building something different.

---

## Why This Is Strength (Not Weakness)

Performance tools answer: *"Why is this request slow?"*

BugSneak answers: **"Why did my site crash?"**

These are different emotional states. When a developer sees a White Screen of Death, Fatal error, Memory exhausted, or a random crash in production — they don't want SQL tables, hook priority lists, or template hierarchy dumps.

They want:
- The exact file
- The exact line
- The stack trace
- The suspected plugin
- How often it is happening

---

## MVP Scope

1. Reliable error interception
2. Intelligent error grouping
3. Clean modern dashboard
4. Code snippet preview (±5 lines)
5. Basic culprit detection
6. Minimal context capture
7. Safe log retention system

**Nothing else.**

---

## The Discipline Rule

Before adding ANY feature, ask:

> **Does this directly help understand a crash faster?**

If NO → Do not build it.

---

## The Identity Protection Rule

If the dashboard starts showing massive SQL tables, query breakdowns, hook trees, or performance metrics — **we have lost the plot.**
