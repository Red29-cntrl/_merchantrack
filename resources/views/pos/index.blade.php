@extends('layouts.app')

@section('title', 'Point of Sale')

@section('styles')
<style>
    .pos-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        max-height: 500px;
        overflow-y: auto;
    }
    .product-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    .product-card:hover {
        border-color: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .product-card.out-of-stock {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .cart-item .quantity-input,
    .cart-item .unit-select {
        width: 80px;
        padding: 6px;
        font-size: 14px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .cart-item .unit-select {
        width: 100px;
    }
    .cart-item {
        border-bottom: 1px solid #eee;
        padding: 12px 0;
    }
    .cart-item strong {
        font-size: 16px;
    }
    .cart-item label {
        font-size: 14px;
        font-weight: 500;
    }
    .cart-summary {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
    }
    /* Custom Alert Modal */
    .custom-alert-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s;
    }
    .custom-alert-modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .custom-alert-content {
        background-color: white;
        border: 2px solid #852E4E;
        border-radius: 12px;
        padding: 0;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        animation: slideDown 0.3s;
    }
    .custom-alert-header {
        background-color: #852E4E;
        color: white;
        padding: 20px;
        border-radius: 10px 10px 0 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }
    .custom-alert-header i {
        font-size: 24px;
    }
    .custom-alert-body {
        padding: 25px;
        color: #4C1D3D;
        font-size: 16px;
        line-height: 1.6;
        text-align: center;
    }
    .custom-alert-footer {
        padding: 15px 25px;
        border-top: 1px solid #DC586D;
        display: flex;
        justify-content: center;
        background-color: #f8f9fa;
        border-radius: 0 0 10px 10px;
    }
    .custom-alert-btn {
        background-color: #852E4E;
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .custom-alert-btn:hover {
        background-color: #4C1D3D;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideDown {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    /* Barcode Scanner Modal */
    #barcode-scanner-modal {
        z-index: 10001;
    }
    #barcode-scanner-container {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }
    #barcode-scanner-container video {
        width: 100%;
        border-radius: 8px;
    }
    .scanner-controls {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 15px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-cash-register me-2"></i>Point of Sale</h2>
    
    <script>
    // Number formatting function with commas and decimal points
    function formatNumber(num) {
        return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    </script>

    <div class="pos-container">
        <div>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="product-search" class="form-label">
                                <i class="fas fa-search me-2"></i>Search Product
                            </label>
                            <input type="text" id="product-search" class="form-control" 
                                   placeholder="Search by product name or SKU..." autofocus>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="button" id="scan-barcode-btn" style="margin-top: 0;">
                                <i class="fas fa-camera me-2"></i>Scan
                            </button>
                        </div>
                        <div class="col-md-4">
                            <label for="category-filter" class="form-label">
                                <i class="fas fa-filter me-2"></i>Filter by Category
                            </label>
                            <select id="category-filter" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Products</h5>
                </div>
                <div class="card-body">
                    <div class="product-grid" id="product-grid">
                        @foreach($products as $product)
                        <div class="product-card {{ $product->quantity <= 0 ? 'out-of-stock' : '' }}" 
                             data-product-id="{{ $product->id }}"
                             data-product-name="{{ $product->name }}"
                             data-product-sku="{{ $product->sku }}"
                             data-product-barcode="{{ $product->barcode ?? '' }}"
                             data-product-price="{{ $product->price }}"
                             data-product-stock="{{ $product->quantity }}"
                             data-product-unit="{{ $product->unit ?? 'pcs' }}"
                             data-category-id="{{ $product->category_id ?? '' }}">
                            <strong>{{ $product->name }}</strong><br>
                            <small class="text-muted">{{ $product->sku }}</small><br>
                            <span class="badge bg-primary">₱{{ number_format($product->price, 2) }}</span><br>
                            <small class="product-stock">Stock: {{ number_format($product->quantity, 0) }} {{ ucfirst($product->unit ?? 'pcs') }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Cart</h5>
                </div>
                <div class="card-body">
                    <div id="cart-items">
                        <p class="text-muted text-center">No items in cart</p>
                    </div>
                    <div class="cart-summary mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="subtotal">₱0.00</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tax (%)</label>
                            <input type="number" step="0.01" class="form-control" id="tax-rate" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount</label>
                            <input type="number" step="0.01" class="form-control" id="discount" value="0">
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong id="total">₱0.00</strong>
                        </div>
                        <button class="btn btn-success w-100" id="process-sale">
                            <i class="fas fa-check me-2"></i>Process Sale
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Barcode Scanner Modal -->
<div class="modal fade" id="barcode-scanner-modal" tabindex="-1" aria-labelledby="barcodeScannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #852E4E; color: white;">
                <h5 class="modal-title" id="barcodeScannerModalLabel">
                    <i class="fas fa-barcode me-2"></i>Barcode Scanner
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="close-scanner-btn"></button>
            </div>
            <div class="modal-body">
                <div id="barcode-scanner-container"></div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Alert Modal -->
<div id="customAlertModal" class="custom-alert-modal">
    <div class="custom-alert-content">
        <div class="custom-alert-header">
            <i id="alertIcon" class="fas fa-exclamation-triangle"></i>
            <h5 class="mb-0" id="alertTitle">Alert</h5>
        </div>
        <div class="custom-alert-body">
            <p id="alertMessage" class="mb-0"></p>
        </div>
        <div class="custom-alert-footer">
            <button class="custom-alert-btn" onclick="closeCustomAlert()">OK</button>
        </div>
    </div>
</div>

<!-- Success Modal with Receipt -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #852E4E; color: white;">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Sale Processed Successfully
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="receipt-modal-body">
                <!-- Receipt content will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print me-2"></i>Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Receipt styling - A5 size (148mm x 210mm) Portrait, Monospaced Font */
.receipt-content {
    font-family: 'Courier New', Courier, monospace;
    width: 148mm;
    min-height: 210mm;
    padding: 8mm;
    margin: 0 auto;
    background: white;
    font-size: 10pt;
    line-height: 1.3;
    color: #000;
}

@media print {
    @page {
        size: A5 portrait;
        margin: 0;
    }
    body * {
        visibility: hidden;
    }
    .receipt-content, .receipt-content * {
        visibility: visible;
    }
    .receipt-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 148mm;
        min-height: 210mm;
        padding: 8mm;
        margin: 0;
        font-family: 'Courier New', Courier, monospace;
        font-size: 10pt;
        page-break-after: always;
    }
    .receipt-content table {
        width: 100%;
        margin-bottom: 8mm;
        font-size: 9pt;
        border-collapse: collapse;
        font-family: 'Courier New', Courier, monospace;
    }
    .receipt-content table th,
    .receipt-content table td {
        padding: 2mm 1mm;
        font-size: 9pt;
        border-bottom: 1px solid #000;
        font-family: 'Courier New', Courier, monospace;
    }
    .receipt-content table th {
        font-weight: bold;
        text-align: left;
    }
    .receipt-content h3 {
        font-size: 14pt;
        margin-bottom: 4mm;
        margin-top: 0;
        font-family: 'Courier New', Courier, monospace;
        font-weight: bold;
    }
    .receipt-content hr {
        margin: 4mm 0;
        border: none;
        border-top: 1px solid #000;
    }
}
.receipt-content {
    font-size: 14px;
    max-width: 300px;
    margin: 0 auto;
    padding: 20px;
}
</style>
@endsection

@section('scripts')
<!-- Html5-QRCode Library for Barcode Scanning -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
// Number formatting function with commas and decimal points (for prices/currency)
function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Number formatting function for integers (quantities) - with commas but no decimals
function formatQuantity(num) {
    return parseInt(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Function to remove commas from a string (for parsing input)
function removeCommas(str) {
    return str.toString().replace(/,/g, '');
}

// Function to format quantity input on blur
function formatQuantityInput(input) {
    const value = removeCommas(input.value);
    // Handle empty string - set to 0
    if (value === '' || value === null || value === undefined) {
        input.value = '0';
        return;
    }
    // Parse the value
    const numValue = parseInt(value);
    // If valid number (including 0), format it
    if (!isNaN(numValue)) {
        // For 0, just set to '0', otherwise format with commas
        input.value = numValue === 0 ? '0' : formatQuantity(numValue);
    } else {
        // Invalid input, set to 0
        input.value = '0';
    }
}

// Function to handle quantity input change (remove commas for calculation)
function handleQuantityInputChange(index, input) {
    const numericValue = removeCommas(input.value);
    updateCartQuantity(index, numericValue);
}

// Custom Alert Function
function showCustomAlert(title, message, type = 'warning') {
    const modal = document.getElementById('customAlertModal');
    const icon = document.getElementById('alertIcon');
    const titleEl = document.getElementById('alertTitle');
    const messageEl = document.getElementById('alertMessage');
    
    // Set icon based on type
    if (type === 'error' || type === 'danger') {
        icon.className = 'fas fa-exclamation-circle';
        icon.style.color = '#DC586D';
    } else if (type === 'success') {
        icon.className = 'fas fa-check-circle';
        icon.style.color = '#A33757';
    } else {
        icon.className = 'fas fa-exclamation-triangle';
        icon.style.color = '#FFBB94';
    }
    
    titleEl.textContent = title;
    messageEl.innerHTML = message;
    modal.classList.add('show');
}

function closeCustomAlert() {
    const modal = document.getElementById('customAlertModal');
    modal.classList.remove('show');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('customAlertModal');
    if (event.target === modal) {
        closeCustomAlert();
    }
});

let cart = [];
const products = @json($products);
let html5QrcodeScanner = null;
let isScanning = false;

// Barcode Scanner Functionality
$('#scan-barcode-btn').on('click', function() {
    $('#barcode-scanner-modal').modal('show');
});

// Automatically start scanner when modal is shown
$('#barcode-scanner-modal').on('shown.bs.modal', function() {
    startBarcodeScanner();
});

$('#close-scanner-btn, #barcode-scanner-modal').on('hidden.bs.modal', function() {
    stopBarcodeScanner();
});

function startBarcodeScanner() {
    if (isScanning) return;
    
    const containerId = 'barcode-scanner-container';
    
    html5QrcodeScanner = new Html5Qrcode(containerId);
    
    html5QrcodeScanner.start(
        { facingMode: "environment" }, // Use back camera
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
        (decodedText, decodedResult) => {
            // Successfully scanned
            stopBarcodeScanner();
            $('#barcode-scanner-modal').modal('hide');
            searchProductByBarcode(decodedText);
        },
        (errorMessage) => {
            // Ignore scanning errors (they're frequent during scanning)
        }
    ).catch((err) => {
        console.error('Error starting scanner:', err);
        showCustomAlert('Scanner Error', 'Failed to start camera. Please check permissions and try again.', 'error');
    });
    
    isScanning = true;
}

function stopBarcodeScanner() {
    if (html5QrcodeScanner && isScanning) {
        html5QrcodeScanner.stop().then(() => {
            html5QrcodeScanner.clear();
            html5QrcodeScanner = null;
            isScanning = false;
        }).catch((err) => {
            console.error('Error stopping scanner:', err);
            html5QrcodeScanner = null;
            isScanning = false;
        });
    }
}

function searchProductByBarcode(barcode) {
    if (!barcode || barcode.trim().length === 0) {
        return;
    }
    
    $.ajax({
        url: '{{ route("pos.getProductByBarcode") }}',
        method: 'GET',
        data: { barcode: barcode },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success && response.product) {
                const product = response.product;
                
                // Check if product is in stock
                if (product.quantity <= 0) {
                    showCustomAlert('Out of Stock', `Product <strong>${product.name}</strong> (SKU: ${product.sku}) is currently out of stock.`, 'error');
                    return;
                }
                
                // Find product in products array and update it with latest data
                const productInList = products.find(p => p.id == product.id);
                if (!productInList) {
                    // Product not in list, add it
                    products.push(product);
                } else {
                    // Update existing product with latest data from server
                    Object.assign(productInList, product);
                }
                
                if (product.quantity <= 0) {
                    showCustomAlert('Out of Stock', `Product <strong>${product.name}</strong> (SKU: ${product.sku}) is currently out of stock.`, 'error');
                    return;
                }
                
                // Add product to cart
                const productUnit = (product.unit || 'pcs').toLowerCase();
                const existingItem = cart.find(item => item.product_id == product.id);
                
                if (existingItem) {
                    // Product already in cart - do not auto-increment on scan
                    showCustomAlert(
                        'Already in Cart',
                        `Product <strong>${product.name}</strong> (SKU: ${product.sku}) is already in the cart.<br><br>Please adjust the quantity in the cart row instead.`,
                        'warning'
                    );
                } else {
                    // Add new item to cart with quantity 0 (stock not taken until quantity is set > 0)
                    cart.push({
                        product_id: product.id,
                        product_name: product.name,
                        product_sku: product.sku,
                        product_unit: productUnit,
                        unit_price: parseFloat(product.price),
                        quantity: 0,
                        max_stock: product.quantity
                    });
                    // No stock update needed when quantity is 0
                }
                
                updateCart();
                
                // Visual feedback - highlight the product card briefly
                const productCard = $(`.product-card[data-product-id="${product.id}"]`);
                if (productCard.length) {
                    productCard.css('background-color', '#d4edda');
                    setTimeout(() => {
                        productCard.css('background-color', '');
                    }, 1000);
                }
            } else {
                showCustomAlert('Product Not Found', `No product found with barcode: <strong>${barcode}</strong>`, 'warning');
            }
        },
        error: function(xhr) {
            const errorMessage = xhr.responseJSON?.message || 'Error searching for product';
            showCustomAlert('Error', errorMessage, 'error');
        }
    });
}

// Product search functionality
$('#product-search').on('input', function() {
    filterProducts();
});

// Category filter functionality
$('#category-filter').on('change', function() {
    filterProducts();
});

function filterProducts() {
    const search = $('#product-search').val().toLowerCase();
    const categoryId = $('#category-filter').val();
    
    $('.product-card').each(function() {
        const name = $(this).data('product-name').toLowerCase();
        const sku = $(this).data('product-sku').toLowerCase();
        const barcode = $(this).data('product-barcode') ? $(this).data('product-barcode').toLowerCase() : '';
        const productCategoryId = $(this).data('category-id') || '';
        
        let matchesSearch = true;
        let matchesCategory = true;
        
        // Check search filter (search by name, SKU, or barcode)
        if (search.length > 0) {
            matchesSearch = name.includes(search) || sku.includes(search) || (barcode && barcode.includes(search));
        }
        
        // Check category filter
        if (categoryId && categoryId !== '') {
            matchesCategory = productCategoryId == categoryId;
        }
        
        // Show/hide product card
        if (matchesSearch && matchesCategory) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Available unit options
const unitOptions = ['pcs', 'box', 'pack', 'set', 'bag', 'kg', 'g', 'lb', 'liter', 'ml', 'meter', 'cm'];

// Function to update product stock display
function updateProductStockDisplay(productId, quantityChange) {
    const productCard = $(`.product-card[data-product-id="${productId}"]`);
    if (productCard.length === 0) return;
    
    // Find product in products array
    const product = products.find(p => p.id == productId);
    if (!product) return;
    
    // Update the product quantity in the array
    product.quantity = Math.max(0, product.quantity - quantityChange);
    
    // Update the data attribute
    productCard.data('product-stock', product.quantity);
    
    // Update the stock display text
    const stockText = productCard.find('.product-stock');
    const unit = product.unit || 'pcs';
    stockText.text(`Stock: ${formatQuantity(product.quantity)} ${ucfirst(unit)}`);
    
    // Update out-of-stock class
    if (product.quantity <= 0) {
        productCard.addClass('out-of-stock');
    } else {
        productCard.removeClass('out-of-stock');
    }
}

// Helper function to capitalize first letter
function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Add product to cart on product card click
$('.product-card').on('click', function() {
    if ($(this).hasClass('out-of-stock')) return;
    
    const productId = $(this).data('product-id');
    const product = products.find(p => p.id == productId);
    
    if (!product || product.quantity <= 0) {
        showCustomAlert('Out of Stock', `Product <strong>${product.name}</strong> (SKU: ${product.sku}) is currently out of stock.`, 'error');
        return;
    }
    
    // Get unit from product data or fallback to default
    const productUnit = (product.unit || $(this).data('product-unit') || 'pcs').toLowerCase();
    
    // Check if product already exists in cart
    const existingItem = cart.find(item => item.product_id == productId);
    if (existingItem) {
        // Product already in cart - do not auto-increment on click
        showCustomAlert(
            'Already in Cart',
            `Product <strong>${product.name}</strong> (SKU: ${product.sku}) is already in the cart.<br><br>Please adjust the quantity in the cart row instead.`,
            'warning'
        );
    } else {
        // Add new item to cart with quantity 0 (stock not taken until quantity is set > 0)
        cart.push({
            product_id: productId,
            product_name: product.name,
            product_sku: product.sku,
            product_unit: productUnit,
            unit_price: parseFloat(product.price),
            quantity: 0,
            max_stock: product.quantity
        });
        // No stock update needed when quantity is 0
    }
    
    updateCart();
});

function updateCart() {
    const cartHtml = cart.length === 0 
        ? '<p class="text-muted text-center">No items in cart</p>'
        : cart.map((item, index) => {
            const product = products.find(p => p.id == item.product_id);
            // Use current stock from products array, fallback to stored max_stock
            const maxStock = product ? product.quantity : (item.max_stock || 999);
            // Update max_stock in cart item to keep it in sync
            if (product) {
                cart[index].max_stock = product.quantity;
            }
            const unitOptionsHtml = unitOptions.map(unit => 
                `<option value="${unit}" ${item.product_unit === unit ? 'selected' : ''}>${unit.charAt(0).toUpperCase() + unit.slice(1)}</option>`
            ).join('');
            
            return `
            <div class="cart-item">
                <div class="mb-2">
                    <strong style="font-size: 16px;">${item.product_name}</strong>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <label class="mb-0" style="font-size: 14px; font-weight: 500;">Qty:</label>
                    <input type="text" 
                           class="quantity-input" 
                           value="${formatQuantity(item.quantity)}" 
                           data-cart-index="${index}"
                           onblur="formatQuantityInput(this); handleQuantityInputChange(${index}, this)"
                           onkeypress="return (event.charCode >= 48 && event.charCode <= 57)"
                           style="font-size: 14px; text-align: right;">
                    <label class="mb-0" style="font-size: 14px; font-weight: 500;">Unit:</label>
                    <select class="unit-select" 
                            data-cart-index="${index}"
                            onchange="updateCartUnit(${index}, this.value)"
                            style="font-size: 14px;">
                        ${unitOptionsHtml}
                    </select>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size: 15px; font-weight: 600;">₱${formatNumber(item.unit_price)} × ${formatQuantity(item.quantity)} = ₱${formatNumber(item.unit_price * item.quantity)}</span>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
        `;
        }).join('');
    
    $('#cart-items').html(cartHtml);
    calculateTotal();
}

function updateCartQuantity(index, newQuantity) {
    // Remove commas if present and parse as integer
    // Allow 0 quantity - handle empty string and 0 explicitly
    const cleanedValue = removeCommas(newQuantity);
    let quantity;
    
    // Handle empty string or whitespace - set to 0
    if (!cleanedValue || cleanedValue.trim() === '') {
        quantity = 0;
    } else {
        const parsedValue = parseInt(cleanedValue);
        // Allow 0 - only use default if truly NaN
        quantity = isNaN(parsedValue) ? 0 : parsedValue;
    }
    const item = cart[index];
    
    if (!item) return;
    
    const product = products.find(p => p.id == item.product_id);
    const maxStock = product ? product.quantity : (item.max_stock || 999);
    const MIN_REMAINING_STOCK = 20;
    
    // Allow 0 quantity, but show warning
    if (quantity < 0) {
        showCustomAlert('Invalid Quantity', 'Quantity cannot be negative.', 'warning');
        cart[index].quantity = 0;
        updateCart();
        return;
    }
    
    // If quantity is 0, allow it but warn user
    if (quantity === 0) {
        // Still update the cart and stock, but allow 0
        const oldQuantity = item.quantity;
        const quantityDiff = quantity - oldQuantity;
        cart[index].quantity = quantity;
        if (quantityDiff !== 0) {
            updateProductStockDisplay(item.product_id, quantityDiff);
        }
        updateCart();
        return;
    }
    
    // Show stock warning when remaining stock after this quantity goes below the buffer
    const remainingAfter = maxStock - quantity;
    if (quantity > 0 && remainingAfter >= 0 && remainingAfter < MIN_REMAINING_STOCK) {
        const productName = product ? product.name : item.product_name;
        const productSku = product ? product.sku : 'N/A';
        const warningMessage = `
            <strong>${productName}</strong> (${productSku}) will be out of stock.
            <br><br>
            Reorder level is <strong>${MIN_REMAINING_STOCK}</strong> stock.`;
        showCustomAlert('Stock warning', warningMessage, 'warning');
        
        // Reset quantity and keep stock at its current value after user acknowledges the warning
        cart[index].quantity = 0;
        updateCart();
        return;
    }
    
    if (quantity > maxStock) {
        const productName = product ? product.name : item.product_name;
        const productSku = product ? product.sku : 'N/A';
        const productUnit = product ? product.unit : 'pcs';
        showCustomAlert('Insufficient Stock', `Insufficient stock for <strong>${productName}</strong> (SKU: ${productSku}).<br>Available: <strong>${formatQuantity(maxStock)}</strong> ${productUnit}`, 'warning');
        cart[index].quantity = maxStock;
        updateCart();
        return;
    }
    
    // Calculate the difference in quantity
    const oldQuantity = item.quantity;
    const quantityDiff = quantity - oldQuantity;
    
    // Update cart quantity
    cart[index].quantity = quantity;
    
    // Update product stock display
    // If quantity increased (positive diff), stock decreases (pass positive)
    // If quantity decreased (negative diff), stock increases (pass negative, which becomes positive in function)
    if (quantityDiff !== 0) {
        updateProductStockDisplay(item.product_id, quantityDiff);
    }
    
    updateCart();
}

function updateCartUnit(index, newUnit) {
    if (!cart[index]) return;
    cart[index].product_unit = newUnit.toLowerCase();
    updateCart();
}

function removeFromCart(index) {
    const item = cart[index];
    if (!item) {
        updateCart();
        return;
    }
    
    // Always restore stock when removing from cart
    // The stock was reduced when the item was added/changed, so we need to restore it
    // Restore stock based on current quantity in cart (which represents what's currently reserved)
    // Pass negative value to increase stock (since updateProductStockDisplay subtracts the quantityChange)
    const quantityToRestore = item.quantity || 0;
    if (quantityToRestore > 0) {
        updateProductStockDisplay(item.product_id, -quantityToRestore);
    }
    
    // Remove item from cart after restoring stock
    cart.splice(index, 1);
    updateCart();
}

function calculateTotal() {
    let subtotal = cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
    const taxRate = parseFloat($('#tax-rate').val()) || 0;
    const discount = parseFloat($('#discount').val()) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax - discount;
    
    $('#subtotal').text('₱' + formatNumber(subtotal));
    $('#total').text('₱' + formatNumber(total));
}

$('#tax-rate, #discount').on('input', calculateTotal);

// Offline Storage using IndexedDB
const DB_NAME = 'merchantrack_offline';
const DB_VERSION = 1;
const STORE_NAME = 'pending_sales';

let db = null;

// Initialize IndexedDB
function initDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            db = request.result;
            resolve(db);
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                const store = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };
    });
}

// Save sale to IndexedDB (offline storage)
async function saveSaleOffline(saleData) {
    if (!db) await initDB();
    
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        
        const saleRecord = {
            ...saleData,
            timestamp: new Date().toISOString(),
            synced: false
        };
        
        const request = store.add(saleRecord);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

// Get pending sales from IndexedDB
async function getPendingSales() {
    if (!db) await initDB();
    
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([STORE_NAME], 'readonly');
        const store = transaction.objectStore(STORE_NAME);
        const index = store.index('timestamp');
        const request = index.getAll();
        
        request.onsuccess = () => resolve(request.result || []);
        request.onerror = () => reject(request.error);
    });
}

// Remove synced sale from IndexedDB
async function removePendingSale(id) {
    if (!db) await initDB();
    
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        const request = store.delete(id);
        
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

// Sync pending sales when online
async function syncPendingSales() {
    if (!navigator.onLine) return;
    
    try {
        const pendingSales = await getPendingSales();
        if (pendingSales.length === 0) return;
        
        console.log(`Syncing ${pendingSales.length} pending sales...`);
        
        for (const sale of pendingSales) {
            try {
                // Remove product_name before syncing (backend expects only product_id)
                const syncData = {
                    items: sale.items.map(item => ({
                        product_id: item.product_id,
                        quantity: item.quantity,
                        unit: item.unit,
                        unit_price: item.unit_price,
                        subtotal: item.subtotal
                    })),
                    subtotal: sale.subtotal,
                    tax: sale.tax,
                    discount: sale.discount,
                    total: sale.total,
                    payment_method: sale.payment_method
                };
                
                const response = await fetch('{{ route("pos.processSale") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    body: JSON.stringify(syncData)
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        await removePendingSale(sale.id);
                        console.log('Synced sale:', sale.id);
                    }
                }
            } catch (error) {
                console.error('Error syncing sale:', error);
            }
        }
        
        // Show notification if sales were synced
        if (pendingSales.length > 0) {
            showSyncNotification(pendingSales.length);
        }
    } catch (error) {
        console.error('Error in syncPendingSales:', error);
    }
}

// Show sync notification
function showSyncNotification(count) {
    const notification = $('<div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 80px; right: 20px; z-index: 10000; min-width: 300px;">')
        .html(`<i class="fas fa-sync-alt me-2"></i>Synced ${count} pending sale(s) successfully!`)
        .append('<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.alert('close');
    }, 5000);
}

