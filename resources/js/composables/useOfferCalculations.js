import { computed } from 'vue';

// --- INICIO MODIFICACIÓN BENEFICIOS ---
/**
 * Aplica una regla de beneficio a un precio original.
 * @param {number} originalPrice - El precio base del addon.
 * @param {object | null} benefit - El objeto de beneficio (de la BBDD).
 * @returns {number} - El precio final después de aplicar el beneficio.
 */
function applyBenefit(originalPrice, benefit) {
    if (!benefit) {
        return originalPrice; // Sin beneficio, devuelve el precio original
    }

    switch (benefit.apply_type) {
        case 'free':
            return 0; // El producto es gratis
        case 'percentage_discount':
            const discount = originalPrice * (parseFloat(benefit.apply_value || 0) / 100);
            return Math.max(0, originalPrice - discount); // Precio - X%
        case 'fixed_discount':
            return Math.max(0, originalPrice - parseFloat(benefit.apply_value || 0)); // Precio - X€
        default:
            return originalPrice;
    }
}
// --- FIN MODIFICACIÓN BENEFICIOS ---


// Recibe las props y refs necesarios *específicamente* para el cálculo.
export function useOfferCalculations(
    props, // Necesita: packages, discounts, portabilityCommission, auth, centralitaExtensions, additionalInternetAddons, fiberFeatures, allAddons
    selectedPackageId, // ref
    lines, // ref
    selectedInternetAddonId, // ref
    additionalInternetLines, // ref
    selectedCentralitaId, // ref
    centralitaExtensionQuantities, // ref
    isOperadoraAutomaticaSelected, // ref
    selectedTvAddonIds, // ref
    selectedDigitalAddonIds, // <-- AÑADIDO
    form, // <-- Recibe el objeto 'form' completo
    // --- INICIO MODIFICACIÓN BENEFICIOS ---
    selectedBenefits // <-- ¡NUEVO ARGUMENTO! Un computed ref de los objetos de beneficio seleccionados
    // --- FIN MODIFICACIÓN BENEFICIOS ---
) {

    // --- Computeds auxiliares INTERNAS al cálculo ---
    const selectedPackage = computed(() => {
        return props.packages.find(p => p.id === selectedPackageId.value) || null;
    });
    const internetAddonOptions = computed(() => { // Necesario para selectedInternetAddonInfo
        if (!selectedPackage.value?.addons) return [];
        return selectedPackage.value.addons.filter(a => a.type === 'internet');
    });
    const selectedInternetAddonInfo = computed(() => {
        if (!selectedInternetAddonId.value || !internetAddonOptions.value.length) return null;
        return internetAddonOptions.value.find(a => a.id === selectedInternetAddonId.value);
    });
    const tvAddonOptions = computed(() => { // Necesario para cálculo TV
        // --- INICIO MODIFICACIÓN BENEFICIOS ---
        // Ahora buscamos en TODOS los addons, no solo en los del paquete
        if (!props.allAddons) return [];
        // Filtra los addons de tipo 'tv' que NO son beneficios de 'Hogar' (Disney/Amazon)
        // para no duplicarlos en la sección "Televisión".
        const benefitHogarAddonIds = selectedPackage.value?.benefits
            ?.filter(b => b.category === 'Hogar')
            .map(b => b.addon_id) || [];
        
        // Muestra los addons de TV que están en el paquete (como 'Futbol Bares')
        const packageTvAddons = selectedPackage.value?.addons.filter(a => a.type === 'tv') || [];
        // Opcional: Muestra addons de TV globales que no son beneficios
        // const globalTvAddons = props.allAddons.filter(a => 
        //     a.type === 'tv' && 
        //     !benefitHogarAddonIds.includes(a.id) &&
        //     !packageTvAddons.some(pkgAddon => pkgAddon.id === a.id)
        // );
        
        // Devolvemos solo los que están explícitamente en el paquete
        return packageTvAddons;
        // --- FIN MODIFICACIÓN BENEFICIOS ---
    });
    const centralitaAddonOptions = computed(() => { // Necesario para cálculo Centralita
        if (!selectedPackage.value?.addons) return [];
        return selectedPackage.value.addons.filter(a => a.type === 'centralita' && !a.pivot.is_included);
    });
    const includedCentralita = computed(() => { // Necesario para cálculo Centralita
        if (!selectedPackage.value?.addons) return null;
        return selectedPackage.value.addons.find(a => a.type === 'centralita' && a.pivot.is_included);
    });
    const isCentralitaActive = computed(() => { // Necesario para cálculo Centralita/Extensiones/Operadora/IP Fija
        return !!includedCentralita.value || !!selectedCentralitaId.value || additionalInternetLines.value.some(line => !!line.selected_centralita_id);
    });
    const autoIncludedExtension = computed(() => { // Necesario para cálculo Extensiones
        if (!selectedCentralitaId.value) return null;
        const selectedCentralita = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
        if (!selectedCentralita) return null;
        const centralitaType = selectedCentralita.name.split(' ')[1];
        if (!centralitaType) return null;
        return props.centralitaExtensions.find(ext => ext.name.includes(centralitaType));
    });
    const includedCentralitaExtensions = computed(() => { // Necesario para cálculo Extensiones
        if (!isCentralitaActive.value || !selectedPackage.value?.addons) return [];
        return selectedPackage.value.addons.filter(addon =>
            addon.type === 'centralita_extension' && addon.pivot.is_included
        );
    });
    const operadoraAutomaticaInfo = computed(() => { // Necesario para cálculo Operadora
        if (!selectedPackage.value?.addons) return null;
        return selectedPackage.value.addons.find(a => a.type === 'centralita_feature' && a.name === 'Operadora Automática');
    });

    // --- INICIO CORRECCIÓN DE BUG ---
    // Volvemos a la lógica original. El Addon "mobile_line" se define EN EL PAQUETE,
    // ya que necesitamos sus datos del PIVOT (precio, límite de líneas).
    const mobileAddonInfo = computed(() => { // Necesario para cálculo Líneas Móviles
        if (!selectedPackage.value?.addons) return null;
        return selectedPackage.value.addons.find(a => a.type === 'mobile_line');
    });
    // --- FIN CORRECCIÓN DE BUG ---

    const availableO2oDiscounts = computed(() => { // Necesario para cálculo O2O
        if (!selectedPackage.value) return [];
        return selectedPackage.value.o2o_discounts || [];
    });

    // --- Computed para IP Fija (Addon principal) ---
    const ipFijaAddonInfo = computed(() => {
        if (!props.fiberFeatures || props.fiberFeatures.length === 0) return null;
        return props.fiberFeatures.find(f => f.name === 'IP Fija');
    });
    // --- FIN ---

    // --- INICIO: AÑADIDO PARA FIBRA ORO ---
    const fibraOroAddonInfo = computed(() => {
        if (!props.fiberFeatures || props.fiberFeatures.length === 0) return null;
        return props.fiberFeatures.find(f => f.name === 'Fibra Oro');
    });
    // --- FIN: AÑADIDO PARA FIBRA ORO ---

    // --- INICIO: AÑADIDO PARA DDI ---
    const ddiAddonInfo = computed(() => {
        if (!props.allAddons) return null;
        return props.allAddons.find(a => a.name === 'DDI' && a.type === 'centralita_feature');
    });
    // --- FIN: AÑADIDO PARA DDI ---


    // --- INICIO MODIFICACIÓN BENEFICIOS ---
    /**
     * Crea un Map de los beneficios seleccionados para búsquedas rápidas.
     * La clave es el 'addon_id' y el valor es el objeto 'benefit'.
     */
    const activeBenefitsMap = computed(() => {
        const map = new Map();
        if (!selectedBenefits.value) return map;
        
        for (const benefit of selectedBenefits.value) {
            // benefit.addon_id es la clave (el producto al que aplica)
            map.set(benefit.addon_id, benefit);
        }
        return map;
    });
    // --- FIN MODIFICACIÓN BENEFICIOS ---

    // =================================================================
    // =========== LÓGICA DE DESCUENTOS (Tarifa) ============
    // =================================================================
    const appliedDiscount = computed(() => {
        if (!lines.value || lines.value.length === 0 || !selectedPackage.value) {
            return null;
        }

        const principalLine = lines.value[0];
        if (!principalLine) return null;

        const packageName = selectedPackage.value.name;

        const hasTVBares = selectedTvAddonIds.value.some(id => {
            // Buscamos en TODOS los addons, no solo en los del paquete
            const tvAddon = props.allAddons.find(a => a.id === id);
            return tvAddon && tvAddon.name.includes('Futbol Bares');
        });

        // 1. Si se ha contratado TV Bares
        if (hasTVBares) {
            const matchingTvDiscount = props.discounts.find(d => {
                const conditions = d.conditions;
                if (!conditions.requires_tv_bares) return false;

                const packageMatch = conditions.package_names.includes(packageName);
                const portabilityMatch = conditions.requires_portability === principalLine.is_portability;
                const vapMatch = conditions.requires_vap === principalLine.has_vap;

                if(packageMatch && portabilityMatch && vapMatch) {
                    return true;
                }
                return false;
            });
            return matchingTvDiscount || null;
        }

        // 2. Si NO se ha contratado TV Bares
        else {
            const applicableGeneralDiscounts = props.discounts.filter(d => {
                const conditions = d.conditions;
                if (conditions.requires_tv_bares) {
                    return false;
                }
                if (!conditions.package_names || !conditions.package_names.includes(packageName)) {
                    return false;
                }
                if (conditions.hasOwnProperty('requires_vap') && conditions.requires_vap !== principalLine.has_vap) {
                    return false;
                }
                if (conditions.hasOwnProperty('requires_portability') && conditions.requires_portability !== principalLine.is_portability) {
                    return false;
                }
                if (conditions.hasOwnProperty('source_operators') && conditions.source_operators && !conditions.source_operators.includes(principalLine.source_operator)) {
                    return false;
                }
                if (conditions.hasOwnProperty('excluded_operators') && conditions.excluded_operators && conditions.excluded_operators.includes(principalLine.source_operator)) {
                    return false;
                }
                return true;
            });

            if (applicableGeneralDiscounts.length > 0) {
                const bestDiscount = applicableGeneralDiscounts.sort((a, b) => b.percentage - a.percentage)[0];
                return bestDiscount;
            }
        }

        return null;
    });
    // =================================================================
    // ======================= FIN LÓGICA DESCUENTOS ===================
    // =================================================================

    // --- Fin Computeds auxiliares INTERNAS ---

    // --- CALCULATION SUMMARY ---
    const calculationSummary = computed(() => {
        if (!selectedPackage.value) {
            return { basePrice: 0, finalPrice: 0, summaryBreakdown: [], totalInitialPayment: 0, totalCommission: 0, teamCommission: 0, userCommission: 0, commissionDetails: {} };
        }

        const basePrice = parseFloat(selectedPackage.value.base_price) || 0;
        let price = basePrice;
        let summaryBreakdown = [{ description: `Paquete Base: ${selectedPackage.value.name}`, price: basePrice }];
        // --- INICIO MODIFICACIÓN BENEFICIOS ---
        let commissionDetails = { Fibra: [], Televisión: [], Centralita: [], "Líneas Móviles": [], Terminales: [], Ajustes: [], "Servicios": [] }; // <-- AÑADIDO "Servicios"
        // --- FIN MODIFICACIÓN BENEFICIOS ---

        // --- INICIO MODIFICACIÓN FIBRA ORO (Límite 1) ---
        // 1. Buscamos el ID del addon de Fibra Oro.
        const fibraOroAddonId = fibraOroAddonInfo.value?.id || null;
        // 2. Comprobamos si el beneficio de Fibra Oro está activo en la oferta.
        const isFibraOroBenefitActive = activeBenefitsMap.value.has(fibraOroAddonId);
        // 3. Este flag controlará que solo se aplique una vez.
        let isFibraOroBenefitAvailable = isFibraOroBenefitActive; // Flag para controlar que el beneficio se aplique solo una vez
        // --- FIN MODIFICACIÓN FIBRA ORO ---

        if (appliedDiscount.value) {
            const discountAmount = basePrice * (parseFloat(appliedDiscount.value.percentage) / 100);
            price -= discountAmount;
            summaryBreakdown.push({ description: `Descuento Tarifa (${appliedDiscount.value.percentage}%)`, price: -discountAmount });
        }
        if (selectedInternetAddonInfo.value) {
            // --- INICIO MODIFICACIÓN BENEFICIOS ---
            const originalPrice = parseFloat(selectedInternetAddonInfo.value.pivot.price) || 0;
            const benefit = activeBenefitsMap.value.get(selectedInternetAddonInfo.value.id);
            const itemPrice = applyBenefit(originalPrice, benefit);
            // --- FIN MODIFICACIÓN BENEFICIOS ---

            price += itemPrice;
            if (itemPrice > 0) summaryBreakdown.push({ description: `Mejora Fibra (${selectedInternetAddonInfo.value.name})`, price: itemPrice });
            commissionDetails.Fibra.push({ description: `Fibra Principal (${selectedInternetAddonInfo.value.name})`, amount: parseFloat(selectedInternetAddonInfo.value.pivot.included_line_commission) || 0 });
        }

        // --- INICIO: LÓGICA DE FIBRA ORO PRINCIPAL (MOVIDA PARA PRIORIDAD) ---
        if (form.is_fibra_oro_selected && fibraOroAddonInfo.value) {
            
            const originalPrice = parseFloat(fibraOroAddonInfo.value.price) || 0;
            const baseCommission = parseFloat(fibraOroAddonInfo.value.commission) || 0;
            const decommission = parseFloat(fibraOroAddonInfo.value.decommission) || 0;

            let itemPrice = originalPrice;
            let description = 'Fibra Oro Principal';
            let commissionAmount = baseCommission; 

            // Aplicar el beneficio si está disponible
            if (isFibraOroBenefitAvailable) {
                const benefit = activeBenefitsMap.value.get(fibraOroAddonId);
                itemPrice = applyBenefit(originalPrice, benefit);
                description = 'Fibra Oro Principal (Beneficio)';
                
                if (benefit && benefit.apply_type === 'free') {
                    if (decommission > 0) {
                        commissionDetails.Ajustes.push({ description: `Ajuste Decomisión (Fibra Oro Pr.)`, amount: -decommission });
                    }
                }
                
                isFibraOroBenefitAvailable = false; // ¡MARCAR COMO USADO!
            }
            
            price += itemPrice;
            summaryBreakdown.push({ description: description, price: itemPrice });

            if (commissionAmount > 0) {
                commissionDetails.Fibra.push({ description: 'Fibra Oro Principal', amount: commissionAmount });
            }
        }
        // --- FIN: LÓGICA DE FIBRA ORO PRINCIPAL ---

        // --- INICIO CÓDIGO MODIFICADO: Añadir cálculo IP Fija adicional y Centralita Multisede ---
        additionalInternetLines.value.forEach((line, index) => {
            if (line.addon_id) {
                const addonInfo = props.additionalInternetAddons.find(a => a.id === line.addon_id);
                if (addonInfo) {
                    // --- INICIO MODIFICACIÓN BENEFICIOS ---
                    const originalPrice = parseFloat(addonInfo.price) || 0;
                    const benefit = activeBenefitsMap.value.get(addonInfo.id);
                    const linePrice = applyBenefit(originalPrice, benefit);
                    const description = benefit ? `Internet Adicional ${index + 1} (Beneficio)` : `Internet Adicional ${index + 1} (${addonInfo.name})`;
                    
                    // --- INICIO CORRECCIÓN 1: Internet Adicional (Benefit Rule) ---
                    const baseCommission = parseFloat(addonInfo.commission) || 0;
                    const decommission = parseFloat(addonInfo.decommission) || 0;
                    const commission = baseCommission; // "Ponerla toda"
                    
                    if (benefit && benefit.apply_type === 'free' && decommission > 0) {
                        commissionDetails.Ajustes.push({ description: `Ajuste Decomisión (${addonInfo.name})`, amount: -decommission });
                    }
                    // --- FIN CORRECCIÓN 1 ---

                    price += linePrice;
                    summaryBreakdown.push({ description: description, price: linePrice });
                    if(commission > 0) {
                        commissionDetails.Fibra.push({ description: `Internet Adicional ${index + 1} (${addonInfo.name})`, amount: commission });
                    }

                    // --- INICIO LÓGICA MODIFICADA: IP Fija gratis si hay centralita multisede ---
                    if (line.has_ip_fija && ipFijaAddonInfo.value) {
                        const isIncluded = !!line.selected_centralita_id; 
                        
                        // --- INICIO MODIFICACIÓN BENEFICIOS ---
                        const originalIpFijaPrice = parseFloat(ipFijaAddonInfo.value.price) || 0;
                        const benefitIpFija = activeBenefitsMap.value.get(ipFijaAddonInfo.value.id);
                        const priceAfterBenefit = applyBenefit(originalIpFijaPrice, benefitIpFija);
                        
                        const ipFijaPrice = isIncluded ? 0 : priceAfterBenefit; 
                        const description = isIncluded 
                            ? `IP Fija Adicional ${index + 1} (Incluida por Centralita)` 
                            : benefitIpFija 
                                ? `IP Fija Adicional ${index + 1} (Beneficio)` 
                                : `IP Fija Adicional ${index + 1}`;
                        
                        // --- INICIO CORRECCIÓN 2: IP Fija Adicional (Benefit Rule) ---
                        const ipFijaBaseCommission = parseFloat(ipFijaAddonInfo.value.commission) || 0;
                        const ipFijaDecommission = parseFloat(ipFijaAddonInfo.value.decommission) || 0;
                        const ipFijaCommission = ipFijaBaseCommission; // "Ponerla toda"

                        if (benefitIpFija && benefitIpFija.apply_type === 'free' && ipFijaDecommission > 0) {
                            commissionDetails.Ajustes.push({ description: `Ajuste Decomisión (IP Fija Ad. ${index + 1})`, amount: -ipFijaDecommission });
                        }
                        // --- FIN CORRECCIÓN 2 ---

                        price += ipFijaPrice;
                        summaryBreakdown.push({ description: description, price: ipFijaPrice });
                        
                        if (ipFijaCommission > 0) {
                            commissionDetails.Fibra.push({ description: `IP Fija Adicional ${index + 1}`, amount: ipFijaCommission });
                        }
                    }
                    // --- FIN LÓGICA MODIFICADA ---

                    // --- INICIO: AÑADIDO PARA FIBRA ORO ADICIONAL (MODIFICADO) ---
                    if (line.has_fibra_oro && fibraOroAddonInfo.value) {
                        
                        const originalPrice = parseFloat(fibraOroAddonInfo.value.price) || 0;
                        const baseCommission = parseFloat(fibraOroAddonInfo.value.commission) || 0;
                        const decommission = parseFloat(fibraOroAddonInfo.value.decommission) || 0;

                        let itemPrice = originalPrice;
                        let description = `Fibra Oro Adicional ${index + 1}`;
                        let commissionAmount = baseCommission; // <-- Se paga la comisión base por defecto

                        // --- INICIO MODIFICACIÓN FIBRA ORO (Límite 1) ---
                        if (isFibraOroBenefitAvailable) { // Comprobar si el beneficio aún no se ha usado
                            const benefit = activeBenefitsMap.value.get(fibraOroAddonId);
                            itemPrice = applyBenefit(originalPrice, benefit);
                            description = `Fibra Oro Adicional ${index + 1} (Beneficio)`;
                            
                            // --- INICIO MODIFICACIÓN COMISIÓN (Petición Usuario) ---
                            if (benefit && benefit.apply_type === 'free') {
                                // commissionAmount se queda como baseCommission (Positivo)
                                if (decommission > 0) {
                                    // Pero se aplica la decomisión como un ajuste negativo
                                    commissionDetails.Ajustes.push({ description: `Ajuste Decomisión (Fibra Oro Ad.)`, amount: -decommission });
                                }
                            }
                            // --- FIN MODIFICACIÓN COMISIÓN ---

                            isFibraOroBenefitAvailable = false; // ¡MARCAR COMO USADO!
                        }
                        // --- FIN MODIFICACIÓN FIBRA ORO ---
                        
                        price += itemPrice;
                        summaryBreakdown.push({ description: description, price: itemPrice });

                        // Añadir la comisión (positiva) si es mayor que 0
                        if (commissionAmount > 0) {
                            commissionDetails.Fibra.push({ description: `Fibra Oro Adicional ${index + 1}`, amount: commissionAmount });
                        }
                    }
                    // --- FIN: AÑADIDO PARA FIBRA ORO ADICIONAL (MODIFICADO) ---

                    // --- INICIO CÓDIGO NUEVO: Cálculo Centralita Multisede ---
                    if (line.selected_centralita_id) {
                        const centralitaInfo = centralitaAddonOptions.value.find(c => c.id === line.selected_centralita_id);
                        if (centralitaInfo) {
                            // --- INICIO MODIFICACIÓN BENEFICIOS ---
                            const originalPrice = parseFloat(centralitaInfo.pivot.price) || 0;
                            // const benefit = activeBenefitsMap.value.get(centralitaInfo.id); // Centralita no es beneficio
                            const itemPrice = originalPrice; // applyBenefit(originalPrice, benefit);
                            const description = `Centralita Multisede ${index + 1} (${centralitaInfo.name})`;
                            
                            // --- INICIO CORRECCIÓN 3: Centralita Multisede (Centralita Rule) ---
                            const baseCommission = parseFloat(centralitaInfo.commission) || 0;
                            const decommission = parseFloat(centralitaInfo.decommission) || 0;
                            const totalAmount = baseCommission; // "Ponerla toda"
                            
                            if (decommission > 0) { // Aplicar siempre
                                commissionDetails.Ajustes.push({ 
                                    description: `Ajuste Decomisión (${centralitaInfo.name})`, 
                                    amount: -decommission 
                                });
                            }
                            // --- FIN CORRECCIÓN 3 ---
                            
                            price += itemPrice;
                            summaryBreakdown.push({ description: description, price: itemPrice });
                            
                            if (totalAmount > 0) {
                                commissionDetails.Centralita.push({ 
                                    description: `Centralita Multisede ${index + 1} (${centralitaInfo.name})`, 
                                    amount: totalAmount 
                                });
                            }

                            // ... (lógica de extensión auto-incluida para multisede)
                            const centralitaType = centralitaInfo.name.split(' ')[1];
                            if (centralitaType) {
                                const autoExt = props.centralitaExtensions.find(ext => ext.name.includes(centralitaType));
                                if (autoExt) {
                                    const extCommission = parseFloat(autoExt.commission) || 0;
                                    if (extCommission > 0) {
                                        commissionDetails.Centralita.push({ 
                                            description: `1x ${autoExt.name} (Por Multisede ${index + 1})`, 
                                            amount: extCommission 
                                        });
                                    }
                                }
                            }
                        }
                    }
                    // --- FIN CÓDIGO NUEVO ---
                }
            }
        });
        // --- FIN CÓDIGO MODIFICADO ---

        // ===================================================
        // --- LÓGICA DE IP FIJA (Línea Principal) ---
        // ===================================================
        if (form.is_ip_fija_selected && ipFijaAddonInfo.value) {
            const isIncluded = isCentralitaActive.value;
            
            // --- INICIO MODIFICACIÓN BENEFICIOS ---
            const originalPrice = parseFloat(ipFijaAddonInfo.value.price) || 0;
            const benefit = activeBenefitsMap.value.get(ipFijaAddonInfo.value.id);
            const priceAfterBenefit = applyBenefit(originalPrice, benefit);
            
            const itemPrice = isIncluded ? 0 : priceAfterBenefit; 
            const description = isIncluded 
                ? 'IP Fija Principal (Incluida por Centralita)' 
                : benefit 
                    ? 'IP Fija Principal (Beneficio)' 
                    : 'IP Fija Principal';
            
            // --- INICIO CORRECCIÓN 4: IP Fija Principal (Benefit Rule) ---
            const baseCommission = parseFloat(ipFijaAddonInfo.value.commission) || 0;
            const decommission = parseFloat(ipFijaAddonInfo.value.decommission) || 0;
            const commission = baseCommission; // "Ponerla toda"

            if (benefit && benefit.apply_type === 'free' && decommission > 0) {
                commissionDetails.Ajustes.push({ description: `Ajuste Decomisión (IP Fija Pr.)`, amount: -decommission });
            }
            // --- FIN CORRECCIÓN 4 ---

            price += itemPrice;
            summaryBreakdown.push({ description: description, price: itemPrice });
            if (commission > 0) {
                commissionDetails.Fibra.push({ description: 'IP Fija Principal', amount: commission });
            }
        } else if (form.is_ip_fija_selected) {
             console.log("IP Fija principal seleccionada, pero ipFijaAddonInfo es null/undefined.");
        }
        // ===================================================
        // --- FIN LÓGICA IP FIJA PRINCIPAL ---
        // ===================================================


        // --- INICIO MODIFICACIÓN PRECIOS FÚTBOL ---
        selectedTvAddonIds.value.forEach(addonId => {
            // Busca el addon en la lista completa (props.allAddons)
            const addon = props.allAddons.find(a => a.id === addonId);
            if (addon) {
                // Busca el pivot si está en el paquete (para precio/comisión especial del paquete)
                const pivot = tvAddonOptions.value.find(a => a.id === addonId)?.pivot;
                
                // 1. Obtenemos el precio por defecto del pivot o del addon base
                let originalPrice = parseFloat(pivot?.price ?? addon.price) || 0;
                
                // 2. Comprobamos si el paquete es "Base Plus"
                const isPackageBasePlus = selectedPackage.value?.name === 'Base Plus';

                // 3. Si es "Base Plus", aplicamos los precios especiales
                if (isPackageBasePlus) {
                    if (addon.name === 'Futbol') {
                        originalPrice = 38.40; // <-- TU PRECIO ESPECIAL
                    } else if (addon.name === 'Futbol y más deportes') {
                        originalPrice = 44.05; // <-- TU PRECIO ESPECIAL
                    }
                    // Si es "Base Plus" pero es otro addon (ej. Bares), usará el precio original ya cargado.
                }
                // Si no es "Base Plus", usará el precio original ya cargado.

                // --- FIN DE LA MODIFICACIÓN ---
                
                const benefit = activeBenefitsMap.value.get(addon.id);
                const itemPrice = applyBenefit(originalPrice, benefit);
                const description = benefit ? `${addon.name} (Beneficio)` : `TV: ${addon.name}`;
                
                // --- INICIO CORRECCIÓN 5: Televisión (Benefit Rule) ---
                const baseCommission = parseFloat(pivot?.included_line_commission ?? addon.commission) || 0;
                const decommission = parseFloat(addon.decommission) || 0;
                const commission = baseCommission; // "Ponerla toda"

                if (benefit && benefit.apply_type === 'free' && decommission > 0) {
                    commissionDetails.Ajustes.push({ 
                        description: `Ajuste Decomisión (${addon.name})`, 
                        amount: -decommission 
                    });
                }
                // --- FIN CORRECCIÓN 5 ---
                
                price += itemPrice;
                if (itemPrice > 0 || (benefit && benefit.apply_type === 'free')) { 
                    summaryBreakdown.push({ description: description, price: itemPrice });
                }
                if (commission > 0) {
                    commissionDetails.Televisión.push({ description: addon.name, amount: commission });
                }
            }
        });
        // --- FIN MODIFICACIÓN PRECIOS FÚTBOL ---


        if (includedCentralita.value) {
            
            // --- INICIO CORRECCIÓN 6: Centralita Incluida (Centralita Rule) ---
            const baseCommission = parseFloat(includedCentralita.value.pivot.included_line_commission) || 0;
            const decommission = parseFloat(includedCentralita.value.pivot.included_line_decommission) || 0; 
            const totalAmount = baseCommission; // "Ponerla toda"
            
            if (decommission > 0) { // Aplicar siempre
                 commissionDetails.Ajustes.push({ 
                    description: `Ajuste Decomisión (Cent. Incluida)`, 
                    amount: -decommission 
                });
            }
            // --- FIN CORRECCIÓN 6 ---
            
            if (totalAmount > 0) {
                commissionDetails.Centralita.push({ 
                    description: `Centralita Incluida (${includedCentralita.value.name})`, 
                    amount: totalAmount
                });
            }

        } else if (selectedCentralitaId.value) {
            const selectedCentralita = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
            if (selectedCentralita) {
                // --- INICIO MODIFICACIÓN BENEFICIOS ---
                const originalPrice = parseFloat(selectedCentralita.pivot.price) || 0;
                // const benefit = activeBenefitsMap.value.get(selectedCentralita.id); // No es beneficio
                const itemPrice = originalPrice; // applyBenefit(originalPrice, benefit);
                const description = `Centralita: ${selectedCentralita.name}`;
                
                // --- INICIO CORRECCIÓN 7: Centralita Contratada (Centralita Rule) ---
                const baseCommission = parseFloat(selectedCentralita.commission) || 0;
                const decommission = parseFloat(selectedCentralita.decommission) || 0;
                const totalAmount = baseCommission; // "Ponerla toda"
                
                if (decommission > 0) { // Aplicar siempre
                    commissionDetails.Ajustes.push({ 
                        description: `Ajuste Decomisión (${selectedCentralita.name})`, 
                        amount: -decommission 
                    });
                }
                // --- FIN CORRECCIÓN 7 ---

                price += itemPrice;
                summaryBreakdown.push({ description: description, price: itemPrice });
                if (totalAmount > 0) {
                    commissionDetails.Centralita.push({ description: `Centralita Contratada (${selectedCentralita.name})`, amount: totalAmount  });
                }
            }
        }

        if (isCentralitaActive.value && operadoraAutomaticaInfo.value) {
            const commission = parseFloat(operadoraAutomaticaInfo.value.pivot.included_line_commission) || 0;
            const decommission = parseFloat(operadoraAutomaticaInfo.value.decommission) || 0; // Asumimos que existe

            if (operadoraAutomaticaInfo.value.pivot.is_included) {
                
                // --- INICIO CORRECCIÓN 8a: Operadora Incluida (Centralita Rule) ---
                const commissionFinal = commission;
                if (decommission > 0) { // Aplicar siempre
                     commissionDetails.Ajustes.push({ description: `Ajuste Decomisión (Op. Automática Incl.)`, amount: -decommission });
                }
                if (commissionFinal > 0) {
                    commissionDetails.Centralita.push({ description: 'Operadora Automática (Incluida)', amount: commissionFinal });
                }
                // --- FIN CORRECCIÓN 8a ---

            } else if (isOperadoraAutomaticaSelected.value) {
                 // --- INICIO MODIFICACIÓN BENEFICIOS ---
                const originalPrice = parseFloat(operadoraAutomaticaInfo.value.pivot.price) || 0;
                // const benefit = activeBenefitsMap.value.get(operadoraAutomaticaInfo.value.id); // No es beneficio
                const itemPrice = originalPrice; // applyBenefit(originalPrice, benefit);
                const description = 'Operadora Automática';
                
                // --- INICIO CORRECCIÓN 8b: Operadora Contratada (Centralita Rule) ---
                const commissionFinal = commission; // 'commission' es la baseCommission
                if (decommission > 0) { // Aplicar siempre
                    commissionDetails.Ajustes.push({ description: `Ajuste Decomisión (Op. Automática)`, amount: -decommission });
                }
                // --- FIN CORRECCIÓN 8b ---

                price += itemPrice;
                summaryBreakdown.push({ description: description, price: itemPrice });
                if (commissionFinal > 0) {
                    commissionDetails.Centralita.push({ description: 'Operadora Automática (Contratada)', amount: commissionFinal });
                }
            }
        }
        if (isCentralitaActive.value) {
            includedCentralitaExtensions.value.forEach(ext => {
                const commissionPerUnit = parseFloat(ext.pivot.included_line_commission) || 0;
                const quantity = ext.pivot.included_quantity || 0;
                if (quantity > 0) {
                    commissionDetails.Centralita.push({ description: `${quantity}x ${ext.name} (Incluidas)`, amount: quantity * commissionPerUnit });
                }
            });

            if (autoIncludedExtension.value && !includedCentralita.value) { 
                const commission = parseFloat(autoIncludedExtension.value.commission) || 0;
                commissionDetails.Centralita.push({ description: `1x ${autoIncludedExtension.value.name} (Por Centralita)`, amount: commission });
            }

            for (const addonId in centralitaExtensionQuantities.value) {
                const quantity = centralitaExtensionQuantities.value[addonId];
                if (quantity > 0) {
                    const addonInfo = props.centralitaExtensions.find(ext => ext.id == addonId);
                    if (addonInfo) {
                        // --- INICIO MODIFICACIÓN BENEFICIOS ---
                        const originalPrice = parseFloat(addonInfo.price) || 0;
                        // const benefit = activeBenefitsMap.value.get(addonInfo.id); // No es beneficio
                        const finalUnitPrice = originalPrice; // applyBenefit(originalPrice, benefit);
                        const itemPrice = quantity * finalUnitPrice;
                        const description = `${quantity}x ${addonInfo.name} (Adicional)`;

                        // --- INICIO CORRECCIÓN 9: Extensiones (Centralita Rule) ---
                        const baseCommission = parseFloat(addonInfo.commission) || 0;
                        const decommission = parseFloat(addonInfo.decommission) || 0;
                        const commission = baseCommission; // "Ponerla toda"

                        if (decommission > 0) { // Aplicar siempre
                            commissionDetails.Ajustes.push({ description: `Ajuste Decomisión (${quantity}x ${addonInfo.name})`, amount: -(quantity * decommission) });
                        }
                        // --- FIN CORRECCIÓN 9 ---

                        price += itemPrice;
                        summaryBreakdown.push({ description: description, price: itemPrice });
                        if (commission > 0) {
                            commissionDetails.Centralita.push({ description: `${quantity}x ${addonInfo.name} (Adicional)`, amount: quantity * commission });
                        }
                    }
                }
            }
        }
        
        // --- INICIO: LÓGICA DE DDI (AÑADIDO) ---
        // Asumimos que form.ddi_quantity está disponible en el formulario
        const ddiQuantity = form.ddi_quantity || 0; 
        if (isCentralitaActive.value && ddiQuantity > 0 && ddiAddonInfo.value) {
            
            // Regla: Gratis para 'Negocio 10' y 'Negocio 20'. 1€ por unidad para el resto.
            const isFreePackage = ['Negocio 10', 'Negocio 20'].includes(selectedPackage.value.name);
            
            let itemPrice = 0;
            let commissionAmount = 0;
            let description = '';

            // Utilizamos los valores del Addon de referencia para la comisión
            const baseCommission = parseFloat(ddiAddonInfo.value.commission) || 0;
            
            if (isFreePackage) {
                // Para Negocio 10/20, DDI es gratuito (0€)
                itemPrice = 0; 
                description = `${ddiQuantity}x DDI (Gratuito por Paquete)`;
                commissionAmount = baseCommission; 
            } else {
                // Para el resto, 1€ por DDI
                const ddiUnitPrice = 1.00;
                itemPrice = ddiQuantity * ddiUnitPrice;
                description = `${ddiQuantity}x DDI Adicional`;
                commissionAmount = baseCommission;
            }

            price += itemPrice;
            // Se muestra en el resumen si tiene coste o si es gratuito por el paquete
            if (itemPrice > 0 || isFreePackage) { 
                summaryBreakdown.push({ description: description, price: itemPrice });
            }
            
            if (commissionAmount > 0) {
                 // Multiplicar la comisión base por la cantidad
                 const totalCommission = ddiQuantity * commissionAmount;
                 commissionDetails.Centralita.push({ description: `${ddiQuantity}x DDI`, amount: totalCommission });
            }
        }
        // --- FIN: LÓGICA DE DDI (AÑADIDO) ---


        const appliedO2oList = [];
        let totalTerminalFee = 0;
        let totalInitialPayment = 0;
        let extraLinesCost = 0;

        if (mobileAddonInfo.value) {
            // --- INICIO CORRECCIÓN DE BUG ---
            // 'promoLimit' y 'standardPrice' DEBEN leerse del PIVOT del paquete
            const promoLimit = mobileAddonInfo.value.pivot?.line_limit ?? 0;
            const promoPrice = 8.22; // TODO: Esto debería venir también del pivot si es variable
            const standardPrice = mobileAddonInfo.value.pivot?.price ?? 0; // Usar el precio del PIVOT
            
            const includedCommission = parseFloat(mobileAddonInfo.value.pivot?.included_line_commission) || 0;
            // Usar la comisión del pivot o, si no existe, la comisión base del addon
            const additionalCommission = parseFloat(mobileAddonInfo.value.pivot?.additional_line_commission || mobileAddonInfo.value.commission) || 0;
            // --- FIN CORRECCIÓN DE BUG ---

            let extraLinesCounter = 0;

            // --- INICIO MODIFICACIÓN BENEFICIOS (Lógica para LA Extra) ---
            const freeLineBenefit = activeBenefitsMap.value.get(mobileAddonInfo.value.id);
            // --- TU CAMBIO (|| 1) ---
            const freeExtraLinesQty = freeLineBenefit ? (freeLineBenefit.apply_data?.quantity || 1) : 0;
            // --- FIN MODIFICACIÓN BENEFICIOS ---

            lines.value.forEach((line, index) => {
                const lineName = index === 0 ? 'Línea Principal' : `Línea Adicional ${index+1}`;
                
                // --- INICIO MODIFICACIÓN LA EXTRA (Flag) ---
                // Este flag nos dirá si estamos en la línea gratis
                let isFreeExtraLineBenefitActive = false;
                // --- FIN MODIFICACIÓN LA EXTRA (Flag) ---

                let initialCost = parseFloat(line.initial_cost || 0);
                let monthlyCost = parseFloat(line.monthly_cost || 0);

                totalTerminalFee += monthlyCost;
                totalInitialPayment += initialCost;

                if (line.is_extra) {
                    extraLinesCounter++;

                    // --- INICIO MODIFICACIÓN BENEFICIOS (Aplicar Lógica LA Extra) ---
                    let itemPrice;
                    let description;
                    let commissionAmount = additionalCommission; 

                    if (extraLinesCounter <= freeExtraLinesQty) {
                        itemPrice = 0; // El servicio de la línea es gratis
                        description = `Línea Móvil Adicional ${extraLinesCounter} (Beneficio)`;
                        commissionAmount = 0; // El servicio gratis no da comisión de línea
                        
                        // --- INICIO MODIFICACIÓN LA EXTRA (Flag) ---
                        isFreeExtraLineBenefitActive = true; // ¡Activamos el flag!
                        // --- FIN MODIFICACIÓN LA EXTRA (Flag) ---

                    } else {
                        const paidExtraLinesCounter = extraLinesCounter - freeExtraLinesQty;
                        
                        // --- INICIO CORRECCIÓN DE BUG ---
                        // Asegurarse de que se usa el standardPrice leído del pivot
                        itemPrice = (promoLimit > 0 && paidExtraLinesCounter <= promoLimit) ? promoPrice : parseFloat(standardPrice);
                        // --- FIN CORRECCIÓN DE BUG ---
                        description = `Línea Móvil Adicional ${extraLinesCounter}`;
                        // commissionAmount ya es 'additionalCommission'
                    }
                    // --- FIN MODIFICACIÓN BENEFICIOS ---

                    extraLinesCost += itemPrice;
                    summaryBreakdown.push({ description: description, price: itemPrice });
                    commissionDetails["Líneas Móviles"].push({ description: `Comisión ${lineName}`, amount: commissionAmount });
                } else {
                    commissionDetails["Líneas Móviles"].push({ description: `Comisión ${lineName}`, amount: includedCommission });
                }

                // --- INICIO MODIFICACIÓN LA EXTRA (No pagar Portabilidad) ---
                // Calculamos la comisión de portabilidad por separado
                const exceptions = props.portabilityExceptions || [];
                const isException = exceptions.includes(line.source_operator);
                const portabilityCommissionAmount = isException ? 0 : (parseFloat(props.portabilityCommission) || 0);

                if (line.is_portability) {
                    if (isFreeExtraLineBenefitActive) {
                        // Si la línea es gratis por el beneficio, no se paga comisión de portabilidad.
                        // El usuario pidió dejar el código original comentado.
                        /*
                        commissionDetails["Líneas Móviles"].push({
                            description: `Portabilidad ${lineName}`,
                            amount: portabilityCommissionAmount
                        });
                        */
                    } else {
                        // Si NO es la línea del beneficio, se paga la portabilidad normal.
                        commissionDetails["Líneas Móviles"].push({
                            description: `Portabilidad ${lineName}`,
                            amount: portabilityCommissionAmount
                        });
                    }
                }
                // --- FIN MODIFICACIÓN LA EXTRA ---
  if (line.terminal_pivot && line.selected_duration) {
                    const terminalTotalPrice = (parseFloat(line.original_initial_cost) || 0) + (parseFloat(line.original_monthly_cost || 0) * parseInt(line.selected_duration, 10));
                    
                    let terminalCommission = 0;
                    if (terminalTotalPrice < 40) terminalCommission = 15;
                    else if (terminalTotalPrice >= 40 && terminalTotalPrice < 350) terminalCommission = 45;
                    else if (terminalTotalPrice >= 350) terminalCommission = 75;
                    commissionDetails.Terminales.push({ description: `Terminal ${lineName}`, amount: terminalCommission });
                }

                if (line.o2o_discount_id) {
                    const o2o = availableO2oDiscounts.value.find(d => d.id === line.o2o_discount_id);
                    if (o2o) {
                        const monthlyValue = parseFloat(o2o.total_discount_amount) / parseFloat(o2o.duration_months);
                        price -= monthlyValue;
                        appliedO2oList.push({ line: index === 0 ? 'Línea Principal' : `Línea ${index + 1}`, name: o2o.name, value: monthlyValue.toFixed(2) });
                        summaryBreakdown.push({ description: ` O2O (${o2o.name})`, price: -monthlyValue });
                        const packageO2oPivot = selectedPackage.value?.o2o_discounts?.find(d => d.id === o2o.id)?.pivot;
                        if (packageO2oPivot && packageO2oPivot.dho_payment) {
                             commissionDetails.Ajustes.push({ description: `Ajuste DHO ${lineName}`, amount: -parseFloat(packageO2oPivot.dho_payment) });
                        }
                    }
                }
            });
        }

        price += totalTerminalFee;
        
        if(totalTerminalFee !== 0) { 
            summaryBreakdown.push({ description: 'Cuotas mensuales de Terminales', price: totalTerminalFee });
        }
        
        price += extraLinesCost;

        // --- INICIO MODIFICACIÓN BENEFICIOS (Cálculo de otros addons de servicio) ---
        // Esta sección calcula el precio de addons como MS365, Disney+, etc.
        // que no se seleccionan en ninguna otra parte del formulario.
        
        if (props.allAddons && selectedDigitalAddonIds.value) { // <--- CAMBIADO
            const serviceTypes = ['service', 'software']; // Tipos de addons que son "beneficios"
            
            selectedDigitalAddonIds.value.forEach(addonId => { // <--- CAMBIADO
                const addonInfo = props.allAddons.find(a => a.id === addonId);
                
                // Solo calcula los que NO son de TV (porque TV ya se calculó arriba)
                if (addonInfo && serviceTypes.includes(addonInfo.type)) {
                    const originalPrice = parseFloat(addonInfo.price) || 0;
                    const benefit = activeBenefitsMap.value.get(addonInfo.id);
                    const itemPrice = applyBenefit(originalPrice, benefit);
                    const description = benefit 
                        ? `${addonInfo.name} (Beneficio)` 
                        : addonInfo.name;
                    
                    // --- INICIO CORRECCIÓN 10: Servicios (Benefit Rule) ---
                    const baseCommission = parseFloat(addonInfo.commission) || 0;
                    const decommission = parseFloat(addonInfo.decommission) || 0;
                    const commission = baseCommission; // "Ponerla toda"

                    if (benefit && benefit.apply_type === 'percentage_discount' && decommission > 0) {
                        commissionDetails.Ajustes.push({ description: `Ajuste Decomisión (${addonInfo.name})`, amount: -decommission });
                    }
                    // --- FIN CORRECCIÓN 10 ---

                    price += itemPrice;
                    summaryBreakdown.push({ description: description, price: itemPrice });
                    if(commission > 0) {
                        commissionDetails.Servicios.push({ description: addonInfo.name, amount: commission });
                    }
                }
            });
        }
        // --- FIN MODIFICACIÓN BENEFICIOS ---

        Object.keys(commissionDetails).forEach(key => {
            if (commissionDetails[key].length === 0) {
                delete commissionDetails[key];
            }
        });

        const totalCommission = Object.values(commissionDetails).flat().reduce((acc, item) => acc + item.amount, 0);
        const currentUser = props.auth.user;
        let teamCommission = 0;
        let userCommission = 0;

        // --- INICIO DE LA MODIFICACIÓN DE CÁLCULO DE PORCENTAJES ---
        if (currentUser.role === 'admin') {
            userCommission = totalCommission;
            teamCommission = totalCommission;
            // Admin ve 100% (bruto), no es necesario hacer nada al desglose.
        }
        else if (currentUser.team) {
            const teamPercentage = parseFloat(currentUser.team.commission_percentage || 0) / 100;
            teamCommission = totalCommission * teamPercentage;

            if (currentUser.role === 'user') {
                const userPercentage = parseFloat(currentUser.commission_percentage || 0) / 100;
                userCommission = teamCommission * userPercentage;

                // Es un 'user', aplicar su porcentaje final (team * user) al desglose
                const finalMultiplier = teamPercentage * userPercentage;
                Object.keys(commissionDetails).forEach(category => {
                    commissionDetails[category].forEach(item => {
                        item.amount = item.amount * finalMultiplier;
                    });
                });

            } else { // team_lead o jefe de ventas
                userCommission = teamCommission;

                // Es 'team_lead', aplicar el porcentaje del equipo al desgLOSSe
                const finalMultiplier = teamPercentage;
                Object.keys(commissionDetails).forEach(category => {
                    commissionDetails[category].forEach(item => {
                        item.amount = item.amount * finalMultiplier;
                    });
                });
            }
        }
        else { // user sin equipo
            const userPercentage = parseFloat(currentUser.commission_percentage || 0) / 100;
            userCommission = totalCommission * userPercentage;
            teamCommission = 0; 
            
            // Es 'user' sin equipo, aplicar su porcentaje al desglose
            const finalMultiplier = userPercentage;
            Object.keys(commissionDetails).forEach(category => {
                commissionDetails[category].forEach(item => {
                    item.amount = item.amount * finalMultiplier;
                });
            });
        }
        // --- FIN DE LA MODIFICACIÓN DE CÁLCULO DE PORCENTAJES ---

        return {
            basePrice: basePrice.toFixed(2),
            finalPrice: Math.max(0, price).toFixed(2),
            appliedO2oList: appliedO2oList,
            totalTerminalFee: totalTerminalFee.toFixed(2),
            totalInitialPayment: totalInitialPayment.toFixed(2),
            extraLinesCost: extraLinesCost.toFixed(2),
            totalCommission: totalCommission.toFixed(2),
            teamCommission: teamCommission.toFixed(2),
            userCommission: userCommission.toFixed(2),
            commissionDetails,
            summaryBreakdown,
        };
    });

    // --- INICIO: EXPORTAR INFOS ADICIONALES ---
    return {
        calculationSummary,
        // Exponemos la info para el v-if del formulario
        ipFijaAddonInfo, 
        fibraOroAddonInfo, 
        ddiAddonInfo, // <--- EXPORTADO
    };
    // --- FIN: EXPORTAR INFOS ADICIONALES ---
}