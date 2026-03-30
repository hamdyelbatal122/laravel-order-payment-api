# Order and Payment Management API

Laravel-based API for managing orders and payments with support for multiple payment gateways.

## Features 

- JWT authentication
- Multiple payment gateway support (PayPal, Stripe, Credit Card)
- Full CRUD operations for orders
- Payment processing with validation
- RESTful API design
- Feature tests

## Business Rules

- Payments can only be processed for orders in 'confirmed' status
- Orders cannot be deleted if they have associated payments

## Architecture

### Payment Gateway Strategy Pattern

The system implements the Strategy Pattern for payment gateways:

- `PaymentGatewayInterface`: Contract defining payment processing
- `PaypalGateway`: PayPal payment implementation
- `StripeGateway`: Stripe payment implementation
- `CreditCardGateway`: Credit Card payment implementation
- `PaymentManager`: Factory for resolving payment gateways

### Database Schema

- **users**: Standard authentication fields with JWT support
- **orders**: user_id, total_amount, status (pending, confirmed, cancelled)
- **order_items**: order_id, product_name, quantity, price
- **payments**: order_id, transaction_id, gateway_name, amount, status (pending, successful, failed)

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy environment file:
   ```bash
   cp .env.example .env
   ```
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Generate JWT secret:
   ```bash
   php artisan jwt:secret
   ```
6. Configure database in `.env`
7. Run migrations:
   ```bash
   php artisan migrate
   ```

## Quick Start

1. **Install dependencies**: `composer install`
2. **Setup environment**: Copy `.env.example` to `.env` and configure
3. **Generate keys**: 
   - `php artisan key:generate`
   - `php artisan jwt:secret`
4. **Run migrations**: `php artisan migrate`
5. **Run tests**: `php artisan test`
6. **Start server**: `php artisan serve`

## Configuration

### Payment Gateways

Payment gateway configuration is stored in `config/payments.php`. You can override settings in `.env`:

```env
PAYMENT_DEFAULT_GATEWAY=stripe
PAYPAL_ENABLED=true
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
STRIPE_ENABLED=true
STRIPE_PUBLIC_KEY=your_stripe_public_key
STRIPE_SECRET_KEY=your_stripe_secret_key
CREDITCARD_ENABLED=true
CREDITCARD_API_KEY=your_creditcard_api_key
CREDITCARD_API_SECRET=your_creditcard_api_secret
```

## API Endpoints

### Authentication

- `POST /api/register` - Register a new user
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }
  ```

- `POST /api/login` - Login user
  ```json
  {
    "email": "john@example.com",
    "password": "password123"
  }
  ```

### Orders

All order endpoints require JWT authentication (Bearer token in Authorization header).

- `GET /api/orders` - List orders (with pagination and status filtering)
  - Query parameters: `?status=pending|confirmed|cancelled&page=1`
  
- `POST /api/orders` - Create a new order
  ```json
  {
    "items": [
      {
        "product_name": "Product 1",
        "quantity": 2,
        "price": 29.99
      }
    ]
  }
  ```

- `GET /api/orders/{id}` - Get a specific order

- `PUT /api/orders/{id}` - Update an order
  ```json
  {
    "status": "confirmed",
    "items": [...]
  }
  ```

- `DELETE /api/orders/{id}` - Delete an order (only if no payments exist)

### Payments

All payment endpoints require JWT authentication.

- `POST /api/payments` - Process a payment
  ```json
  {
    "order_id": 1,
    "method": "stripe|paypal|creditcard"
  }
  ```

- `GET /api/payments` - Get all payments for the authenticated user (with pagination)
- `GET /api/payments/order/{order_id}` - Get all payments for a specific order

## Testing

Run the test suite:

```bash
php artisan test
```

The test suite covers:
- Payment processing for confirmed orders only
- Payment gateway strategy (PayPal, Stripe, and Credit Card)
- Order deletion restrictions
- Payment validation and error handling

## Code Standards

- PSR-12 coding standards
- SOLID principles
- Dependency injection in controllers
- Form requests for validation
- API resources for consistent responses

## Project Structure

```
app/
├── Contracts/
│   └── PaymentGatewayInterface.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── OrderController.php
│   │       └── PaymentController.php
│   ├── Requests/
│   │   ├── LoginRequest.php
│   │   ├── RegisterRequest.php
│   │   ├── StoreOrderRequest.php
│   │   ├── StorePaymentRequest.php
│   │   └── UpdateOrderRequest.php
│   └── Resources/
│       ├── OrderResource.php
│       ├── OrderItemResource.php
│       ├── PaymentResource.php
│       └── UserResource.php
├── Models/
│   ├── Order.php
│   ├── OrderItem.php
│   └── Payment.php
└── Services/
    ├── PaymentGateways/
    │   ├── PaypalGateway.php
    │   ├── StripeGateway.php
    │   └── CreditCardGateway.php
    └── PaymentManager.php
