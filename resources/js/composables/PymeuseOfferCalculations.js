import { computed } from 'vue';

export function usePymeOfferCalculations(packages, mobileLines, tariffType) {

    // Helper para obtener el precio base del paquete
    const getPackagePrice = (id) => {
        if (!packages) return 0;
        const pkg = packages.find(p => p.id === id);
        return pkg ? parseFloat(pkg.base_price) : 0;
    };

    // Helper para calcular la comisión de una línea individual
    const calculateLineCommission = (line) => {
        if (!packages) return 0;
        const pkg = packages.find(p => p.id === line.package_id);
        if (!pkg) return 0;

        let commission = 0;

        // 1. Base por Tarifa (Según Selector Óptima vs Personalizada)
        if (tariffType.value === 'OPTIMA') {
            commission += parseFloat(pkg.commission_optima || 0);
        } else {
            commission += parseFloat(pkg.commission_custom || 0);
        }

        // 2. Plus por Portabilidad
        if (line.type === 'portabilidad') {
            commission += parseFloat(pkg.commission_porta || 30);
        }

        // 3. Bonus por Permanencia (CP)
        const cp = parseInt(line.cp_duration);
        if (cp === 24) {
            commission += parseFloat(pkg.bonus_cp_24 || 0);
        } else if (cp === 36) {
            commission += parseFloat(pkg.bonus_cp_36 || 0);
        }

        return commission;
    };

    // Computed para el total general de comisiones
    const totalCommission = computed(() => {
        return mobileLines.value.reduce((sum, line) => {
            const lineComm = calculateLineCommission(line);
            const qty = parseInt(line.quantity) || 0;
            return sum + (lineComm * qty);
        }, 0);
    });

    return {
        getPackagePrice,
        calculateLineCommission,
        totalCommission
    };
}