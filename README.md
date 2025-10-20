# 💰 Wallet Service (Laravel + Docker + MySQL + Redis)

Este servicio permite registrar **transacciones de depósito y retiro**, procesarlas de forma asíncrona en cola (Redis), y consultar el **saldo** y el **historial de movimientos** de un usuario.

---

## 🐳 Requisitos previos

- Docker y Docker Compose instalados
- Puerto `8000` libre (para la API)
- Puerto `3307` libre (para MySQL local)
- Puerto `6379` libre (para Redis)

---

## ⚙️ Configuración inicial

### 1️⃣ Clonar el repositorio

```bash
git clone https://github.com/Kevam97/refacil.git
cd refacil
cp .env.example .env
```

### 2️⃣ Construir e iniciar los servicios

```bash
docker-compose build
docker-compose up -d
```

### 3️⃣ Verificar contenedores activos

```bash
docker ps
```

Deberías ver los siguientes contenedores:

| Servicio | Contenedor       | Puerto |
|-----------|------------------|--------|
| Laravel   | `wallet-app`     | 8000   |
| MySQL     | `wallet-db`      | 3307   |
| Redis     | `wallet-redis`   | 6379   |
| Worker    | `wallet-worker`  | —      |

---

## 🧱 Migraciones

Ejecuta las migraciones dentro del contenedor de la app:

```bash
docker exec -it wallet-app php artisan migrate
```

---

## 🚀 Endpoints de la API

Base URL:
```
http://localhost:8000/api/v1
```

---

### 🧩 1. Crear transacción

**POST** `/transactions`

Registrar un **depósito** o **retiro**.

#### 🔸 Payload
```json
{
  "transaction_id": "7f3b5a10-1111-4e3c-8a2d-4a92e5117c11",
  "user_id": "user-001",
  "amount": 200.00,
  "type": "deposit",
  "timestamp": "2025-10-18T23:00:00Z",
  "currency": "USD"
}
```

#### 🔸 Respuesta esperada
```json
{
  "status": "accepted",
  "transaction_id": "7f3b5a10-1111-4e3c-8a2d-4a92e5117c11"
}
```

> La transacción se envía a una cola (`transactions`) y será procesada de forma asíncrona.

---

### 🧩 2. Consultar historial de transacciones

**GET** `/transactions/{user_id}`

Ejemplo:
```
GET http://localhost:8000/api/v1/transactions/user-001
```

#### 🔸 Respuesta esperada
```json
{
  "data": [
    {
      "id": 1,
      "transaction_id": "7f3b5a10-1111-4e3c-8a2d-4a92e5117c11",
      "amount": "200.00",
      "type": "deposit",
      "status": "completed",
      "occurred_at": "2025-10-18T23:00:00Z"
    },
    {
      "id": 2,
      "transaction_id": "3b4d2fa5-5555-4e3c-9b11-1a67e4227c22",
      "amount": "50.00",
      "type": "withdraw",
      "status": "completed",
      "occurred_at": "2025-10-18T23:10:00Z"
    }
  ]
}
```

---

### 🧩 3. Consultar saldo actual

**GET** `/balance/{user_id}`

Ejemplo:
```
GET http://localhost:8000/api/v1/balance/user-001
```

#### 🔸 Respuesta esperada
```json
{
  "user_id": "user-001",
  "currency": "USD",
  "balance": "150.00"
}
```

---

## ⚡ Colas y procesamiento

El servicio usa Redis para ejecutar trabajos en cola.

- Cola usada: `transactions`
- Worker: `wallet-worker`

### Ver logs del worker

```bash
docker logs -f wallet-worker
```

### Procesar manualmente (opcional)

```bash
docker exec -it wallet-app php artisan queue:work --queue=transactions
```

---

## 🧠 Comandos útiles

```bash
# Ver contenedores activos
docker ps

# Detener los servicios
docker-compose down

# Ver logs de la aplicación
docker logs -f wallet-app

# Entrar al contenedor Laravel
docker exec -it wallet-app bash
```

---

## 📦 Servicios disponibles

| Servicio | Descripción                  | Puerto |
|-----------|------------------------------|--------|
| `app`     | API Laravel                  | 8000   |
| `db`      | Base de datos MySQL          | 3307   |
| `redis`   | Motor de colas               | 6379   |
| `worker`  | Procesador de transacciones  | —      |

---

## 🧪 Ejemplos rápidos con `curl`

### Crear depósito
```bash
curl -X POST http://localhost:8000/api/v1/transactions   -H "Content-Type: application/json"   -d '{
    "transaction_id": "txn-001",
    "user_id": "user-001",
    "amount": 100.00,
    "type": "deposit",
    "timestamp": "2025-10-18T15:00:00Z",
    "currency": "USD"
  }'
```

### Consultar saldo
```bash
curl http://localhost:8000/api/v1/balance/user-001
```

---

## 🧾 Resumen