```

## Adding a New Payment Gateway

The system is designed with extensibility in mind. Adding a new payment gateway requires minimal code changes thanks to the Strategy Pattern implementation.

### Step-by-Step Guide

#### 1. Create the Gateway Class

Create a new gateway class in `app/Services/PaymentGateways/` that implements `PaymentGatewayInterface`:

```php
<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;

class NewGateway implements PaymentGatewayInterface
{
    public function process(float $amount, array $details): array
    {
        // Your payment processing logic here
        $transactionId = 'NEWGATEWAY_' . uniqid();
        
        // Simulate or call actual API
        $success = true; // Your logic here
        
        return [
            'transaction_id' => $transactionId,
            'status' => $success ? 'successful' : 'failed',
            'gateway' => 'newgateway',
            'amount' => $amount,
            'message' => $success 
                ? 'Payment processed successfully via NewGateway' 
                : 'Payment failed via NewGateway',
        ];
    }
}
```

#### 2. Register the Gateway in PaymentManager

Update `app/Services/PaymentManager.php` to include your new gateway:

```php
public function resolve(string $gatewayName): PaymentGatewayInterface
{
    $gatewayName = strtolower($gatewayName);
    
    return match ($gatewayName) {
        'paypal' => new PaypalGateway(),
        'stripe' => new StripeGateway(),
        'creditcard' => new CreditCardGateway(),
        'newgateway' => new NewGateway(), // Add this line
        default => throw new InvalidArgumentException(
            "Payment gateway '{$gatewayName}' is not supported."
        ),
    };
}

public function getAvailableGateways(): array
{
    return config('payments.gateways', ['paypal', 'stripe', 'creditcard', 'newgateway']); // Add to array
}
```

#### 3. Update Configuration

Add your gateway to `config/payments.php`:

```php
'gateways' => [
    'paypal',
    'stripe',
    'creditcard',
    'newgateway', // Add this
],

'newgateway' => [
    'enabled' => env('NEWGATEWAY_ENABLED', true),
    'api_key' => env('NEWGATEWAY_API_KEY'),
    'api_secret' => env('NEWGATEWAY_API_SECRET'),
],
```

#### 4. Update Validation

Update `app/Http/Requests/StorePaymentRequest.php` to include your gateway in validation:

```php
public function rules(): array
{
    return [
        'order_id' => ['required', 'integer', 'exists:orders,id'],
        'method' => ['required', 'string', 'in:paypal,stripe,creditcard,newgateway'], // Add here
    ];
}
```

#### 5. Add Environment Variables (Optional)

Add gateway-specific configuration to `.env`:

```env
NEWGATEWAY_ENABLED=true
NEWGATEWAY_API_KEY=your_api_key
NEWGATEWAY_API_SECRET=your_api_secret
```

That's it! Your new payment gateway is now integrated and ready to use.

### Example Usage

After adding your gateway, you can use it immediately:

```bash
POST /api/payments
{
    "order_id": 1,
    "method": "newgateway"
}
```

This approach allows adding new gateways with minimal code changes while keeping the core payment logic unchanged.

## API Documentation

A Postman collection is available at `postman/collection.json` for testing the API.

To import:
1. Open Postman
2. Click "Import"
3. Select `postman/collection.json`

The collection includes all endpoints with request/response examples, including Credit Card payment processing.

## Project Files

- Laravel project code
- Postman collection (`postman/collection.json`)
- README with setup instructions and API documentation

## API Endpoints Summary

### Authentication (Public)
- `POST /api/register` - Register new user
- `POST /api/login` - Login user

### Orders (Protected - JWT Required)
- `GET /api/orders` - List orders (with status filter & pagination)
- `POST /api/orders` - Create order
- `GET /api/orders/{id}` - Get order by ID
- `PUT /api/orders/{id}` - Update order
- `DELETE /api/orders/{id}` - Delete order

### Payments (Protected - JWT Required)
- `GET /api/payments` - Get all payments (with pagination)
- `POST /api/payments` - Process payment (supports: stripe, paypal, creditcard)
- `GET /api/payments/order/{orderId}` - Get payments for order

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
