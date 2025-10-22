import { ref, watch, onMounted, onUnmounted } from 'vue';

export function useAddressAutocomplete(form) {
    const suggestions = ref([]);
    const isSelecting = ref(false);
    const showSuggestions = ref(false);
    const isLoading = ref(false);
    const activeSuggestionIndex = ref(-1);
    const addressInputRef = ref(null);
    let searchTimeout = null;

    const searchAddress = async (query) => {
        if (!query || query.length < 3 || isSelecting.value) {
            suggestions.value = [];
            showSuggestions.value = false;
            return;
        }
        showSuggestions.value = true;
        isLoading.value = true;
        activeSuggestionIndex.value = -1;
        try {
            const params = new URLSearchParams({
                q: query,
                countrycodes: 'ES',
                format: 'json',
                addressdetails: 1,
                limit: 5,
                'accept-language': 'es',
            });
            const userAgent = 'OrangesohoApp/1.0 (ccklbparda@gmail.com)';
            const response = await fetch(`https://nominatim.openstreetmap.org/search?${params}`, {
                headers: { 'User-Agent': userAgent },
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            suggestions.value = await response.json();
        } catch (error) {
            console.error('Error al buscar dirección:', error);
            suggestions.value = [];
        } finally {
            isLoading.value = false;
        }
    };

    const selectSuggestion = (suggestion) => {
        if (!suggestion) return;
        isSelecting.value = true;
        const addr = suggestion.address;
        if (addr) {
            form.address = addr.road || '';
            form.street_number = addr.house_number || '';
            form.city = addr.city || addr.town || addr.village || '';
            form.postal_code = addr.postcode || '';
        } else {
            form.address = suggestion.display_name;
            form.street_number = '';
            form.city = '';
            form.postal_code = '';
        }
        form.floor = '';
        form.door = '';
        showSuggestions.value = false;
        activeSuggestionIndex.value = -1;
        setTimeout(() => { isSelecting.value = false; }, 100);
    };

    watch(() => form.address, (newVal) => {
        if (isSelecting.value) return;
        clearTimeout(searchTimeout);
        if (newVal && newVal.length >= 3) {
            searchTimeout = setTimeout(() => searchAddress(newVal), 500);
        } else {
            suggestions.value = [];
            showSuggestions.value = false;
        }
    });

    const handleClickOutside = (event) => {
        if (addressInputRef.value && addressInputRef.value.$el && !addressInputRef.value.$el.contains(event.target)) {
            showSuggestions.value = false;
        }
    };

    onMounted(() => document.addEventListener('click', handleClickOutside));
    onUnmounted(() => document.removeEventListener('click', handleClickOutside));

    const onArrowDown = () => {
        if (activeSuggestionIndex.value < (suggestions.value?.length ?? 0) - 1) {
            activeSuggestionIndex.value++;
        }
    };
    
    const onArrowUp = () => {
        if (activeSuggestionIndex.value > 0) {
            activeSuggestionIndex.value--;
        }
    };
    
    const onEnter = () => {
        selectSuggestion(suggestions.value?.[activeSuggestionIndex.value]);
    };

    const highlightMatch = (text) => {
        if (!form.address || form.address.length < 3) return text;
        const escapedQuery = form.address.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${escapedQuery})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    };

    return {
        suggestions,
        showSuggestions,
        isLoading,
        activeSuggestionIndex,
        addressInputRef,
        onArrowDown,
        onArrowUp,
        onEnter,
        highlightMatch,
        selectSuggestion, // Devuelta para el @click
    };
}