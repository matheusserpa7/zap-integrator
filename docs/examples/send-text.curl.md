# Send a text message

HTTP `202 Accepted`. The JSON `data.status` is `sending` until the queued provider job finishes.

Replace `YOUR_ZAP_API_KEY` and `ins_019...` with values from the API Keys and instance screens.

```bash
curl --request POST \
  --url http://localhost:8000/api/v1/messages/text \
  --header "Authorization: Bearer YOUR_ZAP_API_KEY" \
  --header "Content-Type: application/json" \
  --header "Idempotency-Key: 3f5b8269-72d4-4d48-a0d1-d892992c66db" \
  --data '{
    "instance_id": "ins_019...",
    "to": "5511999999999",
    "text": "Hello from ZAP"
  }'
```

Example response:

```json
{
  "data": {
    "id": "msg_019...",
    "status": "sending",
    "instance_id": "ins_019..."
  }
}
```
