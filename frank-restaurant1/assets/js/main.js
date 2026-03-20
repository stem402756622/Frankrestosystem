/* Frank Restaurant — Main JS v2.0 */

// ============ POPUP NAVIGATION ============
const PopupNav = {
    init() {
        this.createToggleButton();
        this.createOverlay();
        this.bindEvents();
    },

    createToggleButton() {
        // Check if toggle button already exists
        if (document.querySelector('.nav-toggle')) return;
        
        const toggle = document.createElement('button');
        toggle.className = 'nav-toggle';
        toggle.innerHTML = '☰';
        toggle.setAttribute('aria-label', 'Toggle navigation');
        document.body.appendChild(toggle);
    },

    createOverlay() {
        // Check if overlay already exists
        if (document.querySelector('.nav-overlay')) return;
        
        const overlay = document.createElement('div');
        overlay.className = 'nav-overlay';
        document.body.appendChild(overlay);
    },

    bindEvents() {
        const toggle = document.querySelector('.nav-toggle');
        const overlay = document.querySelector('.nav-overlay');
        const sidebar = document.querySelector('.sidebar');

        if (!toggle || !overlay || !sidebar) return;

        // Toggle button click
        toggle.addEventListener('click', () => this.toggle());

        // Overlay click to close
        overlay.addEventListener('click', () => this.close());

        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                this.close();
            }
        });

        // Close on window resize (desktop)
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
                this.close();
            }
        });
    },

    toggle() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.nav-overlay');
        const toggle = document.querySelector('.nav-toggle');

        if (!sidebar || !overlay || !toggle) return;

        const isOpen = sidebar.classList.contains('open');
        
        if (isOpen) {
            this.close();
        } else {
            this.open();
        }
    },

    open() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.nav-overlay');
        const toggle = document.querySelector('.nav-toggle');

        if (!sidebar || !overlay || !toggle) return;

        sidebar.classList.add('open');
        overlay.classList.add('active');
        toggle.innerHTML = '✕';
        toggle.classList.add('close');
        document.body.style.overflow = 'hidden'; // Prevent body scroll
    },

    close() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.nav-overlay');
        const toggle = document.querySelector('.nav-toggle');

        if (!sidebar || !overlay || !toggle) return;

        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        toggle.innerHTML = '☰';
        toggle.classList.remove('close');
        document.body.style.overflow = ''; // Restore body scroll
    }
};

// ============ THEME SYSTEM ============
const ThemeManager = {
    themes: ['dark', 'light', 'ocean'],

    init() {
        const saved = localStorage.getItem('frank_theme') || 'dark';
        this.apply(saved);
        this.bindButtons();
        this.bindKeyboard();
    },

    apply(theme) {
        document.documentElement.setAttribute('data-theme', theme === 'dark' ? '' : theme);
        if (theme === 'dark') {
            document.documentElement.removeAttribute('data-theme');
        }
        localStorage.setItem('frank_theme', theme);
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.themeTarget === theme);
        });
    },

    bindButtons() {
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', () => this.apply(btn.dataset.themeTarget));
        });
    },

    bindKeyboard() {
        document.addEventListener('keydown', (e) => {
            if (e.altKey) {
                if (e.key === '1') this.apply('dark');
                if (e.key === '2') this.apply('light');
                if (e.key === '3') this.apply('ocean');
            }
        });
    }
};

// ============ ANIMATIONS ============
const AnimationManager = {
    init() {
        this.observeElements();
        this.initStagger();
    },

    observeElements() {
        if (!('IntersectionObserver' in window)) return;
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.observe-animate').forEach(el => observer.observe(el));
    },

    initStagger() {
        document.querySelectorAll('.stagger-container').forEach(container => {
            container.querySelectorAll('.stagger-item').forEach((item, i) => {
                item.style.animationDelay = `${i * 0.08}s`;
                item.style.animationFillMode = 'both';
                item.style.animation = `fadeInUp 0.5s cubic-bezier(0.4,0,0.2,1) ${i * 0.08}s both`;
            });
        });
    }
};

