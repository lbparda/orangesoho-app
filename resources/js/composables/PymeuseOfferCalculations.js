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
                finalPrice = basePrice * (1 - (discount.percentage / 100));
            }
        }

        return finalPrice;
    };

    // 3. Calcular COMISIÓN DE LÍNEA (Aplicando la MERMA O2O sobre el TOTAL del contrato)
    const calculateLineCommission = (line) => {
        if (!packages) return 0;
        const pkg = packages.find(p => p.id === line.package_id);
        if (!pkg) return 0;

        let commission = 0;

        // A. Base por Tarifa
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

        // --- D. CÁLCULO DE LA MERMA (PENALIZACIÓN) O2O ---
        // Fórmula: (Cuota Base x Meses Permanencia) x (% Penalización Tabla) = Dinero a restar de la comisión
        if (line.o2o_discount_id && discounts.length > 0) {
            const discount = discounts.find(d => d.id === line.o2o_discount_id);
            
            // Solo aplicamos merma si hay un descuento válido y permanencia seleccionada
            if (discount && cp > 0) {
                let penaltyPercentage = 0;
                let effectiveCp = cp;
                // 1. Buscamos el % de penalización en la tabla según la permanencia de la línea
                if (cp === 12) {
                    penaltyPercentage = parseFloat(discount.penalty_12m || 0);
                     effectiveCp = 24; 
                } else if (cp === 24) {
                    penaltyPercentage = parseFloat(discount.penalty_24m || 0);
                } else if (cp === 36) {
                    penaltyPercentage = parseFloat(discount.penalty_36m || 0);
                }

                // 2. Si hay penalización definida en la tabla, calculamos el importe a quitar
                if (penaltyPercentage > 0) {
                    const basePrice = getPackagePrice(line.package_id);
                    const finalPrice = calculateLinePrice(line);
                    // Calculamos el valor total que pagará el cliente durante la permanencia (antes de dtos)
                    // "MULTIPLICAS LA CUOTA BASE DE LA LINEA POR EL NUMERO DE MESES DE PERMANENCIA"
                   // if(cp==12){cp=24;}
                    const totalContractValue = (basePrice-finalPrice) * effectiveCp;
                    
                    // Calculamos cuánto dinero supone el porcentaje de merma sobre ese total
                    // "LE APLICAS EL PORCENTAJE... Y ESO ES LO QUE QUITAS"
                    const penaltyAmount = totalContractValue * (penaltyPercentage / 100);

                    // 3. Restamos ese importe a la comisión de la línea
                    commission = commission - penaltyAmount;
                }
            }
        }

        return commission > 0 ? commission : 0; // Evitamos comisiones negativas
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