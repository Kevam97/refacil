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
git clone https://github.com/tuusuario/wallet-service.git
cd wallet-service
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