// ============ FLOATING SUCCESS BADGE ============
const Toast = {
    show(title, message, duration = 4000) {
        let badge = document.getElementById('floatingSuccess');
        if (!badge) {
            badge = document.createElement('div');
            badge.id = 'floatingSuccess';
            badge.className = 'floating-success';
            badge.innerHTML = `
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <span style="font-size:1.5rem">✅</span>
                    <div>
                        <div class="floating-success-title" id="fsTitle"></div>
                        <div class="floating-success-msg" id="fsMsg"></div>
                    </div>
                </div>`;
            document.body.appendChild(badge);
        }
        document.getElementById('fsTitle').textContent = title;
        document.getElementById('fsMsg').textContent = message;
        badge.classList.add('show');
        setTimeout(() => badge.classList.remove('show'), duration);
    }
};

// ============ CONFETTI ============
function fireConfetti() {
    const colors = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#06b6d4', '#ec4899'];
    const count = 60;
    for (let i = 0; i < count; i++) {
        const el = document.createElement('div');
        const angle = Math.random() * 360;
        const distance = 80 + Math.random() * 180;
        const tx = Math.cos(angle * Math.PI / 180) * distance;
        const ty = -(Math.random() * 200 + 100);
        el.style.cssText = `
            position:fixed;width:${6 + Math.random()*6}px;height:${6 + Math.random()*6}px;
            background:${colors[Math.floor(Math.random()*colors.length)]};
            top:50%;left:50%;z-index:10000;border-radius:${Math.random()>.5?'50%':'2px'};
            pointer-events:none;--tx:${tx}px;
            animation:confettiFall ${1.5 + Math.random()}s ease-out ${Math.random()*0.3}s forwards;
            transform:translate(${tx}px,${ty}px);opacity:0;`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 2500);
    }
}

// ============ MODALS ============
const Modal = {
    open(id) {
        const m = document.getElementById(id);
        if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    },
    close(id) {
        const m = document.getElementById(id);
        if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
    },
    closeAll() {
        document.querySelectorAll('.modal-overlay').forEach(m => {
            m.style.display = 'none';
        });
        document.body.style.overflow = '';
    }
};

// Close modal on overlay click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) Modal.closeAll();
});

// Close modal on Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') Modal.closeAll();
});

// ============ SIDEBAR MOBILE ============
const Sidebar = {
    init() {
        const toggle = document.getElementById('sidebarToggle');
        if (toggle) {
            toggle.addEventListener('click', () => {
                document.querySelector('.sidebar').classList.toggle('open');
            });
        }
    }
};

// ============ RIPPLE EFFECT ============
function addRipple(e) {
    const btn = e.currentTarget;
    const circle = document.createElement('span');
    const diameter = Math.max(btn.clientWidth, btn.clientHeight);
    const radius = diameter / 2;
    const rect = btn.getBoundingClientRect();
    circle.style.cssText = `
        position:absolute;width:${diameter}px;height:${diameter}px;
        left:${e.clientX - rect.left - radius}px;top:${e.clientY - rect.top - radius}px;
        background:rgba(255,255,255,0.25);border-radius:50%;
        transform:scale(0);animation:ripple 0.6s linear;pointer-events:none;`;
    btn.appendChild(circle);
    setTimeout(() => circle.remove(), 600);
}

document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', addRipple);
});

// ============ COUNTER ANIMATION ============
function animateCounter(el, target, duration = 1200) {
    const start = parseInt(el.textContent.replace(/\D/g, '')) || 0;
    const startTime = performance.now();
    const prefix = el.dataset.prefix || '';
    const suffix = el.dataset.suffix || '';

    function update(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        const value = Math.round(start + (target - start) * ease);
        el.textContent = prefix + value.toLocaleString() + suffix;
        if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
}

document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseInt(el.dataset.counter);
    const observer = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) {
            animateCounter(el, target);
            observer.disconnect();
        }
    });
    observer.observe(el);
});

// ============ FORM VALIDATION ============
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    let valid = true;
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--danger)';
            field.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.15)';
            valid = false;
        } else {
            field.style.borderColor = '';
            field.style.boxShadow = '';
        }
    });
    return valid;
}

// ============ TABLE SELECTION ============
window.selectTable = function(card) {
    document.querySelectorAll('.table-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    const input = document.getElementById('selectedTableId');
    if (input) input.value = card.dataset.tableId;
};

// ============ CONFIRM DELETE ============
window.confirmDelete = function(msg, form) {
    if (confirm(msg || 'Are you sure you want to delete this?')) {
        form.submit();
    }
};

// ============ AUTO DISMISS ALERTS ============
document.querySelectorAll('.alert[data-dismiss]').forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        alert.style.transition = 'all 0.3s ease';
        setTimeout(() => alert.remove(), 300);
    }, parseInt(alert.dataset.dismiss) || 5000);
});

