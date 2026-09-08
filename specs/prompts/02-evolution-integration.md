# ZAP — Prompt 02: Milestone 2 Evolution Integration

You are implementing **only Milestone 2** of ZAP. M0–M1 already exist.

## Mandatory reading (in this order)

1. `specs/constitution.md`
2. `ZAP_SDD.md` sections **8.1, 9.6, 12, 13, 22, 23, 26.6, 27, 31, 37.2, 48, 49 (M2)**
3. `preview (6).html` — instance / QR / dashboard CTAs
4. `specs/tasks.md` — complete **T024–T033**

Do not implement the internal Evolution webhook controller (M3), Sanctum public API (M4), inbox persistence (M5), or customer webhook delivery (M6).

## Goal

Workspace owners can create a WhatsApp instance asynchronously, see a QR Code, watch connection status, disconnect, and delete. All Evolution I/O goes through `MessagingProvider`. The HTTP request never waits on Evolution.

## Provider seam (§12)

Create:

```text
app/Domain/... (ZAP types / actions)
app/Integrations/Evolution/EvolutionMessagingProvider.php
app/Integrations/Evolution/EvolutionClient.php
```

Interface (ZAP types only — no Evolution URLs, event names, or payloads):

```php
interface MessagingProvider
{
    public function createInstance(CreateProviderInstanceData $data): ProviderInstance;
    public function connectInstance(string $instanceName): QrCodeData;
    public function getConnectionState(string $instanceName): ProviderConnectionState;
    public function sendText(SendTextData $data): ProviderMessage;
    public function downloadMedia(DownloadMediaData $data): ProviderMedia;
    public function configureWebhook(ConfigureProviderWebhookData $data): void;
    public function deleteInstance(string $instanceName): void;
}
```

`sendText` / `downloadMedia` may be implemented as real client methods but **must not** be wired to a public API or inbox yet. Stub the unused paths with typed exceptions if that keeps M2 smaller — document the stub.

HTTP client: connect/request timeouts, limited retries on safe ops, jitter, structured exceptions, correlation id, **no secrets in logs**.

Credentials: `EVOLUTION_API_KEY` server-side only. Instance tokens encrypted at rest. Never Inertia props, Vue, or logs.

Provider instance name: `zap_<workspace-public-id>_<instance-public-id>` — never pass the user-facing name to Evolution.

## Instance model (§9.6)

Statuses (ZAP-owned enum, not raw Evolution strings):

`creating | waiting_qr | connecting | connected | disconnected | error | deleting`

Quota: `MAX_INSTANCES_PER_WORKSPACE` (default 1), overridable on workspace.

## Flow (§13)

1. `POST /instances` → persist `creating` → dispatch `ProvisionInstance` on `provider` queue → redirect to show page.
2. Job: create provider instance, configure webhook URL to `EVOLUTION_WEBHOOK_BASE_URL` (controller can 404 until M3), fetch QR, set `waiting_qr`.
3. Broadcast `InstanceQrUpdated` / `InstanceProvisionFailed` on `private-workspaces.{workspacePublicId}`.
4. UI polls QR/status if Reverb is down.
5. Disconnect / delete with authorization + audit events.
6. QR rules (§26.6): do not log QR values; authorize workspace members; expire after connect.

Jobs: timeout, retries, backoff, idempotent, pass IDs not Eloquent graphs.

## UI

- `Pages/Instances/Index.vue`, `Create.vue`, `Show.vue`
- Dashboard CTA “Nova instância” should work
- Match mockup density and pt-BR copy
- Never show Evolution URLs or tokens

## Tests

- Feature: quota exceeded, unauthorized workspace, create is async (job faked)
- Integration: `EvolutionClient` via Laravel HTTP fakes + fixtures
- Default Pest suite must **not** require a live Evolution container
- QR is not present in log assertions

## Acceptance

- Provider details do not leak through `MessagingProvider`
- HTTP create/delete does not block on Evolution
- SDD §48
- `specs/tasks.md` T024–T033 checked

## Stop when

Instance provisioning works against fakes and the UI shows QR/status. Do not ingest Evolution webhooks yet (configure the URL, but processing is M3).

At the end, print: “next prompt = `specs/prompts/03-provider-webhooks.md`”.
