# Merchantrack - POS & Inventory Management System

A comprehensive Point of Sale (POS) and Inventory Management System with Consumer Demand Forecasting for Merchantrack.

## Features

- **User Authentication & Role Management**
  - Admin and Staff roles
  - Admin can create and manage users
  - Secure login system

- **Point of Sale (POS)**
  - Real-time product search
  - Shopping cart functionality
  - Multiple payment methods (Cash, Card, Mobile Payment)
  - Tax and discount calculations
  - Receipt generation

- **Product Management**
  - Product CRUD operations
  - Category management
  - SKU tracking
  - Stock quantity management
  - Low stock alerts
  - Product images

- **Inventory Management**
  - Stock in/out tracking
  - Inventory adjustments
  - Movement history
  - Reorder level alerts

- **Sales Management**
  - Sales history
  - Sales reports
  - Filter by date range
  - Sale details view

- **Demand Forecasting**
  - AI-powered demand prediction
  - Historical data analysis
  - Confidence level indicators
  - Forecast date planning

- **Dashboard**
  - Real-time statistics
  - Recent sales overview
  - Low stock alerts
  - Revenue tracking

## Requirements

- PHP >= 7.2.5
- MySQL/MariaDB
- Composer
- Node.js & NPM (for frontend assets)

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd merchantrack
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install NPM dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure Database**
   Edit `.env` file and set your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=merchantrack_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Create Database**
   Create a MySQL database named `merchantrack_database`:
   ```sql
   CREATE DATABASE merchantrack_database;
   ```

7. **Run Migrations**
   ```bash
   php artisan migrate
   ```

8. **Seed Database (Create Admin User)**
   ```bash
   php artisan db:seed
   ```

9. **Create Storage Link (for product images)**
   ```bash
   php artisan storage:link
   ```

10. **Compile Assets (Optional)**
    ```bash
    npm run dev
    # or for production
    npm run prod
    ```

11. **Start Development Server**
    ```bash
    php artisan serve
    ```

## Default Admin Credentials

After running the seeder, you can login with:
- **Email:** admin@merchantrack.com
- **Password:** password

**⚠️ Important:** Change the default password after first login!

## Usage

1. **Login**
   - Navigate to `/login`
   - Use admin credentials to login

2. **Create Users (Admin Only)**
   - Go to Users menu
   - Click "Add New User"
   - Fill in user details and assign role (Admin or Staff)

3. **Add Categories**
   - Navigate to Categories
   - Create product categories

4. **Add Products**
   - Go to Products
   - Click "Add Product"
   - Fill in product details including SKU, price, and initial stock

5. **Process Sales**
   - Go to Point of Sale
   - Search and add products to cart
   - Set tax and discount if needed
   - Select payment method
   - Click "Process Sale"

6. **View Sales History**
   - Navigate to Sales
   - Filter by date range or search by sale number

7. **Manage Inventory**
   - Go to Inventory
   - View all inventory movements
   - Filter by product or movement type

8. **Generate Demand Forecasts**
   - Navigate to Demand Forecast
   - Click "Generate Forecast"
   - Select product and forecast date
   - View predicted demand

## Database Structure

- **users** - User accounts with roles
- **categories** - Product categories
- **products** - Product information
- **sales** - Sales transactions
- **sale_items** - Individual items in each sale
- **inventory_movements** - Stock movement history
- **demand_forecasts** - Demand predictions

## Technologies Used

- Laravel 7.x
- Bootstrap 5
- jQuery
- Font Awesome
- MySQL

## Security Features

- CSRF protection
- Password hashing
- Role-based access control
- Authentication middleware
- Input validation

## Support

For issues or questions, please contact the development team.

## License

This project is proprietary software for Merchantrack.
