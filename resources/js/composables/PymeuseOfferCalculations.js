import { computed, unref } from 'vue'; // Importamos unref para seguridad

export function usePymeOfferCalculations(packages, mobileLines, fixedLines, tariffType, discounts, addonsData = {}) {
    // addonsData: { mobileAddons, fiberFeatures, extensions, extensionsQty, features, selectedFeatures }

    // --- HELPER: Obtener Paquete ---
    const getPackage = (id) => {
        const list = Array.isArray(packages) ? packages : (packages.value || []);
        return list.find(p => p.id === id);
    };

    const getPackagePrice = (id) => {
        const pkg = getPackage(id);
        return pkg ? parseFloat(pkg.base_price) : 0;
    };

    // --- 1. CALCULAR PRECIO LÍNEA MÓVIL (Con O2O + Terminal + MFO/Agente) ---
    const calculateMobileLinePrice = (line) => {
        // A. Precio Base
        let price = getPackagePrice(line.package_id);
        if (price === 0) return 0;

        let debugMsg = `[DEBUG Precio Línea ${line.id}] Base: ${price}`;

        // B. Descuento O2O (Aplicado sobre la cuota base)
        const discountList = Array.isArray(discounts) ? discounts : (discounts?.value || []);
        if (line.o2o_discount_id) {
            const discount = discountList.find(d => d.id === line.o2o_discount_id);
            if (discount && discount.percentage > 0) {
                const discountAmount = price * (parseFloat(discount.percentage) / 100);
                price = price - discountAmount;
                debugMsg += ` | Tras Dto O2O (-${discountAmount}): ${price}`;
            }
        }

        // C. Terminal VAP (Cuota mensual)
        if (line.has_terminal === 'si' && line.terminal_type === 'VAP') {
            const vapCost = parseFloat(line.vap_monthly_payment || 0);
            price += vapCost;
            debugMsg += ` | +VAP: ${vapCost}`;
        }

        // D. Addons Móviles (MFO y Agente) - AQUÍ ESTÁ EL DEBUG CLAVE
        // Usamos unref() por si addonsData.mobileAddons es una ref
        const mobAddons = unref(addonsData.mobileAddons) || [];
        
        // DEBUG: Descomentado para ver la lista de addons disponibles
        console.log("DEBUG: Listado Addons Móviles disponibles:", mobAddons);
        console.log(`DEBUG: Línea ${line.id} - Has MFO: ${line.has_mfo}, Has Agente: ${line.has_agente}`);

        if (line.has_mfo) {
            // Buscamos por nombre exacto (asegúrate que en BBDD es 'MFO')
            const mfo = mobAddons.find(a => a.name === 'MFO');
            if (mfo) {
                const mfoPrice = parseFloat(mfo.price || 0);
                price += mfoPrice;
                debugMsg += ` | +MFO: ${mfoPrice}€`;
            } else {
                console.warn("⚠️ MFO seleccionado pero no encontrado en la lista. Nombres disponibles:", mobAddons.map(a => a.name));
            }
        }

        if (line.has_agente) {
            // Buscamos por nombre exacto
            const agente = mobAddons.find(a => a.name === 'Agente Centralita');
            if (agente) {
                const agentePrice = parseFloat(agente.price || 0);
                price += agentePrice;
                debugMsg += ` | +Agente: ${agentePrice}€`;
            } else {
                console.warn("⚠️ Agente seleccionado pero no encontrado en la lista. Nombres disponibles:", mobAddons.map(a => a.name));
            }
        }

        // DEBUG: Ver el desglose final
        console.log(debugMsg + ` = TOTAL: ${price}`);

        return price > 0 ? price : 0;
    };

    // --- 2. CALCULAR COMISIÓN LÍNEA MÓVIL (Con Merma O2O + Addons) ---
    const calculateMobileLineCommission = (line) => {
        const pkg = getPackage(line.package_id);
        if (!pkg) return 0;

        let comm = 0;

        // A. Comisión Base (Tarifa)
        const type = (tariffType.value || tariffType) === 'OPTIMA' ? 'commission_optima' : 'commission_custom';
        comm += parseFloat(pkg[type] || 0);

        // B. Portabilidad
        if (line.type === 'portabilidad') {
            comm += parseFloat(pkg.commission_porta || 0);
        }

        // C. Permanencia (CP)
        const cp = parseInt(line.cp_duration || 0);
        if (cp === 24) comm += parseFloat(pkg.bonus_cp_24 || 0);
        else if (cp === 36) comm += parseFloat(pkg.bonus_cp_36 || 0);

        // D. Terminal (Bonus y Ajuste Subvención)
        if (line.has_terminal === 'si') {
            if (cp === 24) comm += parseFloat(pkg.bonus_cp_24_terminal || 0);
            else if (cp === 36) comm += parseFloat(pkg.bonus_cp_36_terminal || 0);

            if (line.terminal_type === 'SUBVENCIONADO') {
                const subsidy = parseFloat(line.sub_subsidy_price || 0);
                const cession = parseFloat(line.sub_cession_price || 0);
                comm = comm + subsidy - cession;
            }
        }

        // E. MERMA (PENALIZACIÓN) O2O
        const discountList = Array.isArray(discounts) ? discounts : (discounts?.value || []);
        if (line.o2o_discount_id) {
            const discount = discountList.find(d => d.id === line.o2o_discount_id);
            if (discount && cp > 0) {
                let penaltyPercentage = 0;
                let effectiveCp = cp;

                if (cp === 12) { 
                    penaltyPercentage = parseFloat(discount.penalty_12m || 0); 
                    effectiveCp = 24; 
                } else if (cp === 24) { 
                    penaltyPercentage = parseFloat(discount.penalty_24m || 0); 
                } else if (cp === 36) { 
                    penaltyPercentage = parseFloat(discount.penalty_36m || 0); 
                }

                if (penaltyPercentage > 0) {
                    const basePrice = parseFloat(pkg.base_price);
                    let discountedPrice = basePrice;
                    if (discount.percentage > 0) {
                        discountedPrice = basePrice * (1 - (parseFloat(discount.percentage) / 100));
                    }
                    
                    const monthlySavings = basePrice - discountedPrice;
                    const totalSavings = monthlySavings * effectiveCp;
                    const penaltyAmount = totalSavings * (penaltyPercentage / 100);
                    
                    comm -= penaltyAmount;
                }
            }
        }

        // F. Comisiones de Addons (MFO, Agente) - DEBUG AQUÍ TAMBIÉN
        const mobAddons = unref(addonsData.mobileAddons) || [];
        
        if (line.has_mfo) {
            const mfo = mobAddons.find(a => a.name === 'MFO');
            if (mfo) {
                comm += parseFloat(mfo.commission || 0);
                console.log(`DEBUG Comision: Sumando comisión MFO (${mfo.commission})`);
            }
        }
        if (line.has_agente) {
            const agente = mobAddons.find(a => a.name === 'Agente Centralita');
            if (agente) {
                comm += parseFloat(agente.commission || 0);
                console.log(`DEBUG Comision: Sumando comisión Agente (${agente.commission})`);
            }
        }

        return comm > 0 ? comm : 0;
    };

    // --- 3. CALCULAR PRECIO LÍNEA FIJA (Con IP/Oro) ---
    const calculateFixedLinePrice = (line) => {
        let price = getPackagePrice(line.package_id);
        if (price === 0) return 0;

        // Descuento manual
        if (line.discount > 0) {
            price = price * (1 - line.discount / 100);
        }

        // Addons Fibra
        const fiberList = unref(addonsData.fiberFeatures) || [];
        if (line.has_ip_fija) {
            const ip = fiberList.find(f => f.name === 'IP Fija');
            if (ip) price += parseFloat(ip.price || 0);
        }
        if (line.has_fibra_oro) {
            const oro = fiberList.find(f => f.name === 'Fibra Oro');
            if (oro) price += parseFloat(oro.price || 0);
        }

        return price * (line.quantity || 1);
    };

    // --- 4. CALCULAR COMISIÓN LÍNEA FIJA ---
    const calculateFixedLineCommission = (line) => {
        const listPackages = Array.isArray(packages) ? packages : (packages.value || []);
        const pkg = listPackages.find(p => p.id === line.package_id);
        if (!pkg) return 0;

        let comm = 0;
        const type = (tariffType.value || tariffType) === 'OPTIMA' ? 'commission_optima' : 'commission_custom';
        comm += parseFloat(pkg[type] || 0);

        const fiberList = unref(addonsData.fiberFeatures) || [];
        if (line.has_ip_fija) {
            const ip = fiberList.find(f => f.name === 'IP Fija');
            if (ip) comm += parseFloat(ip.commission || 0);
        }
        if (line.has_fibra_oro) {
            const oro = fiberList.find(f => f.name === 'Fibra Oro');
            if (oro) comm += parseFloat(oro.commission || 0);
        }

        return comm * (line.quantity || 1);
    };

    // --- 5. COMISIONES EXTRAS ---
    const calculateExtensionsCommission = () => {
        let total = 0;
        const qtyMap = unref(addonsData.extensionsQty) || {};
        const list = unref(addonsData.extensions) || [];
        
        for (const [id, qty] of Object.entries(qtyMap)) {
            if (qty > 0) {
                const ext = list.find(e => e.id == id);
                if (ext) total += parseFloat(ext.commission || 0) * qty;
            }
        }
        return total;
    };

    const calculateFeaturesCommission = () => {
        let total = 0;
        const selectedMap = unref(addonsData.selectedFeatures) || {};
        const list = unref(addonsData.features) || [];

        for (const [id, selected] of Object.entries(selectedMap)) {
            if (selected) {
                const feat = list.find(f => f.id == id);
                if (feat) total += parseFloat(feat.commission || 0);
            }
        }
        return total;
    };

    // --- 6. COMISIÓN TOTAL GLOBAL ---
    const totalCommission = computed(() => {
        let total = 0;
        
        // Sumar Móviles
        const mLines = mobileLines.value || mobileLines;
        mLines.forEach(line => {
            total += calculateMobileLineCommission(line) * (line.quantity || 1);
        });

        // Sumar Fijos
        const fLines = fixedLines.value || fixedLines;
        fLines.forEach(line => {
            total += calculateFixedLineCommission(line);
        });

        // Sumar Extras
        total += calculateExtensionsCommission();
        total += calculateFeaturesCommission();

        return total;
    });

    return {
        getPackagePrice,
        calculateMobileLinePrice,
        calculateMobileLineCommission,
        calculateFixedLinePrice,
        calculateFixedLineCommission,
        totalCommission
    };
}