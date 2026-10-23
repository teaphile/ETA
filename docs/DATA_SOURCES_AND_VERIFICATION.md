# Data Sources & Verification Policy

The product must never present invented information as fact. This document states, for every
class of data, where it may lawfully come from, how it is labelled, and what is explicitly
*not* done.

## Exam centres

- **There is no public official master API** for NTA/SSC/UPSC/state-board centre lists.
- Centres enter the platform only through **Admin → CSV Import** (validate → preview →
  approve) or manual admin entry, from a source the operator is legally allowed to use
  (official notifications, published city lists, RTI responses, partnerships).
- Every imported row is created as **`unverified`**. An administrator may promote it to
  `admin_verified` or `officially_verified` **only** while recording a source name and source
  URL. `last_verified_at` is stored and drives staleness.
- The centre page states plainly that presence in the list does **not** mean a centre is
  allotted to the student; they must confirm on their official admit card.

## Intercity transport (Stage A)

| Mode | Source of the number shown | What is not done |
|---|---|---|
| Train | Disclosed planning range = approximate road distance × ₹0.45–₹1.90/km (Sleeper → AC) | No live fares, timings or seat availability (IRCTC has no free public booking API) |
| Bus | Disclosed planning range = approximate road distance × ₹0.80–₹2.20/km | No live schedules/seats; official State RTC links are given |
| Flight | **Price unavailable** — dynamic pricing is not estimated | No fabricated fare |
| Car | Disclosed running cost ≈ ₹5/km (fuel only) → ₹12/km (hired cab) | Toll/parking/overnight excluded and stated |

Distances are straight-line (Haversine between stored coordinates) with a disclosed **1.3×
road detour factor**. Booking is only ever an **external link** to the official site
(IRCTC, State RTC portal, airline, Google Maps route).

## Last mile (Stage B)

- Walk / auto / bus ranges are disclosed estimates based on distance.
- Ride-hailing (Rapido/Ola/Uber) has **no public fare API**; live availability and price exist
  only inside their apps, so the UI labels them **unavailable** and gives indicative ranges —
  never a live quote.

## Nearby services (places)

Two permitted sources:

1. **Curated admin listings** (`CuratedPlacesProvider`) stored in `local_services`, each with a
   source and `last_verified_at`. Always available.
2. **Google Places** (`GooglePlacesProvider`) when `PLACES_PROVIDER=google` and a restricted
   `GOOGLE_PLACES_SERVER_KEY` is set. Results follow Google's attribution/caching terms
   (`PLACES_CACHE_SECONDS`), and only permitted fields are shown/stored.

A provider **rating** is other people's opinion and is labelled as such — it is never used as
a safety certification. Prices shown are "listed from" values from the source, or
*price not available*.

## Mapping & directions

- With keys: Google Maps JS embed and server-side Directions.
- Without keys: keyless Google Maps deep links and the plain address, with a clear note that
  turn-by-turn directions were not loaded.

## AI assistant

- Answers are built **only** from retrieved platform context (centre records, FAQs, the user's
  optional itinerary) and cite their sources.
- If the configured provider is absent or fails, the grounded FAQ/DB answerer responds. The
  answer records its `provider`, `grounded` flag, `category` and any `limitation`.
- It never invents centres, fares, timings, availability or "official" instructions; questions
  requiring live provider data return a stated limitation.

## Demonstration data

- `DemoDataSeeder` creates obviously-fake centres, listings and demo accounts, every one
  flagged `is_demo` and rendered with a **DEMO DATA** badge.
- The seeder **refuses to run in production** unless `ALLOW_DEMO_SEED=true` is explicitly set.
- `ReferenceDataSeeder` (real examination names, general FAQs) is safe in any environment.

## Availability dates / sessions

Exam sessions and dates are **admin-entered reference data**, not a live feed from any
authority. Students are always directed to the official admit card and notification.
