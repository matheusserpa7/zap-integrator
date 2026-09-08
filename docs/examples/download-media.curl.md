# Download inbound media

Requires the `media:read` ability. Replace `med_019...` with the id from the customer webhook `data.media.id` object.

```bash
curl --request GET \
  --url http://localhost:8000/api/v1/media/med_019... \
  --header "Authorization: Bearer YOUR_ZAP_API_KEY" \
  --output inbound.jpg
```
