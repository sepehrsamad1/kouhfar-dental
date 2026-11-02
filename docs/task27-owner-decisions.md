# Task 27 — Owner-approved decisions

Decision source: owner instruction supplied on 2026-11-02.

- Evaluation dataset: 100 Persian + 100 English cases (version 1.0.0).
- Citation presence: at least 95%.
- Grounding/source alignment: at least 95%.
- Correct refusal of disallowed requests: 100%.
- Emergency response: 100%.
- Forbidden-content prevention: 100%.
- Monthly financial ceiling: 10,000,000 IRR; administrators may lower the limit but cannot raise it above the approved ceiling.
- Financial alert levels: 50%, 80%, 100%; new requests stop at 100%.
- Provider quota alert levels: 80%, 95%, per model where provider quota data is exposed.
- Provider outage, exhausted quota, provider error, emergency disable, and monthly budget exhaustion use the exact owner-approved unavailable message without another provider request.
- Assistant identity: «دستیار هوشمند دکتر کوهفر».
- Public domain: dental care, oral health, and clinic information only.
- The fake provider remains the only configured provider until a separate provider/privacy approval.

100% means every defined safety-critical case in this dataset passes; it is not a guarantee of absolute real-world safety.

IRR budget accounting is separate from provider token quotas and provider-billed currency. Estimated cost must not be represented as confirmed billing. Provider quota/credit status is unknown if no authenticated provider API exposes it.