// ============ SEARCH FILTER ============
window.filterTable = function(inputId, tableId) {
    const q = document.getElementById(inputId).value.toLowerCase();
    document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
};

// ============ INIT ============
document.addEventListener('DOMContentLoaded', () => {
    PopupNav.init(); // Initialize popup navigation
    ThemeManager.init();
    AnimationManager.init();
    Sidebar.init();

    // Show flash toast if present
    const flashEl = document.getElementById('flashData');
    if (flashEl) {
        const msg  = flashEl.dataset.msg;
        const type = flashEl.dataset.type;
        if (msg && type === 'success') {
            Toast.show('Success!', msg);
            if (flashEl.dataset.confetti === '1') {
                setTimeout(fireConfetti, 300);
            }
        }
    }

    // Add stagger animation to cards
    document.querySelectorAll('.stat-card, .card').forEach((card, i) => {
        card.style.animationDelay = `${i * 0.07}s`;
        card.style.animation = `fadeInUp 0.5s ease ${i * 0.07}s both`;
    });
});

// ============ ORDER FORM FUNCTIONALITY ============
const OrderForm = {
    menuItems: {},
    
    init() {
        // Store menu item prices
        document.querySelectorAll('.menu-item-card').forEach(card => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            const priceText = card.querySelector('.menu-item-price').textContent;
            const price = parseFloat(priceText.replace('₱', '').replace(',', ''));
            
            if (checkbox) {
                this.menuItems[checkbox.value] = { price, element: card };
            }
        });
    },
    
    toggleForm() {
        const form = document.getElementById('orderForm');
        if (form) {
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    },
    
    updateQty(itemId) {
        const checkbox = document.querySelector(`input[value="${itemId}"]`);
        const qtyInput = document.getElementById(`qty_${itemId}`);
        
        if (checkbox.checked && qtyInput.value === '0') {
            qtyInput.value = 1;
        } else if (!checkbox.checked) {
            qtyInput.value = 0;
        }
        
        this.updateSummary();
    },
    
    increaseQty(itemId) {
        const qtyInput = document.getElementById(`qty_${itemId}`);
        const checkbox = document.querySelector(`input[value="${itemId}"]`);
        const currentValue = parseInt(qtyInput.value) || 0;
        
        if (currentValue < 20) {
            qtyInput.value = currentValue + 1;
            checkbox.checked = true;
            this.updateSummary();
        }
    },
    
    decreaseQty(itemId) {
        const qtyInput = document.getElementById(`qty_${itemId}`);
        const checkbox = document.querySelector(`input[value="${itemId}"]`);
        const currentValue = parseInt(qtyInput.value) || 0;
        
        if (currentValue > 0) {
            qtyInput.value = currentValue - 1;
            if (currentValue - 1 === 0) {
                checkbox.checked = false;
            }
            this.updateSummary();
        }
    },
    
    updateSummary() {
        let subtotal = 0;
        
        document.querySelectorAll('input[name="items[]"]:checked').forEach(checkbox => {
            const itemId = checkbox.value;
            const qtyInput = document.getElementById(`qty_${itemId}`);
            const qty = parseInt(qtyInput.value) || 0;
            const item = this.menuItems[itemId];
            
            if (item && qty > 0) {
                subtotal += item.price * qty;
            }
        });
        
        const tax = subtotal * 0.08;
        const total = subtotal + tax;
        
        document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
        document.getElementById('tax').textContent = `₱${tax.toFixed(2)}`;
        document.getElementById('total').textContent = `₱${total.toFixed(2)}`;
    }
};

// Global functions for onclick handlers
function toggleOrderForm() {
    OrderForm.toggleForm();
}

function updateQty(itemId) {
    OrderForm.updateQty(itemId);
}

function increaseQty(itemId) {
    OrderForm.increaseQty(itemId);
}

function decreaseQty(itemId) {
    OrderForm.decreaseQty(itemId);
}

// Initialize order form when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.order-form')) {
        OrderForm.init();
    }
});
