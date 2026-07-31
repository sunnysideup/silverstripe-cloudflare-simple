# tl;dr

## Add to .env file:

```sh
CLOUDFLARE_PURGE_ZONE_ID="your-zone-id"
CLOUDFLARE_PURGE_API_TOKEN="your-api-token"
```

- `your-zone-id` is your zone ID found on the [Account](https://dash.cloudflare.com/?to=/:account/home) home
- `your-api-token` is an [API token](https://dash.cloudflare.com/profile/api-tokens/) with permission to purging cache

## Add to your yml file:

### To purge specific hosts

```yml
Sunnysideup\CloudflareSimple\CloudflarePurgeCache:
  purge_hosts:
    - 'example.com'
    - 'test.example.com'
```

### To purge all hosts

```yml
Sunnysideup\CloudflareSimple\CloudflarePurgeCache:
  purge_hosts:
```

or simply remove the yml file.

## Notes

When running dev flush from CLI, you might see it flush cache twice.  
For the free-tier plan, it might hit the API rate limit when dev flush'ing several times a minute.
