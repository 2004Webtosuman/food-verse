/**
 * assets/js/rider_tracking.js
 * Unified GPS tracking system for FoodVerse riders.
 * Handles persistence across reloads and multi-page synchronization.
 */

class RiderGPS {
    constructor(options = {}) {
        this.statusIndicatorId = options.statusIndicatorId || 'gpsStatus';
        this.dotId = options.dotId || 'gpsDot';
        this.toggleBtnId = options.toggleBtnId || 'gpsToggle';
        this.coordDisplayId = options.coordDisplayId || 'coordDisplay';
        this.onLocationUpdate = options.onLocationUpdate || null;
        
        this.activeOrderIds = options.activeOrderIds || [];
        this.watchId = null;
        this.isPersistent = true;
        
        this.init();
    }

    init() {
        const savedState = localStorage.getItem('global_gps_active');
        if (savedState === 'true') {
            console.log("RiderGPS: Persistent state found (ON). Starting...");
            this.start();
        } else {
            console.log("RiderGPS: Initialized (OFF).");
            this.updateUI(false);
        }
    }

    isActive() {
        return localStorage.getItem('global_gps_active') === 'true';
    }

    toggle() {
        if (this.isActive()) {
            this.stop();
        } else {
            this.start();
        }
    }

    start() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }

        localStorage.setItem('global_gps_active', 'true');
        this.updateUI(true);

        // Immediate feedback so user doesn't see "stale" or "0.000" coordinates
        const display = document.getElementById(this.coordDisplayId);
        if (display) {
            display.innerHTML = '<span class="animate-pulse">Locating...</span>';
            const info = document.getElementById('locationInfo');
            if (info) info.classList.remove('hidden');
        }

        this.watchId = navigator.geolocation.watchPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Update local UI with high precision
                if (display) {
                    display.innerText = `Lat: ${lat.toFixed(6)} | Lng: ${lng.toFixed(6)}`;
                    const info = document.getElementById('locationInfo');
                    if (info) info.classList.remove('hidden');
                }

                // Call external hook if provided (e.g., to move map marker)
                if (this.onLocationUpdate) {
                    this.onLocationUpdate(lat, lng);
                }

                // Sync with server
                this.sync(lat, lng);
            },
            (error) => {
                console.warn('RiderGPS Error:', error.message);
                this.updateUI(false, true); // Visual error state
            },
            { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 }
        );
    }

    stop() {
        localStorage.setItem('global_gps_active', 'false');
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        this.updateUI(false);
        
        const info = document.getElementById('locationInfo');
        if (info) info.classList.add('hidden');
    }

    updateUI(active, error = false) {
        const btn = document.getElementById(this.toggleBtnId);
        const status = document.getElementById(this.statusIndicatorId);
        const dot = document.getElementById(this.dotId);

        if (active) {
            if (btn) {
                btn.innerText = 'Stop GPS';
                btn.classList.remove('bg-green-50', 'text-green-600', 'border-green-200');
                btn.classList.add('bg-red-50', 'text-red-500', 'border-red-200');
            }
            if (status) {
                status.innerHTML = `<div id="${this.dotId}" class="w-2 h-2 rounded-full bg-green-500 pulse-green"></div> GPS LIVE`;
                status.classList.remove('bg-gray-100', 'text-gray-400');
                status.classList.add('bg-green-50', 'text-green-600', 'border-green-200');
            }
        } else {
            if (btn) {
                btn.innerText = 'Start GPS';
                btn.classList.add('bg-green-50', 'text-green-600', 'border-green-200');
                btn.classList.remove('bg-red-50', 'text-red-500', 'border-red-200');
            }
            if (status) {
                status.innerHTML = `<div id="${this.dotId}" class="w-2 h-2 rounded-full ${error ? 'bg-red-500' : 'bg-gray-400'}"></div> GPS ${error ? 'ERROR' : 'OFF'}`;
                status.classList.add('bg-gray-100', 'text-gray-400');
                status.classList.remove('bg-green-50', 'text-green-600', 'border-green-200');
            }
        }
    }

    async sync(lat, lng) {
        try {
            const formData = new FormData();
            formData.append('lat', lat);
            formData.append('lng', lng);
            
            // If we are on a specific order page, include it
            const urlParams = new URLSearchParams(window.location.search);
            const currentOrderId = urlParams.get('id');
            if (currentOrderId && window.location.pathname.includes('view_order.php')) {
                formData.append('order_id', currentOrderId);
            }

            // Sync with central API
            await fetch('/food-verse/api/update_driver_location.php', {
                method: 'POST',
                body: formData
            });
        } catch (err) {
            console.error('RiderGPS Sync failed:', err);
        }
    }
}
