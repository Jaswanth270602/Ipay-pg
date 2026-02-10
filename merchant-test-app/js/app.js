// Global variables
let currentProduct = null;

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Badlicash Test Store Initialized');
    checkPaymentStatus();
});

// Initiate payment modal
function initiatePayment(productId, productName, amount) {
    currentProduct = {
        id: productId,
        name: productName,
        amount: amount
    };

    // Update modal content
    document.getElementById('modal-product-name').textContent = productName;
    document.getElementById('modal-amount').textContent = `$${amount.toFixed(2)}`;

    // Show modal
    const modal = document.getElementById('paymentModal');
    modal.classList.add('active');
}

// Close payment modal
function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.remove('active');
    currentProduct = null;
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target === modal) {
        closePaymentModal();
    }
}

// Process payment
async function processPayment() {
    if (!currentProduct) {
        alert('No product selected');
        return;
    }

    const customerEmail = document.getElementById('customer-email').value;
    const customerName = document.getElementById('customer-name').value;

    if (!customerEmail || !customerName) {
        alert('Please fill all fields');
        return;
    }

    // Show loader
    const btn = document.querySelector('.btn-confirm-payment');
    btn.disabled = true;
    btn.querySelector('.btn-text').style.display = 'none';
    btn.querySelector('.btn-loader').style.display = 'flex';

    try {
        // Call payment gateway
        const result = await createPayment({
            product_id: currentProduct.id,
            product_name: currentProduct.name,
            amount: currentProduct.amount,
            customer_email: customerEmail,
            customer_name: customerName
        });

        if (result.success && result.payment_url) {
            // Redirect to payment gateway
            window.location.href = result.payment_url;
        } else {
            throw new Error(result.message || 'Payment initialization failed');
        }
    } catch (error) {
        console.error('Payment error:', error);
        alert('Payment failed: ' + error.message);
        
        // Reset button
        btn.disabled = false;
        btn.querySelector('.btn-text').style.display = 'block';
        btn.querySelector('.btn-loader').style.display = 'none';
    }
}

// Check for payment status in URL (after redirect back)
function checkPaymentStatus() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const transactionId = urlParams.get('transaction_id');

    if (status && transactionId) {
        // Redirect to appropriate status page
        switch(status.toLowerCase()) {
            case 'success':
                window.location.href = `success.html?transaction_id=${transactionId}`;
                break;
            case 'failed':
            case 'failure':
                window.location.href = `failure.html?transaction_id=${transactionId}`;
                break;
            case 'pending':
                window.location.href = `pending.html?transaction_id=${transactionId}`;
                break;
            case 'refunded':
                window.location.href = `refund.html?transaction_id=${transactionId}`;
                break;
        }
    }
}

// Utility function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Utility function to format date
function formatDate(dateString) {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    }).format(new Date(dateString));
}

