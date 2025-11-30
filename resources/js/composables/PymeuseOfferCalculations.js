import { computed } from 'vue';

export function usePymeOfferCalculations(packages, mobileLines, tariffType, discounts = []) {

    // 1. Obtener precio BASE del paquete (sin dtos)
    const getPackagePrice = (id) => {
        if (!packages) return 0;
        const pkg = packages.find(p => p.id === id);
        return pkg ? parseFloat(pkg.base_price) : 0;
    };

    // 2. Calcular PRECIO CLIENTE (Descuento O2O directo sobre la cuota)
    const calculateLinePrice = (line) => {
        const basePrice = getPackagePrice(line.package_id);
        if (basePrice === 0) return 0;

        let finalPrice = basePrice;

        // Si tiene descuento O2O, aplicamos el % de descuento a la cuota mensual
        if (line.o2o_discount_id && discounts.length > 0) {
            const discount = discounts.find(d => d.id === line.o2o_discount_id);
            if (discount && discount.percentage > 0) {
                // Aplicar descuento porcentual sobre la cuota base
                finalPrice = basePrice * (1 - (discount.percentage / 100));
            }
        }

        return finalPrice > 0 ? finalPrice : 0;
    };

    // 3. Calcular COMISIÓN DE LÍNEA (Aplicando la MERMA O2O y el BONUS de Terminal)
    const calculateLineCommission = (line) => {
        if (!packages) return 0;
        const pkg = packages.find(p => p.id === line.package_id);
        if (!pkg) return 0;

        let commission = 0;

        // A. Comisión Base por Tarifa (OPTIMA/PERSONALIZADA)
        if (tariffType.value === 'OPTIMA') {
            commission += parseFloat(pkg.commission_optima || 0);
        } else {
            commission += parseFloat(pkg.commission_custom || 0);
        }

        // B. Plus por Portabilidad
        if (line.type === 'portabilidad') {
            commission += parseFloat(pkg.commission_porta || 0); 
        }

        // C. Bonus por Permanencia (CP)
        const cp = parseInt(line.cp_duration || 0);
        if (cp === 24) {
            commission += parseFloat(pkg.bonus_cp_24 || 0);
        } else if (cp === 36) {
            commission += parseFloat(pkg.bonus_cp_36 || 0);
        }

        // --- D. LÓGICA Y BONUS POR TERMINAL (VAP o SUBVENCIONADO) ---
        if (line.has_terminal === 'si') {
             // 1. APLICAR BONUS DE COMISIÓN POR TERMINAL (PARA VAP Y SUBVENCIONADO)
             // Se aplica si hay permanencia de terminal (24/36)
             if (cp === 24) {
                 commission += parseFloat(pkg.bonus_cp_24_terminal || 0);
             } else if (cp === 36) {
                 commission += parseFloat(pkg.bonus_cp_36_terminal || 0);
             }

             // 2. APLICAR AJUSTE ESPECÍFICO DE SUBVENCIÓN
             if (line.terminal_type === 'SUBVENCIONADO') {
                 // LÓGICA SUBVENCIONADO (SUMAR SUBVENCIÓN y RESTAR CESIÓN)
                 const subsidy = parseFloat(line.sub_subsidy_price || 0);
                 const cession = parseFloat(line.sub_cession_price || 0);
                 
                 // Comisión = Comisión + Subvención - Precio de Cesión
                 // NOTA: El bonus de terminal (punto 1) ya se ha sumado arriba.
                 commission = commission + subsidy - cession;
             }
             // Para VAP no hay ajuste adicional en la comisión, solo el bonus ya sumado en el punto 1.
        }

        // --- E. CÁLCULO DE LA MERMA (PENALIZACIÓN) O2O ---
        if (line.o2o_discount_id && discounts.length > 0) {
            const discount = discounts.find(d => d.id === line.o2o_discount_id);
            
            if (discount && cp > 0) {
                let penaltyPercentage = 0;
                let effectiveCp = cp;
                
                // 1. Buscamos el % de penalización en la tabla según la permanencia de la línea
                if (cp === 12) {
                    penaltyPercentage = parseFloat(discount.penalty_12m || 0);
                    effectiveCp = 24; // <-- Aplicar base de cálculo de 24 meses SIEMPRE que CP sea 12
                } else if (cp === 24) {
                    penaltyPercentage = parseFloat(discount.penalty_24m || 0);
                } else if (cp === 36) {
                    penaltyPercentage = parseFloat(discount.penalty_36m || 0);
                }

                // 2. Si hay penalización definida en la tabla, calculamos el importe a quitar
                if (penaltyPercentage > 0) {
                    const basePrice = getPackagePrice(line.package_id);
                    const finalPrice = calculateLinePrice(line);

                    // BASE DE CÁLCULO: (Cuota Base - Cuota Final) x Meses CP Efectivos
                    const monthlyDiscount = basePrice - finalPrice;
                    
                    const totalDiscountValue = monthlyDiscount * effectiveCp; // Se multiplica por 24 si cp=12
                    
                    // Importe Merma: Aplicamos el % de penalización a ese valor total de descuento.
                    const penaltyAmount = totalDiscountValue * (penaltyPercentage / 100);

                    // 3. Restamos el importe de la merma a la comisión de la línea
                    commission = commission - penaltyAmount;
                }
            }
        }

        return commission > 0 ? commission : 0;
    };

    // Computed para el total general de comisiones sumando las líneas
    const totalCommission = computed(() => {
        return mobileLines.value.reduce((sum, line) => {
            const lineComm = calculateLineCommission(line);
            const qty = parseInt(line.quantity) || 0;
            return sum + (lineComm * qty);
        }, 0);
    });

    return {
        getPackagePrice,
        calculateLinePrice,
        calculateLineCommission,
        totalCommission
    };
}