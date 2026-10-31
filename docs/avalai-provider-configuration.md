# AvalAI provider configuration

This change adds a server-side AvalAI chat-completions client. It does not activate any public AI feature or change the booking/OTP flow.

## Environment

Set these values in the local or deployment environment (never in Git):

```dotenv
AVALAI_API_KEY=your_rotated_key
AVALAI_BASE_URL=https://api.avalai.ir/v1
AVALAI_MODEL=your_supported_model_id
AVALAI_TIMEOUT=30
```

After changing environment values on a Laravel deployment, refresh cached configuration with `php artisan config:cache` (or clear the cache during local development with `php artisan config:clear`).

## Use

Resolve `App\Services\AvalAIClient` through Laravel's container and call `chat($messages, $options)`. The client sends a Bearer-authenticated JSON request to `/chat/completions` and throws for missing configuration or unsuccessful HTTP responses.

## Safety boundary

- Never commit a real API key, put it in browser JavaScript, or return it from an endpoint.
- Rotate any key pasted into chat, tickets, logs, or source control.
- Do not send patient-identifying, medical, appointment, image, or other confidential clinic data to AvalAI until the clinic has approved the provider's privacy, retention, and processing terms and the applicable consent workflow.
- This is provider plumbing only. The clinic's AI features remain inactive/demo until their approved product and safety requirements are implemented.
- This does not implement SMS OTP. Task 15 remains separately deferred and the public booking verification gate must remain enabled.
