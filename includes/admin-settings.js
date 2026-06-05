jQuery(document).ready(function($) {
    // === Tab Switching Logic ===
    const tabs = $('.rsldvtost-nav-item');
    const panels = $('.rsldvtost-tab-panel');

    tabs.on('click', function(e) {
        e.preventDefault();
        const targetTab = $(this).data('tab');

        tabs.removeClass('active');
        $(this).addClass('active');

        panels.removeClass('active');
        $(`#panel-${targetTab}`).addClass('active');
        
        // Store current tab in session storage to persist page refresh
        sessionStorage.setItem('rsldvtost_active_tab', targetTab);
    });

    // Restore active tab
    const savedTab = sessionStorage.getItem('rsldvtost_active_tab');
    if (savedTab && $(`#panel-${savedTab}`).length) {
        $(`[data-tab="${savedTab}"]`).trigger('click');
    } else {
        tabs.first().trigger('click');
    }

    // === Click to Copy Code Helpers ===
    $('.rsldvtost-btn-copy-code').on('click', function() {
        const textToCopy = $(this).siblings('.rsldvtost-code-text').text();
        const $btn = $(this);
        const originalText = $btn.text();
        
        navigator.clipboard.writeText(textToCopy).then(function() {
            $btn.text('Copied!');
            setTimeout(function() {
                $btn.text(originalText);
            }, 1500);
        });
    });

    // === Shortcode Injector ===
    const $textarea = $('#rsldvtost_template');
    
    $('.rsldvtost-shortcode-pill').on('click', function() {
        const shortcode = $(this).data('shortcode');
        const textElement = $textarea[0];
        
        if (!textElement) return;

        const startPos = textElement.selectionStart;
        const endPos = textElement.selectionEnd;
        const beforeText = textElement.value.substring(0, startPos);
        const afterText = textElement.value.substring(endPos, textElement.value.length);
        
        textElement.value = beforeText + shortcode + afterText;
        textElement.focus();
        
        // Set cursor position right after the inserted text
        const cursorPosition = startPos + shortcode.length;
        textElement.setSelectionRange(cursorPosition, cursorPosition);
        
        // Trigger input event to update live preview
        $textarea.trigger('input');
    });

    // === Live Preview Parser ===
    const $previewBubble = $('.rsldvtost-telegram-bubble');
    
    function updateLivePreview() {
        if (!$textarea.length || !$previewBubble.length) return;
        
        let template = $textarea.val();
        
        // Default template fallback if empty
        if (!template.trim()) {
            template = "🔔 *Store Order Notification* 🔔\n\n🛒 *Order Number:* #9482\n🛎️ *Order Status:* _Processing_\n🤑 *Total Amount:* 149.99 USD\n🏦 *Payment Method:* Stripe Credit Card\n📦 *Products:*\n- *Premium Leather Wallet* (Qty: 1)\n- *Minimalist Card Holder* (Qty: 2)\n\n{view_order_url}";
        }

        // Replace placeholders with dummy data
        const dummyData = {
            '{site_name}': 'My Awesome Store',
            '{order_number}': '9482',
            '{order_status}': 'Processing',
            '{order_total}': '149.99',
            '{order_currency}': 'USD',
            '{payment_method}': 'Stripe Credit Card',
            '{items}': '- *Premium Leather Wallet* (Qty: 1)\n- *Minimalist Card Holder* (Qty: 2)',
            '{customer_name}': 'John Doe',
            '{customer_email}': 'john.doe@example.com',
            '{customer_phone}': '+1 (555) 234-5678',
            '{billing_address}': '123 Main St\nNew York, NY 10001',
            '{shipping_address}': '123 Main St\nNew York, NY 10001',
            '{view_order_url}': '[View Order in Dashboard](#)'
        };

        for (const [key, value] of Object.entries(dummyData)) {
            template = template.replaceAll(key, value);
        }

        // Simple Markdown parser for Telegram preview bubble
        // 1. Escaped chars: \* -> *
        let parsed = template.replace(/\\([\*\_`\[\]])/g, '$1');
        
        // 2. Bold syntax *text* -> <strong>text</strong>
        parsed = parsed.replace(/\*([^\*]+)\*/g, '<strong>$1</strong>');
        
        // 3. Italic syntax _text_ -> <em>text</em>
        parsed = parsed.replace(/_([^_]+)_/g, '<em>$1</em>');
        
        // 4. Code syntax `text` -> <code>text</code>
        parsed = parsed.replace(/`([^`]+)`/g, '<code style="background:#f1f5f9;padding:2px 4px;border-radius:4px;font-family:monospace;font-size:90%;">$1</code>');
        
        // 5. Link syntax [text](url) -> <a href="url" target="_blank">text</a>
        parsed = parsed.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" style="color:#0088cc;text-decoration:none;font-weight:600;">$1</a>');

        $previewBubble.html(parsed);
    }

    // Bind event listeners for preview
    $textarea.on('input propertychange', updateLivePreview);
    updateLivePreview(); // Run once initially

    // === Notification Toast Helper ===
    function showToast(message, type = 'success') {
        // Remove existing toasts
        $('.rsldvtost-toast').remove();
        
        const toast = $('<div class="rsldvtost-toast rsldvtost-toast-' + type + '">' +
            (type === 'success' ? '✅' : '❌') + ' <span>' + message + '</span></div>');
            
        $('body').append(toast);
        
        // Automatically slide out and remove after 4 seconds
        setTimeout(function() {
            toast.css('animation', 'fadeOut 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 4000);
    }

    // === Test Connection Action ===
    const $testBtn = $('#rsldvtost_btn_test_conn');
    $testBtn.on('click', function(e) {
        e.preventDefault();
        
        const botToken = $('#rsldvtost_bot_token').val().trim();
        const chatId = $('#rsldvtost_chat_id').val().trim();
        
        if (!botToken || !chatId) {
            showToast('Please fill in both Bot Token and Chat ID to run a connection test.', 'error');
            return;
        }

        $testBtn.addClass('loading').prop('disabled', true).text('Testing connection...');
        
        $.post(rsldvtost_params.ajax_url, {
            action: 'rsldvtost_test_connection',
            nonce: rsldvtost_params.nonce,
            bot_token: botToken,
            chat_id: chatId
        }, function(response) {
            $testBtn.removeClass('loading').prop('disabled', false).text('Test Connection');
            if (response.success) {
                showToast(response.data.message, 'success');
            } else {
                showToast(response.data.message || 'Connection test failed.', 'error');
            }
        }).fail(function() {
            $testBtn.removeClass('loading').prop('disabled', false).text('Test Connection');
            showToast('A server error occurred while testing the connection.', 'error');
        });
    });

    // === Fetch Chat ID Action ===
    const $fetchBtn = $('#rsldvtost_btn_fetch_chat');
    $fetchBtn.on('click', function(e) {
        e.preventDefault();
        
        const botToken = $('#rsldvtost_bot_token').val().trim();
        
        if (!botToken) {
            showToast('Please fill in the Bot Token first so we can query updates.', 'error');
            return;
        }

        $fetchBtn.addClass('loading').prop('disabled', true).text('Fetching...');
        
        $.post(rsldvtost_params.ajax_url, {
            action: 'rsldvtost_fetch_chat_id',
            nonce: rsldvtost_params.nonce,
            bot_token: botToken
        }, function(response) {
            $fetchBtn.removeClass('loading').prop('disabled', false).text('Fetch Chat ID');
            if (response.success) {
                $('#rsldvtost_chat_id').val(response.data.chat_id);
                showToast(response.data.message, 'success');
            } else {
                showToast(response.data.message || 'Failed to retrieve Chat ID.', 'error');
            }
        }).fail(function() {
            $fetchBtn.removeClass('loading').prop('disabled', false).text('Fetch Chat ID');
            showToast('A server error occurred while retrieving updates.', 'error');
        });
    });
});
