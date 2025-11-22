# Architecture (Hexagonal)

## Diagram
```mermaid
flowchart TB
  subgraph Domain
    DM[Domain Models]
    DS[Domain Services]
  end
  subgraph Ports
    BP[BrokerPort]
    DP[DBPort]
    CP[CachePort]
    QP[QueuePort]
    HP[HttpClientPort]
    LP[LoggingPort]
    EP[ExceptionPort]
  end
  subgraph Adapters
    DBA[MySQL Adapter]
    CA[Redis Adapter]
    QA[Redis Queue Adapter]
    HPA[Symfony HttpClient]
    DhanA[Dhan Adapter]
    LA[Monolog Adapter]
  end
  A[CLI/API] --> DS
  DS -->|contracts| Ports
  Ports --> Adapters
  Adapters --> External[(Broker, DB, Cache, Queue)]
```

## Namespaces
- `App\Domain`: models, value objects, services.
- `App\Infrastructure`: adapters and ports.
- `App\Shared`: utilities (DateTime, String, Array, JSON, Enum, Validation, Currency, Number).
- `App\Tests`: test utilities.

## DI
- Constructor injection; factories for adapters; config via env.