// Check online status and show warning
function checkOnlineStatus() {
    if (!navigator.onLine) {
        const warning = $('<div class="alert alert-warning mb-3" role="alert">')
            .html('<i class="fas fa-exclamation-triangle me-2"></i><strong>Offline Mode:</strong> Sales will be saved locally and synced when connection is restored.');
        $('#process-sale').before(warning);
        $('#process-sale').html('<i class="fas fa-save me-2"></i>Save Sale (Offline)');
    } else {
        $('#process-sale').html('<i class="fas fa-check me-2"></i>Process Sale');
        $('.alert-warning').remove();
    }
}

// Initialize DB and check status on page load
initDB().then(() => {
    checkOnlineStatus();
    syncPendingSales(); // Try to sync any pending sales
    
    // Listen for online/offline events
    window.addEventListener('online', () => {
        checkOnlineStatus();
        syncPendingSales();
    });
    
    window.addEventListener('offline', () => {
        checkOnlineStatus();
    });
});

$('#process-sale').on('click', async function() {
    if (cart.length === 0) {
        showCustomAlert('Empty Cart', 'Your cart is empty. Please add items before processing the sale.', 'warning');
        return;
    }
    
    // Check for items with quantity 0 - prevent sale if any item has 0 quantity
    const itemsWithZeroQuantity = cart.filter(item => item.quantity <= 0);
    if (itemsWithZeroQuantity.length > 0) {
        const productNames = itemsWithZeroQuantity.map(item => item.product_name).join(', ');
        showCustomAlert('Invalid Quantity', `Cannot process sale. The following items have quantity 0:<br><strong>${productNames}</strong><br><br>Please set a quantity greater than 0 or remove these items from the cart.`, 'error');
        return;
    }
    
    // Filter out any items with 0 quantity (shouldn't happen after above check, but just in case)
    const validCartItems = cart.filter(item => item.quantity > 0);
    if (validCartItems.length === 0) {
        showCustomAlert('Empty Cart', 'Your cart has no items with valid quantities. Please add items before processing the sale.', 'warning');
        return;
    }
    
    const subtotal = validCartItems.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
    const taxRate = parseFloat($('#tax-rate').val()) || 0;
    const discount = parseFloat($('#discount').val()) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax - discount;
    
    const saleData = {
        items: validCartItems.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity,
            unit: item.product_unit || 'pcs',
            unit_price: item.unit_price,
            subtotal: item.unit_price * item.quantity
        })),
        subtotal: subtotal,
        tax: tax,
        discount: discount,
        total: total,
        payment_method: 'cash'
    };
    
    // Check if online
    if (navigator.onLine) {
        // Try to process sale online
        $.ajax({
            url: '{{ route("pos.processSale") }}',
            method: 'POST',
            data: saleData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Stock is already updated from cart operations, but ensure it's correct
                    // The stock display should already reflect the sold quantities
                    // Show success modal with receipt
                    showSuccessModal(response.sale, response.business);
                    cart = [];
                    updateCart();
                }
            },
            error: async function(xhr) {
                // If error, try to save offline
                if (xhr.status === 0 || !navigator.onLine) {
                    await handleOfflineSale(saleData);
                } else {
                    const error = xhr.responseJSON?.message || 'Error processing sale';
                    showCustomAlert(
                        'Stock warning',
                        `An error occurred: ${error}`.replace(/\n/g, '<br>'),
                        'warning'
                    );
                }
            }
        });
    } else {
        // Save offline
        await handleOfflineSale(saleData);
    }
});

