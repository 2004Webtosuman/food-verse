/* assets/js/modern_filter.js */

document.addEventListener('DOMContentLoaded', () => {
    const productGrid = document.getElementById('productGrid');
    const categoryItems = document.querySelectorAll('.category-item');
    const tagPills = document.querySelectorAll('.tag-pill');
    
    let activeCategory = 'all';
    let activeTags = new Set();

    // Make filterProducts globally accessible for renderProducts to call
    window.filterProducts = function() {
        const cards = document.querySelectorAll('.product-card-v2');
        const sections = document.querySelectorAll('section'); // To hide empty ones
        
        let visibleInMainGrid = 0;

        cards.forEach(card => {
            const category = (card.dataset.category || '').trim();
            const isSpicy = card.dataset.spicy == '1';
            const isPremium = card.dataset.premium == '1';
            const isCombo = card.dataset.combo == '1';
            const isHealthy = card.dataset.healthy == '1';

            let categoryMatch = activeCategory === 'all' || category === activeCategory;
            
            let tagsMatch = true;
            if (activeTags.size > 0) {
                activeTags.forEach(tag => {
                    if (tag === 'spicy' && !isSpicy) tagsMatch = false;
                    if (tag === 'premium' && !isPremium) tagsMatch = false;
                    if (tag === 'combo' && !isCombo) tagsMatch = false;
                    if (tag === 'healthy' && !isHealthy) tagsMatch = false;
                });
            }

            if (categoryMatch && tagsMatch) {
                card.style.display = 'flex';
                card.style.animation = 'none';
                void card.offsetWidth; // Trigger reflow
                card.style.animation = 'fadeInScale 0.4s ease-out forwards';
                
                // Track visibility for main grid count
                if (card.closest('#productGrid')) {
                    visibleInMainGrid++;
                }
            } else {
                card.style.display = 'none';
            }
        });

        // Smart Section Hiding: Hide recommendation sections if they have no visible products
        sections.forEach(section => {
            const recScroll = section.querySelector('.rec-scroll');
            if (recScroll) {
                const visibleInRec = Array.from(recScroll.querySelectorAll('.product-card-v2')).filter(c => c.style.display !== 'none').length;
                if (visibleInRec === 0 && activeCategory !== 'all') {
                    section.style.display = 'none';
                } else {
                    section.style.display = 'block';
                }
            }
        });

        // Update heading count
        const headingText = document.getElementById('headingText');
        const productGrid = document.getElementById('productGrid');
        if (headingText) {
            headingText.textContent = `${activeCategory === 'all' ? 'All Products' : activeCategory} (${visibleInMainGrid})`;
        }

        // Handle Empty State for main grid
        let emptyState = document.getElementById('emptyState');
        if (visibleInMainGrid === 0 && productGrid) {
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.id = 'emptyState';
                emptyState.className = 'col-span-full flex flex-col items-center justify-center py-20 opacity-60';
                emptyState.innerHTML = `
                    <i data-lucide="search-x" class="w-16 h-16 mb-4 text-gray-300"></i>
                    <h3 class="text-xl font-bold text-gray-400">No matching products</h3>
                    <p class="text-sm text-gray-400 mt-2">Try adjusting your filters or category</p>
                `;
                productGrid.appendChild(emptyState);
                if (window.lucide) lucide.createIcons();
            }
        } else if (emptyState) {
            emptyState.remove();
        }
    }

    // Category Click Handler
    categoryItems.forEach(item => {
        item.addEventListener('click', () => {
            categoryItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            activeCategory = (item.dataset.category || '').trim();
            if (window.filterProducts) window.filterProducts();
        });
    });

    // Tag Pill Click Handler
    tagPills.forEach(pill => {
        pill.addEventListener('click', () => {
            const tag = pill.dataset.tag;
            if (pill.classList.contains('active')) {
                pill.classList.remove('active');
                activeTags.delete(tag);
            } else {
                pill.classList.add('active');
                activeTags.add(tag);
            }
            if (window.filterProducts) window.filterProducts();
        });
    });
});
