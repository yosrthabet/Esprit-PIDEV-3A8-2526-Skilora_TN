---
description: Auto-rotate API accounts when hitting rate limits
---

# API Account Rotation on Rate Limit

Pattern for implementing automatic account/key rotation when API rate limits are hit.

## Core Pattern

```python
import os
import time
from dataclasses import dataclass
from typing import Optional

@dataclass
class ApiAccount:
    name: str
    key: str
    rate_limit_remaining: int = 100
    last_used: float = 0.0
    is_exhausted: bool = False

class RotatingApiClient:
    def __init__(self, accounts: list[ApiAccount], rate_limit_header: str = "X-RateLimit-Remaining"):
        self.accounts = accounts
        self.current_index = 0
        self.rate_limit_header = rate_limit_header
        self.session = requests.Session()
    
    @property
    def current(self) -> ApiAccount:
        """Get current active account, rotating if exhausted."""
        account = self.accounts[self.current_index]
        if account.is_exhausted:
            self.rotate()
            account = self.accounts[self.current_index]
        return account
    
    def rotate(self) -> bool:
        """Rotate to next available account. Returns False if all exhausted."""
        available = [a for a in self.accounts if not a.is_exhausted]
        if not available:
            return False
        # Pick account with highest remaining quota
        best = max(available, key=lambda a: a.rate_limit_remaining)
        self.current_index = self.accounts.index(best)
        return True
    
    def request(self, method: str, url: str, **kwargs) -> Optional[requests.Response]:
        """Make request with auto-rotation on 429/rate limit."""
        max_retries = len(self.accounts)
        
        for attempt in range(max_retries):
            account = self.current
            headers = kwargs.get("headers", {})
            headers["Authorization"] = f"Bearer {account.key}"
            kwargs["headers"] = headers
            
            resp = self.session.request(method, url, **kwargs)
            
            # Update rate limit tracking
            remaining = resp.headers.get(self.rate_limit_header)
            if remaining:
                account.rate_limit_remaining = int(remaining)
            
            # Handle rate limit
            if resp.status_code == 429:
                account.is_exhausted = True
                reset_after = int(resp.headers.get("Retry-After", 60))
                print(f"[RATE LIMIT] {account.name} exhausted, retry-after: {reset_after}s")
                
                if not self.rotate():
                    print("[ERROR] All accounts exhausted")
                    return resp  # Return the 429
                
                time.sleep(min(reset_after, 5))  # Brief backoff before retry
                continue
            
            account.last_used = time.time()
            return resp
        
        return None
```

## Usage Example (Shodan)

```python
# Load multiple Shodan keys from env
accounts = [
    ApiAccount(name="shodan-1", key=os.getenv("SHODAN_KEY_1")),
    ApiAccount(name="shodan-2", key=os.getenv("SHODAN_KEY_2")),
    ApiAccount(name="shodan-3", key=os.getenv("SHODAN_KEY_3")),
]

client = RotatingApiClient(accounts, rate_limit_header="X-RateLimit-Remaining")

# Use transparently - rotates automatically on 429
for ip in target_list:
    resp = client.request("GET", f"https://api.shodan.io/shodan/host/{ip}?key={client.current.key}")
    if resp and resp.status_code == 200:
        process_result(resp.json())
```

## Usage Example (FOFA)

```python
# FOFA uses email:key auth
class FofaRotatingClient(RotatingApiClient):
    def __init__(self, accounts: list[tuple[str, str]]):  # (email, key) pairs
        super().__init__([
            ApiAccount(name=f"fofa-{i}", key=f"{email}:{key}")
            for i, (email, key) in enumerate(accounts)
        ])
    
    def request(self, method: str, url: str, **kwargs) -> Optional[requests.Response]:
        account = self.current
        email, key = account.key.split(":")
        # FOFA specific auth
        kwargs.setdefault("params", {})["email"] = email
        kwargs["params"]["key"] = key
        return super().request(method, url, **kwargs)

# Load from env
ff_accounts = [
    (os.getenv("FOFA_EMAIL_1"), os.getenv("FOFA_KEY_1")),
    (os.getenv("FOFA_EMAIL_2"), os.getenv("FOFA_KEY_2")),
]
client = FofaRotatingClient(ff_accounts)
```

## Key Implementation Rules

1. **Never hardcode keys** — Always use environment variables or secure vault
2. **Track remaining quota** — Parse rate limit headers to preempt rotation
3. **Exponential backoff** — Brief sleep (1-5s) before switching accounts
4. **Fail fast** — Return 429 response when all accounts exhausted (don't infinite loop)
5. **Thread safety** — Use threading.Lock if accessing from multiple threads
6. **Metrics** — Log which account hit limits for monitoring

## Integration with Existing Scanners

Wrap your existing scanner functions:

```python
def scan_with_rotation(targets: list[str], accounts: list[ApiAccount]):
    client = RotatingApiClient(accounts)
    
    def probe_target(target: str) -> dict:
        resp = client.request("GET", target, timeout=10)
        return {"target": target, "status": resp.status_code if resp else "error"}
    
    with ThreadPoolExecutor(max_workers=10) as pool:
        results = list(pool.map(probe_target, targets))
    
    return results
```

## Environment Setup Template

```bash
# .env file (add to .gitignore!)
SHODAN_KEY_1=your_first_key
SHODAN_KEY_2=your_second_key
SHODAN_KEY_3=your_third_key

FOFA_EMAIL_1=email1@example.com
FOFA_KEY_1=key1
FOFA_EMAIL_2=email2@example.com
FOFA_KEY_2=key2
```
