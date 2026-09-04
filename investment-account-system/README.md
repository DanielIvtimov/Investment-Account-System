# Investment Account System

## Local Setup

### 1. Clone the repository

Clone the repository and enter the project directory:

```bash
git clone https://github.com/DanielIvtimov/Investment-Account-System.git
cd Investment-Account-System/investment-account-system
```

### 2. Install dependencies

Install the PHP dependencies using Composer:

```bash
composer install
```

### 3. Configure the environment

Create the local environment file from the provided example:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure the database

Create a MySQL database named `investment_account_system`.

Then update the database settings in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=investment_account_system
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run the database migrations

Create the required database tables by running:

```bash
php artisan migrate
```

### 6. Seed demo data

Populate the database with demo clients and transactions:

```bash
php artisan db:seed
```

### 7. Start the application

Start the Laravel development server:

```bash
php artisan serve
```

## API

### Create Client

Create a new client account.

**Endpoint**

```text
POST /api/clients
```
**Request**
```json
{
    "name": "Ana"
}
```
**Response — 201 Created**
```json
{
    "id": 1,
    "name": "Ana"
}
```

### Get Account State

Get the current cash balance and instrument holdings for a client.

**Endpoint**

```text
GET /api/clients/{client}
```

**Response — 200 OK**
```json
{
    "id": 1,
    "name": "Ana",
    "cash_balance": "860.00",
    "holdings": [
        {
            "instrument_ticker": "AAPL",
            "quantity": 2
        }
    ]
}
```
### Deposit

Add cash to the client's account.

**Endpoint**

```text
POST /api/clients/{client}/deposit
```
**Request**
```json
{
    "amount": "1000.00"
}
```

**Response — 201 Created**
```json
{
    "id": 1,
    "client_id": 1,
    "type": "deposit",
    "cash_amount": "1000.00",
    "created_at": "2026-09-04T12:00:00.000000Z"
}
```

### Withdraw

Remove cash from the client's account.

**Endpoint**

```text
POST /api/clients/{client}/withdraw
```
**Request**
```json
{
    "amount": "200.00"
}
```
**Response — 201 Created**
```json
{
    "id": 2,
    "client_id": 1,
    "type": "withdrawal",
    "cash_amount": "200.00",
    "created_at": "2026-09-04T12:05:00.000000Z"
}
```

### Buy

Buy a quantity of an instrument using the client's available cash.

**Endpoint**

```text
POST /api/clients/{client}/buy
```
**Request**
```json
{
    "ticker": "AAPL",
    "quantity": 5,
    "price_per_unit": "100.00"
}
```
**Response — 201 Created**
```json
{
    "id": 3,
    "client_id": 1,
    "type": "buy",
    "cash_amount": "500.00",
    "instrument_ticker": "AAPL",
    "quantity": 5,
    "price_per_unit": "100.0000",
    "created_at": "2026-09-04T12:10:00.000000Z"
}
```
### Sell

Sell a quantity of an instrument currently held by the client.

**Endpoint**

```text
POST /api/clients/{client}/sell
```
**Request**
```json
{
    "ticker": "AAPL",
    "quantity": 3,
    "price_per_unit": "120.00"
}
```
**Response — 201 Created**
```json
{
    "id": 4,
    "client_id": 1,
    "type": "sell",
    "cash_amount": "360.00",
    "instrument_ticker": "AAPL",
    "quantity": 3,
    "price_per_unit": "120.0000",
    "created_at": "2026-09-04T12:15:00.000000Z"
}
```

### Transaction History

Get the complete transaction history for a client.

**Endpoint**

```text
GET /api/clients/{client}/transactions
```
**Response — 200 OK**
```json
{
    "data": [
        {
            "id": 1,
            "client_id": 1,
            "type": "deposit",
            "cash_amount": "1000.00",
            "instrument_ticker": null,
            "quantity": null,
            "price_per_unit": null,
            "created_at": "2026-09-04T12:00:00.000000Z"
        },
        {
            "id": 2,
            "client_id": 1,
            "type": "buy",
            "cash_amount": "500.00",
            "instrument_ticker": "AAPL",
            "quantity": 5,
            "price_per_unit": "100.0000",
            "created_at": "2026-09-04T12:10:00.000000Z"
        }
    ]
}
```
## Business Rules

The system enforces the following business rules:

- Each client has one account and one currency. Currency conversion is not supported.
- Transactions are append-only. Existing transactions cannot be updated or deleted.
- Deposits and withdrawals only affect the client's cash balance.
- A buy requires sufficient available cash. The cash amount is calculated as `quantity × price_per_unit`.
- A sell requires sufficient holdings of the requested instrument. The sell proceeds are calculated as `quantity × price_per_unit`.
- Sell prices may differ from previous buy prices. Profit and loss are not calculated.
- Quantity must be a positive integer.
- Cash amounts and prices must be positive.
- Buy and sell amounts are rounded to two decimal places using decimal arithmetic.
- Each client's cash balance and holdings are isolated from other clients.
- Invalid requests are rejected without creating a transaction or changing the account state.
- Validation errors return `422 Unprocessable Entity`.
- Business rule violations such as insufficient funds or insufficient holdings return `409 Conflict`.
- A missing client returns `404 Not Found`.

## Why This Way

### Transactions as the source of truth

The transaction table is the source of truth for account state. Cash balance is calculated from deposits, withdrawals, buys, and sells, while holdings are calculated from buy and sell transactions.

This keeps the system append-only and avoids storing duplicated balance or holding state that could become inconsistent with the transaction history.

### Service layer for business rules

Transaction creation is handled by `TransactionService` instead of placing business rules inside controllers.

The service is responsible for checking available cash and holdings, calculating transaction amounts, and creating the transaction only when the operation is valid.

Form Requests handle input validation, while the service handles rules that depend on the client's current account state.

### Database transactions and concurrency

State-dependent operations such as withdrawals, buys, and sells run inside database transactions and lock the client's row with `lockForUpdate()`.

This prevents two concurrent operations for the same client from both reading the same available balance or holdings and exceeding the account's actual state.

### Money precision

Cash amounts are stored as `DECIMAL(15,2)` and prices as `DECIMAL(15,4)` in the database.

Buy and sell notional amounts are calculated using BCMath instead of floating-point arithmetic and are rounded to two decimal places using half-up rounding.

For example, `3 × 100.3333` results in a cash amount of `301.00`.

### Append-only ledger

Transactions are never updated or deleted after they are created.

Instead of modifying historical data, every account movement is recorded as a new transaction. This preserves the complete history of how the current account state was produced.

### Scope

The implementation intentionally stays within the requirements of the assignment.

Authentication, authorization, instrument master data, profit and loss calculation, multiple currencies, foreign exchange, and other infrastructure concerns are outside the current scope.

## Tests

Run the full test suite with:

```bash
php artisan test
```