| Operación                  | Método | Endpoint                          | Cola/Sync | Estado |
|-----------------------------|---------|------------------------------------|------------|---------|
| Registrar transacción       | POST    | `/transactions`                   | Asíncrono (cola) | ✅ |
| Consultar historial usuario | GET     | `/transactions/{user_id}`         | Directo | ✅ |
| Consultar saldo actual      | GET     | `/balance/{user_id}`              | Directo | ✅ |

---

## 📚 Tecnologías usadas

- **Laravel 11**
- **MySQL 8**
- **Redis 7**
- **Docker & Docker Compose**
- **Laravel Queue Jobs**
- **bcMath (precisión decimal para saldos)**

---
## Resumen de la arquitectura

- **Tecnología: Laravel 10+ (Eloquent, Jobs/Queues, FormRequests)**.

- **DB: MySQL (tablas normalizadas: users, accounts o balances, transactions, alerts)**.

- **Procesamiento: escritura de transacción a través de API -> validador -> Job en cola que aplica la lógica de negocio (actualiza balance) dentro de una transacción DB.**

- **Observabilidad: auditoría básica (tabla transactions y alerts)**

- **Escalado: stateless API + workers (horizontales) con Redis (o RabbitMQ) para colas**.

- **Despliegue: Dockerfile + docker-compose para desarrollo.**
---
## 🔒 Seguridad, Idempotencia y Validación

Este servicio implementa varias medidas para garantizar la integridad y consistencia de las transacciones financieras.

### ⚙️ Idempotencia
Cada transacción enviada debe incluir un campo único `transaction_id`.  
- Si se intenta registrar una transacción con un `transaction_id` ya existente, la API debe retornar:
  - **HTTP 409 (Conflict)** o
  - El mismo recurso existente, evitando procesar duplicados.

### ✅ Validaciones
Antes de procesar cualquier solicitud, se aplican las siguientes reglas:
- `amount` debe ser mayor a **0**
- `timestamp` debe tener un formato de fecha válido
- `type` debe ser uno de los valores permitidos (`deposit`, `withdraw`)
- Se valida mediante un **FormRequest** (`TransactionRequest`) centralizado

### 🔐 Autenticación y Autorización
Si el servicio se utiliza dentro de un entorno de microservicios, se recomienda implementar alguno de los siguientes mecanismos:
- **Mutual TLS (mTLS)**
- **JWT** a través de un API Gateway
- Autenticación interna mediante tokens firmados

### 🚦 Rate Limiting
Para evitar abusos, se sugiere implementar limitación de peticiones:
- Por dirección **IP**
- Por **API key** o **user_id**
- Utilizando el middleware de Laravel `throttle`

### 🧾 Auditoría y Trazabilidad
- Las transacciones **nunca se eliminan**, solo cambian de estado (`pending`, `processed`, `failed`)
- Se conserva el campo `metadata` completo para permitir auditorías posteriores
- Se recomienda implementar logs estructurados en JSON para trazabilidad en sistemas distribuidos
---
## 💡 Preguntas Conceptuales

### 1️⃣ ¿Cómo manejar los picos altos de transacciones?

- La API debe ser **sin estado (stateless)**, lo que permite escalar fácilmente agregando más instancias del servicio cuando aumenta la carga.  
- Las transacciones entrantes se registran rápidamente como **"pendientes"** y se envían a una **cola** para su procesamiento posterior.  
- Los **workers** que procesan las transacciones pueden escalarse automáticamente según la cantidad de mensajes en la cola.  
- Si la base de datos se vuelve un cuello de botella, puede dividirse por usuario o cuenta (sharding).  
- Los **balances** y datos de lectura frecuentes pueden almacenarse temporalmente en cache.  
- En momentos de carga extrema, se pueden procesar operaciones por lotes (batch) para mejorar el rendimiento.  
- Se recomienda tener métricas y alertas para detectar sobrecarga y activar el **autoescalado**.

---

### 2️⃣ ¿Qué estrategias usar para prevenir fraudes?

- Establecer **límites por transacción** y también por períodos de tiempo (por ejemplo, monto máximo diario).  
- Detectar **patrones anormales**, como actividad inusual en horarios o ubicaciones.  
- Aplicar una **verificación adicional** (como 2FA o revisión manual) si se detecta una operación sospechosa.  
- Monitorear la **velocidad de las transacciones** y bloquear aquellas que superen límites razonables.  
- Mantener **listas negras** de cuentas o IPs con comportamiento riesgoso.  
- Guardar todos los eventos para auditorías y activar **alertas en tiempo real** ante anomalías.  
- Si se requiere, integrar con servicios externos de **verificación de identidad (KYC)** o detección de fraude.

---

### 3️⃣ ¿Cómo mejorar si el sistema se vuelve lento por alta concurrencia?

- Revisar qué partes generan bloqueos o esperas (por ejemplo, actualizaciones simultáneas de un mismo balance).  
- Evitar bloqueos largos: permitir que las operaciones se procesen en paralelo o con actualización diferida.  
- Aumentar el número de **workers** y agregar **réplicas de base de datos** para repartir la carga.  
- Usar **cache** para las consultas más frecuentes y enviar tareas pesadas a procesos en segundo plano.  
- Optimizar las consultas y los índices de base de datos para que respondan más rápido.  