// Handle offline sale
async function handleOfflineSale(saleData) {
    try {
        // Add product names to items for receipt display
        const saleDataWithNames = {
            ...saleData,
            items: saleData.items.map(item => ({
                ...item,
                product_name: products.find(p => p.id == item.product_id)?.name || 'Unknown Product'
            }))
        };
        
        const saleId = await saveSaleOffline(saleDataWithNames);
        
        // Generate a temporary receipt for offline sale
        const tempSale = {
            sale_number: 'OFFLINE-' + Date.now(),
            date: new Date().toLocaleString(),
            cashier: '{{ auth()->user()->name ?? "User" }}',
            payment_method: saleData.payment_method,
            items: saleDataWithNames.items,
            subtotal: saleData.subtotal,
            tax: saleData.tax,
            discount: saleData.discount,
            total: saleData.total
        };
        
        // Update product stocks for offline sale
        saleData.items.forEach(function(item) {
            updateProductStockDisplay(item.product_id, item.quantity);
        });
        
        showSuccessModal(tempSale);
        
        // Show offline notice
        const offlineNotice = $('<div class="alert alert-info mt-2">')
            .html('<i class="fas fa-info-circle me-2"></i>Sale saved offline. It will be synced automatically when connection is restored.');
        $('#successModal .modal-body').append(offlineNotice);
        
        cart = [];
        updateCart();
    } catch (error) {
        console.error('Error saving offline sale:', error);
        showCustomAlert('Offline Error', 'Error saving sale offline. Please check your connection and try again.', 'error');
    }
}

