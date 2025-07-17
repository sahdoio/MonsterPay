# 🧟 MonsterPay — Rinha de Backend 2025 (HyPerf + Docker)

**MonsterPay** is a high-performance, fault-tolerant payment gateway built with [HyPerf](https://hyperf.io/) and PHP.  
Forged in the depths of legacy code, it thrives where others timeout.  
Built for the chaos of the **Rinha de Backend 2025**, it may look like a monster — but it's fast, smart, and dangerously efficient.

![The Monster is Back!](/docs/evilphp.png)

---

## ⚙️ Tech Stack

| Layer       | Stack/Tools                              |
|-------------|-------------------------------------------|
| Backend     | **HyPerf 3.1**, PHP **8.3**, Swoole       |
| Caching     | Redis (throttle + summary cache)          |
| Container   | Docker + Docker Compose                   |
| Load Balancer | Nginx (with two web API instances)      |
| Monitoring  | Health-check caching + Redis TTL strategy |
| DB          | ❌ No SQL here — raw memory & Redis only   |

---

## 🚀 Core Features

- Smart routing between **Default** and **Fallback** payment processors
- Health-check aware failover (with Redis cooldown)
- Summary aggregation of payments by processor type
- High-performance Hyperf architecture with coroutine-based handlers
- Fully containerized: runs with `make up` and dies with `make stop`
- Implements **`POST /payments`** and **`GET /payments-summary`** as required
- No bloat, no beauty — only raw backend strength

---

## 🧠 Processing Strategy

1. Check cached health status (Redis key with 5s TTL)
2. Prefer the **default** processor if available
3. Fall back to the **fallback** processor only when needed
4. Record payment count and amount per processor type
5. Expose a clean summary for consistency auditing

---

## 🧪 Testing the Beast

```bash
make test           # run tests (if implemented)
make up             # build + up backend stack
make logs           # stream container logs
make stop           # stop and clean up
````

---

## ⚔️ Rinha Deployment Notes

* Two API instances behind Nginx on port `9999`
* Redis included in the same compose file
* All services run under `payment-processor` Docker network
* CPU & Memory limits applied: **≤ 1.5 CPUs**, **≤ 350MB**
* Summary endpoint matches audit format 100%

---

## 📦 Folder Structure

```
monster-pay/
├── app/
│   ├── Controller/
│   ├── Service/
│   ├── Client/
│   └── Repository/
├── config/
├── docker/
│   ├── nginx/
│   └── php/
├── runtime/
├── tests/
├── composer.json
└── docker-compose.yml
```

---

## 🧟 Final Words

> MonsterPay doesn’t care how it looks.
> It cares about uptime, performance, and profit.
> It’s ugly. It’s PHP. And it’s still alive.
