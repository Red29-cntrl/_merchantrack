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
    .cart-item {
        border-bottom: 1px solid #eee;
        padding: 10px 0;
    }
    .cart-summary {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <h2 class="mb-4"><i class="fas fa-cash-register me-2"></i>Point of Sale</h2>

    <div class="pos-container">
        <div>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="barcode-scanner" class="form-label">
                            <i class="fas fa-barcode me-2"></i>Scan Barcode or Search
                        </label>
                        <input type="text" id="barcode-scanner" class="form-control" 
                               placeholder="Scan barcode or search by name/SKU..." autofocus>
                        <small class="text-muted">Use camera to scan barcode or type to search</small>
                    </div>
                    <div id="scanner-container" class="mb-3" style="display: none;">
                        <video id="video" width="100%" height="200" style="border: 1px solid #ddd; border-radius: 4px;"></video>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" id="stop-scanner">Stop Camera</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" id="start-scanner">
                        <i class="fas fa-camera me-2"></i>Start Camera Scanner
                    </button>
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
                             data-product-price="{{ $product->price }}"
                             data-product-stock="{{ $product->quantity }}">
                            <strong>{{ $product->name }}</strong><br>
                            <small class="text-muted">{{ $product->sku }}</small><br>
                            <span class="badge bg-primary">₱{{ number_format($product->price, 2) }}</span><br>
                            <small>Stock: {{ $product->quantity }}</small>
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
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" id="payment-method" disabled>
                                <option value="cash" selected>Cash Only</option>
                            </select>
                            <small class="text-muted">Only cash payments are accepted</small>
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
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script>
let cart = [];
const products = @json($products);
let scannerActive = false;
let stream = null;

// Barcode scanner setup
$('#start-scanner').on('click', function() {
    if (scannerActive) return;
    
    $('#scanner-container').show();
    $('#start-scanner').prop('disabled', true);
    
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'environment' // Use back camera on mobile
        } 
    }).then(function(mediaStream) {
        stream = mediaStream;
        const video = document.getElementById('video');
        video.srcObject = stream;
        video.play();
        scannerActive = true;
        
        // Initialize QuaggaJS for barcode scanning
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: video,
                constraints: {
                    width: 640,
                    height: 480,
                    facingMode: "environment"
                }
            },
            decoder: {
                readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader", "code_39_vin_reader", "codabar_reader", "upc_reader", "upc_e_reader", "i2of5_reader"]
            }
        }, function(err) {
            if (err) {
                console.error('QuaggaJS initialization error:', err);
                alert('Camera scanner initialization failed. Please use manual search.');
                stopScanner();
                return;
            }
            Quagga.start();
        });
        
        Quagga.onDetected(function(result) {
            const code = result.codeResult.code;
            $('#barcode-scanner').val(code);
            addProductByBarcode(code);
            // Stop scanner after successful scan
            setTimeout(() => {
                stopScanner();
            }, 1000);
        });
    }).catch(function(err) {
        console.error('Camera access error:', err);
        alert('Camera access denied. Please use manual search.');
        stopScanner();
    });
});

$('#stop-scanner').on('click', function() {
    stopScanner();
});

function stopScanner() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    if (scannerActive) {
        Quagga.stop();
        scannerActive = false;
    }
    $('#scanner-container').hide();
    $('#start-scanner').prop('disabled', false);
}

// Barcode scanner input
$('#barcode-scanner').on('input', function() {
    const search = $(this).val().toLowerCase();
    if (search.length > 0) {
        searchProducts(search);
    } else {
        $('.product-card').show();
    }
});

function addProductByBarcode(barcode) {
    const product = products.find(p => p.sku.toLowerCase() === barcode.toLowerCase());
    if (product) {
        if (product.quantity <= 0) {
            alert('Product is out of stock');
            return;
        }
        
        const existingItem = cart.find(item => item.product_id == product.id);
        if (existingItem) {
            if (existingItem.quantity < product.quantity) {
                existingItem.quantity++;
            } else {
                alert('Insufficient stock');
                return;
            }
        } else {
            cart.push({
                product_id: product.id,
                product_name: product.name,
                unit_price: parseFloat(product.price),
                quantity: 1
            });
        }
        
        updateCart();
        $('#barcode-scanner').val('');
    } else {
        alert('Product not found with barcode: ' + barcode);
    }
}

function searchProducts(search) {
    $('.product-card').each(function() {
        const name = $(this).data('product-name').toLowerCase();
        const sku = $(this).find('small').text().toLowerCase();
        if (name.includes(search) || sku.includes(search)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Add product to cart
$('.product-card').on('click', function() {
    if ($(this).hasClass('out-of-stock')) return;
    
    const productId = $(this).data('product-id');
    const product = products.find(p => p.id == productId);
    
    if (product.quantity <= 0) return;
    
    const existingItem = cart.find(item => item.product_id == productId);
    if (existingItem) {
        if (existingItem.quantity < product.quantity) {
            existingItem.quantity++;
        } else {
            alert('Insufficient stock');
            return;
        }
    } else {
        cart.push({
            product_id: productId,
            product_name: product.name,
            unit_price: parseFloat(product.price),
            quantity: 1
        });
    }
    
    updateCart();
});

function updateCart() {
    const cartHtml = cart.length === 0 
        ? '<p class="text-muted text-center">No items in cart</p>'
        : cart.map((item, index) => `
            <div class="cart-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${item.product_name}</strong><br>
                        <small>₱${item.unit_price.toFixed(2)} x ${item.quantity}</small>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    
    $('#cart-items').html(cartHtml);
    calculateTotal();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCart();
}

function calculateTotal() {
    let subtotal = cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
    const taxRate = parseFloat($('#tax-rate').val()) || 0;
    const discount = parseFloat($('#discount').val()) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax - discount;
    
    $('#subtotal').text('₱' + subtotal.toFixed(2));
    $('#total').text('₱' + total.toFixed(2));
}

$('#tax-rate, #discount').on('input', calculateTotal);

$('#process-sale').on('click', function() {
    if (cart.length === 0) {
        alert('Cart is empty');
        return;
    }
    
    const subtotal = cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
    const taxRate = parseFloat($('#tax-rate').val()) || 0;
    const discount = parseFloat($('#discount').val()) || 0;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax - discount;
    
    const saleData = {
        items: cart.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            subtotal: item.unit_price * item.quantity
        })),
        subtotal: subtotal,
        tax: tax,
        discount: discount,
        total: total,
        payment_method: $('#payment-method').val()
    };
    
    $.ajax({
        url: '{{ route("pos.processSale") }}',
        method: 'POST',
        data: saleData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('Sale processed successfully! Sale #: ' + response.sale_number);
                cart = [];
                updateCart();
                window.location.href = '/sales/' + response.sale_id;
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Error processing sale';
            alert(error);
        }
    });
});
</script>
@endsection