function showSuccessModal(sale, business) {
    // Get business settings from parameter or use defaults - EXACTLY as shown on receipt
    // Priority: business parameter from API > Blade template variables
    const businessInfo = business || {
        business_name: '{{ $businessSettings->business_name ?? "" }}',
        receipt_type: '{{ $businessSettings->receipt_type ?? "SALES INVOICE" }}',
        business_type: '{{ $businessSettings->business_type ?? "" }}',
        address: '{{ $businessSettings->address ?? "" }}',
        proprietor: '{{ $businessSettings->proprietor ?? "" }}',
        vat_reg_tin: '{{ $businessSettings->vat_reg_tin ?? "" }}',
        phone: '{{ $businessSettings->phone ?? "" }}',
        receipt_footer_note: '{{ $businessSettings->receipt_footer_note ?? "" }}'
    };
    
    // Debug: Check if business info is available
    console.log('Business Info Received:', businessInfo);
    console.log('Business Name:', businessInfo.business_name);
    
    // Extract receipt number (remove DR- prefix if present for display)
    const receiptNo = sale.sale_number.replace('DR-', '').replace('SALE-', '');
    const dateDisplay = sale.date_short || sale.date.split(' ').slice(0, 3).join(' ');
    
    // Build AL-NES style sales invoice HTML - A5 size, monospaced font, exact header format
    // Always show header section even if some fields are empty
    let receiptHtml = `
        <div class="receipt-content" id="receipt-content">
            <!-- Header - EXACTLY as shown on receipt -->
            <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 4mm; margin-bottom: 4mm;">
                <h3 style="margin: 0 0 2mm 0; font-size: 14pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5pt;">${(businessInfo.business_name && businessInfo.business_name.trim()) || 'MERCHANTRACK'}</h3>
                ${(businessInfo.business_type && businessInfo.business_type.trim()) ? `<p style="margin: 1mm 0; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.3pt;">${businessInfo.business_type}</p>` : ''}
                ${(businessInfo.address && businessInfo.address.trim()) ? `<p style="margin: 1mm 0; font-size: 9pt;">${businessInfo.address}</p>` : ''}
                ${(businessInfo.proprietor && businessInfo.proprietor.trim()) ? `<p style="margin: 1mm 0; font-size: 9pt;"><strong>Proprietor:</strong> ${businessInfo.proprietor}</p>` : ''}
                ${(businessInfo.vat_reg_tin && businessInfo.vat_reg_tin.trim()) ? `<p style="margin: 1mm 0; font-size: 9pt;">VAT Reg. TIN: ${businessInfo.vat_reg_tin}</p>` : ''}
                ${(businessInfo.phone && businessInfo.phone.trim()) ? `<p style="margin: 1mm 0; font-size: 9pt;">${businessInfo.phone}</p>` : ''}
            </div>
            
            <!-- Sales Invoice Box - EXACTLY as shown -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 4mm 0; padding: 3mm; border: 1px solid #000; background: #f9f9f9;">
                <div style="font-weight: bold; font-size: 10pt; letter-spacing: 0.5pt;">${(businessInfo.receipt_type && businessInfo.receipt_type.trim()) || 'SALES INVOICE'}</div>
                <div style="font-weight: bold; font-size: 12pt; color: #d32f2f;">No.${receiptNo}</div>
            </div>
            
            <!-- Receipt Details Section -->
            <div style="margin: 4mm 0; font-size: 9pt; line-height: 1.6;">
                <div><strong>Date:</strong> ${dateDisplay}</div>
                ${sale.cashier ? `<div style="margin-top: 2mm;"><strong>Cashier:</strong> ${sale.cashier}</div>` : ''}
                <div style="margin-top: 2mm;"><strong>Payment:</strong> ${sale.payment_method ? sale.payment_method.charAt(0).toUpperCase() + sale.payment_method.slice(1) : 'Cash'}</div>
            </div>
            
            <hr style="margin: 4mm 0; border: none; border-top: 1px solid #000;">
            
            <!-- Items Table - QTY | UNIT | ARTICLES | AMOUNT -->
            <table style="width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 4mm;">
                <thead>
                    <tr style="border-bottom: 1px solid #000;">
                        <th style="text-align: left; padding: 2mm 1mm; font-weight: bold; width: 12%;">QTY.</th>
                        <th style="text-align: left; padding: 2mm 1mm; font-weight: bold; width: 15%;">UNIT</th>
                        <th style="text-align: left; padding: 2mm 1mm; font-weight: bold; width: 48%;">ARTICLES</th>
                        <th style="text-align: right; padding: 2mm 1mm; font-weight: bold; width: 25%;">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    // Only include items that exist - no guessing
    if (sale.items && sale.items.length > 0) {
        sale.items.forEach(item => {
            // Standardize unit (PC, PCS, etc.)
            const unit = item.unit ? item.unit.toUpperCase().replace(/S$/, '') : 'PC';
            // Standardize product name - uppercase, no guessing
            const productName = item.product_name ? item.product_name.toUpperCase().trim() : '';
            // Ensure quantity is numeric
            const quantity = parseInt(item.quantity) || 0;
            // Ensure amount is numeric
            const amount = parseFloat(item.subtotal) || 0;
            
            if (productName && quantity > 0) {
                receiptHtml += `
                    <tr style="border-bottom: 1px dotted #666;">
                        <td style="padding: 2mm 1mm; text-align: left;">${formatQuantity(quantity)}</td>
                        <td style="padding: 2mm 1mm; text-align: left;">${unit}</td>
                        <td style="padding: 2mm 1mm; text-align: left;">${productName}</td>
                        <td style="padding: 2mm 1mm; text-align: right;">₱${formatNumber(amount)}</td>
                    </tr>
                `;
            }
        });
    }
    
    receiptHtml += `
                </tbody>
                <tfoot>
                    <tr style="border-top: 2px solid #000;">
                        <td colspan="3" style="padding: 3mm 1mm; text-align: right; font-weight: bold; font-size: 10pt;">TOTAL:</td>
                        <td style="padding: 3mm 1mm; text-align: right; font-weight: bold; font-size: 11pt;">₱${formatNumber(parseFloat(sale.total || 0))}</td>
                    </tr>
                </tfoot>
            </table>
            
            <hr style="margin: 4mm 0; border: none; border-top: 1px solid #000;">
            
            <!-- Footer - Only include if data exists -->
            <div style="text-align: center; font-size: 8pt; margin-top: 6mm; padding-top: 4mm; border-top: 1px solid #ccc; line-height: 1.5;">
                ${businessInfo.receipt_footer_note ? `<p style="margin: 2mm 0; font-style: italic;">${businessInfo.receipt_footer_note}</p>` : ''}
                <p style="margin: 4mm 0 2mm 0;">Thank you for your purchase!</p>
            </div>
        </div>
    `;
    
    // Set modal body content
    $('#receipt-modal-body').html(receiptHtml);
    
    // Show modal
    $('#successModal').modal('show');
}

function printReceipt() {
    // Get receipt content
    const receiptContent = document.getElementById('receipt-content').outerHTML;
    const originalContent = document.body.innerHTML;
    const originalTitle = document.title;
    
    // Create a clean print document with A5 size
    const printWindow = window.open('', '_blank', 'width=148mm,height=210mm');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Sales Invoice</title>
            <style>
                @page {
                    size: A5 portrait;
                    margin: 0;
                }
                body {
                    margin: 0;
                    padding: 0;
                    font-family: 'Courier New', Courier, monospace;
                }
                .receipt-content {
                    width: 148mm;
                    min-height: 210mm;
                    padding: 8mm;
                    margin: 0;
                    font-family: 'Courier New', Courier, monospace;
                    font-size: 10pt;
                    line-height: 1.3;
                    color: #000;
                }
            </style>
        </head>
        <body>
            ${receiptContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    
    // Wait for content to load, then print
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}
</script>
@endsection

