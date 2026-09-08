# Send a text message (JavaScript / TypeScript)

Comments are in English. HTTP `202 Accepted`; JSON status is `sending`.

```ts
const apiKey = process.env.ZAP_API_KEY;
const instanceId = process.env.ZAP_INSTANCE_ID;

if (!apiKey || !instanceId) {
  throw new Error('Set ZAP_API_KEY and ZAP_INSTANCE_ID.');
}

const response = await fetch('http://localhost:8000/api/v1/messages/text', {
  method: 'POST',
  headers: {
    Authorization: `Bearer ${apiKey}`,
    'Content-Type': 'application/json',
    'Idempotency-Key': crypto.randomUUID(),
  },
  body: JSON.stringify({
    instance_id: instanceId,
    to: '5511999999999',
    text: 'Hello from ZAP',
  }),
});

if (response.status !== 202) {
  throw new Error(`Unexpected status ${response.status}: ${await response.text()}`);
}

const body: { data: { id: string; status: string; instance_id: string } } = await response.json();

console.log(body.data.id, body.data.status);
```
