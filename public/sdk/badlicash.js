/** BadliCash Payment Gateway SDK v2.0 **/
(function(window){
  'use strict';
  
  var BadliCash = window.BadliCash || {};
  var API_BASE_URL = window.BADLICASH_API_URL || window.location.origin;

  /**
   * BadliCash Checkout Widget
   * Usage:
   *   var checkout = new BadliCash.Checkout({
   *     key: 'pk_test_...',
   *     amount: 1000,
   *     currency: 'INR',
   *     name: 'Product Name',
   *     description: 'Product Description',
   *     handler: function(response) {
   *       console.log('Payment success:', response);
   *     },
   *     prefill: {
   *       name: 'Customer Name',
   *       email: 'customer@example.com',
   *       phone: '9876543210'
   *     }
   *   });
   *   checkout.open();
   */
  BadliCash.Checkout = function(options) {
    this.options = options || {};
    this.validate();
    this.mode = this.detectMode(this.options.key);
  };

  BadliCash.Checkout.prototype.validate = function() {
    if (!this.options) throw new Error('Options required');
    if (!this.options.key) throw new Error('Publishable key required');
    if (!this.options.amount || this.options.amount <= 0) throw new Error('Amount must be > 0');
  };

  BadliCash.Checkout.prototype.detectMode = function(key) {
    if (!key) return 'test';
    return key.startsWith('pk_live_') ? 'live' : 'test';
  };

  BadliCash.Checkout.prototype.open = function() {
    var self = this;
    var overlay = document.createElement('div');
    overlay.id = 'badlicash-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99999;display:flex;align-items:center;justify-content:center;animation:fadeIn 0.3s;';
    
    var modal = document.createElement('div');
    modal.style.cssText = 'background:#fff;border-radius:16px;width:90%;max-width:520px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);position:relative;animation:slideUp 0.3s;';
    
    var closeBtn = document.createElement('button');
    closeBtn.innerHTML = '×';
    closeBtn.style.cssText = 'position:absolute;top:12px;right:16px;font-size:28px;background:#fff;border:0;border-radius:50%;width:36px;height:36px;cursor:pointer;z-index:100000;color:#666;line-height:1;';
    closeBtn.onclick = function() { self.close(); };
    
    var iframe = document.createElement('iframe');
    iframe.id = 'badlicash-iframe';
    iframe.style.cssText = 'width:100%;height:620px;border:0;display:block;';
    iframe.src = this.buildCheckoutUrl();
    
    modal.appendChild(closeBtn);
    modal.appendChild(iframe);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    // Handle messages from iframe
    var messageHandler = function(e) {
      if (e.origin !== API_BASE_URL.replace(/\/$/, '')) return;
      
      var data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
      
      if (data.type === 'payment_success') {
        if (typeof self.options.handler === 'function') {
          self.options.handler({
            razorpay_payment_id: data.transaction_id,
            razorpay_order_id: data.order_id,
            razorpay_signature: data.signature || '',
            amount: data.amount,
            currency: data.currency
          });
        }
        self.close();
      } else if (data.type === 'payment_failed') {
        if (typeof self.options.onClose === 'function') {
          self.options.onClose();
        }
      } else if (data.type === 'close') {
        self.close();
      }
    };
    
    window.addEventListener('message', messageHandler);
    this.messageHandler = messageHandler;
    
    // Add CSS animations
    if (!document.getElementById('badlicash-styles')) {
      var style = document.createElement('style');
      style.id = 'badlicash-styles';
      style.textContent = '@keyframes fadeIn{from{opacity:0}to{opacity:1}}@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}';
      document.head.appendChild(style);
    }
  };

  BadliCash.Checkout.prototype.close = function() {
    var overlay = document.getElementById('badlicash-overlay');
    if (overlay) {
      overlay.style.animation = 'fadeOut 0.3s';
      setTimeout(function() {
        document.body.removeChild(overlay);
        document.body.style.overflow = '';
      }, 300);
    }
    
    if (this.messageHandler) {
      window.removeEventListener('message', this.messageHandler);
    }
    
    if (typeof this.options.onClose === 'function') {
      this.options.onClose();
    }
  };

  BadliCash.Checkout.prototype.buildCheckoutUrl = function() {
    // First, create payment link via API
    var self = this;
    var params = {
      amount: this.options.amount,
      currency: this.options.currency || 'INR',
      name: this.options.name || '',
      description: this.options.description || '',
      customer_name: (this.options.prefill && this.options.prefill.name) || '',
      customer_email: (this.options.prefill && this.options.prefill.email) || '',
      customer_phone: (this.options.prefill && this.options.prefill.phone) || '',
      key: this.options.key,
      mode: this.mode
    };
    
    // For now, use direct checkout URL if link_token provided, otherwise create via API
    if (this.options.link_token) {
      var queryString = Object.keys(params).filter(function(k) { return params[k]; }).map(function(k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
      }).join('&');
      return API_BASE_URL + '/pay/' + this.options.link_token + (queryString ? '?' + queryString : '');
    }
    
    // Return generic checkout (merchant should create payment link via API first)
    var queryString = Object.keys(params).filter(function(k) { return params[k]; }).map(function(k) {
      return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
    }).join('&');
    
    return API_BASE_URL + '/pay/widget?' + queryString;
  };

  /**
   * Create Payment Link via API
   */
  BadliCash.createPaymentLink = function(options, callback) {
    if (!options || !options.key) {
      callback(new Error('API key required'));
      return;
    }
    
    var mode = options.key.startsWith('pk_live_') ? 'live' : 'test';
    var apiKey = options.key.startsWith('pk_') ? options.key.replace('pk_', 'sk_') : options.key;
    
    fetch(API_BASE_URL + '/api/v1/payment_links', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-Key': apiKey
      },
      body: JSON.stringify({
        amount: options.amount,
        currency: options.currency || 'INR',
        title: options.name || '',
        description: options.description || ''
      })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
      if (data.success && data.data && data.data.link_token) {
        callback(null, data.data);
      } else {
        callback(new Error(data.message || 'Failed to create payment link'));
      }
    })
    .catch(function(error) {
      callback(error);
    });
  };

  window.BadliCash = BadliCash;
})(window);