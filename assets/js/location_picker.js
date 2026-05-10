/**
 * location_picker.js
 * Handles Live Location (GPS) and Manual Structured Selection (Nepal Provinces/Districts/Municipalities)
 */

class LocationPicker {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            onSave: null,
            initialData: null,
            ...options
        };
        this.locationData = null;
        this.selectedProvince = null;
        this.selectedDistrict = null;
        this.selectedMunicipality = null;
        this.coordinates = null;

        this.init();
    }

    async init() {
        try {
            // Load Nepalese location data
            const response = await fetch('/food-verse/config/nepal_locations.json');
            this.locationData = await response.json();
            this.render();
        } catch (error) {
            console.error('Failed to initialize LocationPicker:', error);
            this.container.innerHTML = '<p class="text-red-500">Error loading location data.</p>';
        }
    }

    render() {
        this.container.innerHTML = `
            <div class="space-y-6">
                <!-- Toggle Mode -->
                <div class="flex bg-gray-100 p-1 rounded-2xl border border-card-border">
                    <button id="btn-live" class="flex-1 py-3 rounded-xl text-sm font-bold transition-all bg-primary text-white">Live Location</button>
                    <button id="btn-manual" class="flex-1 py-3 rounded-xl text-sm font-bold transition-all text-gray-400 hover:text-gray-800">Manual Selection</button>
                </div>

                <!-- Live Location View -->
                <div id="view-live" class="space-y-4">
                    <div class="bg-white p-6 rounded-3xl border border-card-border text-center space-y-4 shadow-sm">
                        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto">
                            <i data-lucide="map-pin" class="w-8 h-8 text-primary"></i>
                        </div>
                        <div>
                            <h4 class="font-bold">Use GPS Location</h4>
                            <p class="text-xs text-gray-400 mt-1">Faster and more accurate delivery</p>
                        </div>
                        <button id="btn-detect" class="w-full py-4 bg-primary text-white rounded-2xl font-black italic tracking-wider hover:bg-primary-hover hover:scale-[1.02] active:scale-[0.98] transition-all">
                            DETECT MY LOCATION
                        </button>
                        <p id="geo-status" class="text-[10px] text-gray-500"></p>
                    </div>

                    <!-- Detection Results (Hidden by default) -->
                    <div id="detection-results" class="hidden bg-green-50 p-6 rounded-3xl border border-green-200 space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div class="flex items-center gap-3">
                            <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                            <h4 class="font-bold text-sm text-gray-900">Location Detected!</h4>
                        </div>
                        <div class="space-y-1 pl-8">
                            <p id="detected-address" class="text-sm text-gray-800 font-medium"></p>
                            <p class="text-[10px] text-gray-500 italic">Please confirm or edit below</p>
                        </div>
                    </div>
                </div>

                <!-- Manual Selection View (Hidden by default) -->
                <div id="view-manual" class="space-y-4 hidden">
                    <div class="space-y-4">
                        <!-- Province Dropdown -->
                        <div>
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest ml-1">Province</label>
                            <select id="select-province" class="w-full mt-1 p-4 bg-white border border-card-border rounded-2xl text-sm text-gray-800 focus:border-primary outline-none transition-all">
                                <option value="" disabled selected>Select Province</option>
                                ${this.locationData.provinces.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
                            </select>
                        </div>

                        <!-- District Dropdown -->
                        <div>
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest ml-1">District</label>
                            <select id="select-district" disabled class="w-full mt-1 p-4 bg-white border border-card-border rounded-2xl text-sm text-gray-800 focus:border-primary outline-none transition-all opacity-50">
                                <option value="" disabled selected>Select Province first</option>
                            </select>
                        </div>

                        <!-- Municipality Dropdown -->
                        <div>
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest ml-1">Municipality (Palika)</label>
                            <select id="select-municipality" disabled class="w-full mt-1 p-4 bg-white border border-card-border rounded-2xl text-sm text-gray-800 focus:border-primary outline-none transition-all opacity-50">
                                <option value="" disabled selected>Select District first</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Footer Action -->
                <div class="pt-4">
                    <button id="btn-save-location" disabled class="w-full py-4 bg-primary text-white rounded-2xl font-black italic tracking-wider opacity-50 transition-all">
                        CONFIRM LOCATION
                    </button>
                    <p id="validation-msg" class="text-center text-xs text-red-400 mt-2 hidden"></p>
                </div>
            </div>
        `;

        lucide.createIcons();
        this.attachEventListeners();
    }

    attachEventListeners() {
        const btnLive = this.container.querySelector('#btn-live');
        const btnManual = this.container.querySelector('#btn-manual');
        const viewLive = this.container.querySelector('#view-live');
        const viewManual = this.container.querySelector('#view-manual');
        const btnDetect = this.container.querySelector('#btn-detect');
        const btnSave = this.container.querySelector('#btn-save-location');
        const geoStatus = this.container.querySelector('#geo-status');
        const detectionResults = this.container.querySelector('#detection-results');
        const detectedAddr = this.container.querySelector('#detected-address');

        const selectProvince = this.container.querySelector('#select-province');
        const selectDistrict = this.container.querySelector('#select-district');
        const selectMunicipality = this.container.querySelector('#select-municipality');

        // Toggle Switch
        btnLive.addEventListener('click', () => {
            btnLive.classList.add('bg-primary', 'text-white');
            btnLive.classList.remove('text-gray-400');
            btnManual.classList.remove('bg-primary', 'text-white');
            btnManual.classList.add('text-gray-400');
            viewLive.classList.remove('hidden');
            viewManual.classList.add('hidden');
        });

        btnManual.addEventListener('click', () => {
            btnManual.classList.add('bg-primary', 'text-white');
            btnManual.classList.remove('text-gray-400');
            btnLive.classList.remove('bg-primary', 'text-white');
            btnLive.classList.add('text-gray-400');
            viewManual.classList.remove('hidden');
            viewLive.classList.add('hidden');
        });

        // Manual Selection Logic
        selectProvince.addEventListener('change', (e) => {
            const provinceId = parseInt(e.target.value);
            this.selectedProvince = this.locationData.provinces.find(p => p.id === provinceId);
            
            // Update Districts
            selectDistrict.disabled = false;
            selectDistrict.classList.remove('opacity-50');
            selectDistrict.innerHTML = `<option value="" disabled selected>Select District</option>` +
                this.selectedProvince.districts.map(d => `<option value="${d.name}">${d.name}</option>`).join('');
            
            // Reset Municipality
            selectMunicipality.disabled = true;
            selectMunicipality.classList.add('opacity-50');
            selectMunicipality.innerHTML = `<option value="" disabled selected>Select District first</option>`;
            
            this.validate();
        });

        selectDistrict.addEventListener('change', (e) => {
            const districtName = e.target.value;
            this.selectedDistrict = this.selectedProvince.districts.find(d => d.name === districtName);
            
            // Update Municipalities
            selectMunicipality.disabled = false;
            selectMunicipality.classList.remove('opacity-50');
            selectMunicipality.innerHTML = `<option value="" disabled selected>Select Municipality</option>` +
                this.selectedDistrict.municipalities.map(m => `<option value="${m}">${m}</option>`).join('');
            
            this.validate();
        });

        selectMunicipality.addEventListener('change', (e) => {
            this.selectedMunicipality = e.target.value;
            this.validate();
        });

        // Geolocation Logic
        btnDetect.addEventListener('click', () => {
            if (!navigator.geolocation) {
                geoStatus.innerText = "Geolocation is not supported by your browser.";
                return;
            }

            geoStatus.innerText = "Requesting permission...";
            detectionResults.classList.add('hidden');
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    this.coordinates = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    geoStatus.innerText = `Detected: ${this.coordinates.lat.toFixed(4)}, ${this.coordinates.lng.toFixed(4)}`;
                    
                    // Call Reverse Geocoding
                    await this.reverseGeocode(this.coordinates.lat, this.coordinates.lng);
                },
                (error) => {
                    geoStatus.innerText = `Error: ${error.message}. Switching to manual.`;
                    btnManual.click(); // Fallback to manual
                }
            );
        });

        btnSave.addEventListener('click', () => this.saveLocation());
    }
    async reverseGeocode(lat, lng) {
        const geoStatus = this.container.querySelector('#geo-status');
        geoStatus.innerText = "Mapping coordinates to address (Free OSM)...";

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
            const data = await response.json();

            if (data && data.address) {
                const addr = data.address;
                const formattedAddress = data.display_name;
                
                const components = {
                    municipality: addr.municipality || addr.city || addr.town || addr.village || addr.suburb || "",
                    district: addr.county || addr.state_district || addr.district || "",
                    province: addr.state || ""
                };
                
                console.log('OSM Components:', components);
                this.performUnifiedMatch(components.province, components.district, components.municipality, formattedAddress);
            } else {
                geoStatus.innerText = "OSM match unavailable. Using Local GPS Matching...";
                setTimeout(() => this.matchNearestLocal(lat, lng), 500);
            }
        } catch (error) {
            console.error('Reverse geocode error:', error);
            geoStatus.innerText = "Network error. Using Local Distance Matching...";
            setTimeout(() => this.matchNearestLocal(lat, lng), 500);
        }
    }

    // --- LOCAL OFFLINE MATCHING LOGIC ---

    matchNearestLocal(lat, lng) {
        if (!window.nepalLocations) {
            console.error("Local location data (districts.js) not loaded.");
            this.container.querySelector('#btn-manual').click();
            return;
        }

        let minDistance = Infinity;
        let closest = null;

        for (const loc of window.nepalLocations) {
            const dist = this.calculateDistance(lat, lng, loc.lat, loc.lng);
            if (dist < minDistance) {
                minDistance = dist;
                closest = loc;
            }
        }

        if (closest) {
            console.log("Nearest local match:", closest, "Distance:", minDistance.toFixed(2), "km");
            const [muniName, distName] = closest.name.split(',').map(s => s.trim());
            this.performUnifiedMatch("", distName, muniName, closest.name);
        } else {
            this.container.querySelector('#geo-status').innerText = "No local match found. Please select manually.";
            this.container.querySelector('#btn-manual').click();
        }
    }

    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    syncManualSelects(pId, dName, mName) {
        const selectProv = this.container.querySelector('#select-province');
        const selectDist = this.container.querySelector('#select-district');
        const selectMuni = this.container.querySelector('#select-municipality');

        if (!selectProv) return;

        // 1. Set Province and trigger change to populate districts
        selectProv.value = pId;
        
        // We manually trigger the logic since event listeners might be async or blocked
        this.selectedProvince = this.locationData.provinces.find(p => p.id == pId);
        
        if (this.selectedProvince) {
            selectDist.disabled = false;
            selectDist.classList.remove('opacity-50');
            selectDist.innerHTML = `<option value="" disabled>Select District</option>` +
                this.selectedProvince.districts.map(d => `<option value="${d.name}" ${d.name === dName ? 'selected' : ''}>${d.name}</option>`).join('');
            
            this.selectedDistrict = this.selectedProvince.districts.find(d => d.name === dName);
            
            if (this.selectedDistrict) {
                selectMuni.disabled = false;
                selectMuni.classList.remove('opacity-50');
                selectMuni.innerHTML = `<option value="" disabled>Select Municipality</option>` +
                    this.selectedDistrict.municipalities.map(m => `<option value="${m}" ${m === mName ? 'selected' : ''}>${m}</option>`).join('');
                
                this.selectedMunicipality = mName;
            }
        }
        
        this.validate();
    }

    performUnifiedMatch(provName, distName, muniName, displayAddr) {
        const geoStatus = this.container.querySelector('#geo-status');
        const detectionResults = this.container.querySelector('#detection-results');
        const detectedAddr = this.container.querySelector('#detected-address');
        const btnManual = this.container.querySelector('#btn-manual');

        // Helper for fuzzy comparison
        const fuzzyMatch = (target, input) => {
            if (!target || !input) return false;
            const clean = (str) => str.toLowerCase()
                .replace(/municipality|rural|metro|sub-|province|district|nagarpalika|gaunpalika/g, "")
                .replace(/bagmati/g, "bagamati") // Standardize Bagmati variations
                .replace(/\s/g, "")
                .trim();
            
            const t = clean(target);
            const i = clean(input);
            return t.includes(i) || i.includes(t);
        };

        let matchedProv = null;
        let matchedDist = null;
        let matchedMuni = null;

        console.log("Starting Unified Match for:", { provName, distName, muniName });

        // 1. Match Province
        for (const p of this.locationData.provinces) {
            if (fuzzyMatch(p.name, provName)) {
                matchedProv = p;
                break;
            }
        }

        // 2. Match District
        if (matchedProv) {
            matchedDist = matchedProv.districts.find(d => fuzzyMatch(d.name, distName));
        } else {
            // Search all provinces if province name was unrecognizable
            for (const p of this.locationData.provinces) {
                const d = p.districts.find(d => fuzzyMatch(d.name, distName));
                if (d) {
                    matchedProv = p;
                    matchedDist = d;
                    break;
                }
            }
        }

        // 3. Match Municipality within District
        if (matchedDist) {
            matchedMuni = matchedDist.municipalities.find(m => fuzzyMatch(m, muniName));
        }

        if (matchedProv && matchedDist && matchedMuni) {
            this.selectedProvince = matchedProv;
            this.selectedDistrict = matchedDist;
            this.selectedMunicipality = matchedMuni;

            geoStatus.innerText = "Location matched!";
            detectedAddr.innerText = `${matchedMuni}, ${matchedDist.name}, ${matchedProv.name}`;
            detectionResults.classList.remove('hidden');

            // Sync Dropdowns and Validate
            this.syncManualSelects(matchedProv.id, matchedDist.name, matchedMuni);
        } else {
            console.warn("Match failed or partial:", { matchedProv, matchedDist, matchedMuni });
            geoStatus.innerText = `Detected ${muniName}, ${distName}. Please select manually.`;
            
            // Switch to manual view if match fails
            setTimeout(() => {
                btnManual.click();
                if (matchedProv) {
                    this.syncManualSelects(matchedProv.id, (matchedDist ? matchedDist.name : ""), "");
                }
            }, 1000);
        }
    }

    validate() {
        const btnSave = this.container.querySelector('#btn-save-location');
        const isValid = !!(this.selectedProvince && this.selectedDistrict && this.selectedMunicipality);
        
        btnSave.disabled = !isValid;
        if (isValid) {
            btnSave.classList.remove('opacity-50');
            btnSave.classList.add('hover:bg-gray-100', 'active:scale-95');
        } else {
            btnSave.classList.add('opacity-50');
            btnSave.classList.remove('hover:bg-gray-100', 'active:scale-95');
        }

        return isValid;
    }

    async saveLocation() {
        const btnSave = this.container.querySelector('#btn-save-location');
        const validationMsg = this.container.querySelector('#validation-msg');
        
        btnSave.innerText = "SAVING...";
        btnSave.disabled = true;

        const formData = new FormData();
        formData.append('province', this.selectedProvince.name);
        formData.append('district', this.selectedDistrict.name);
        formData.append('municipality', this.selectedMunicipality);
        if (this.coordinates) {
            formData.append('latitude', this.coordinates.lat);
            formData.append('longitude', this.coordinates.lng);
        }

        try {
            const response = await fetch('/food-verse/actions/save_location.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                if (this.options.onSave) {
                    this.options.onSave(data.location);
                }
            } else {
                validationMsg.innerText = data.message;
                validationMsg.classList.remove('hidden');
                btnSave.innerText = "CONFIRM LOCATION";
                btnSave.disabled = false;
            }
        } catch (error) {
            console.error('Save location error:', error);
            validationMsg.innerText = "Network error. Failed to save location.";
            validationMsg.classList.remove('hidden');
            btnSave.innerText = "CONFIRM LOCATION";
            btnSave.disabled = false;
        }
    }
}
