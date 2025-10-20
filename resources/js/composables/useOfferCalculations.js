import { computed } from 'vue';

// Recibe las props y refs necesarios *específicamente* para el cálculo.
export function useOfferCalculations(
    props, // Necesita: packages, discounts, portabilityCommission, auth, centralitaExtensions, additionalInternetAddons
    selectedPackageId, // ref
    lines, // ref
    selectedInternetAddonId, // ref
    additionalInternetLines, // ref
    selectedCentralitaId, // ref
    centralitaExtensionQuantities, // ref
    isOperadoraAutomaticaSelected, // ref
    selectedTvAddonIds // ref
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
         if (!selectedPackage.value?.addons) return [];
         return selectedPackage.value.addons.filter(a => a.type === 'tv');
     });
    const centralitaAddonOptions = computed(() => { // Necesario para cálculo Centralita
        if (!selectedPackage.value?.addons) return [];
        return selectedPackage.value.addons.filter(a => a.type === 'centralita' && !a.pivot.is_included);
    });
    const includedCentralita = computed(() => { // Necesario para cálculo Centralita
        if (!selectedPackage.value?.addons) return null;
        return selectedPackage.value.addons.find(a => a.type === 'centralita' && a.pivot.is_included);
    });
    const isCentralitaActive = computed(() => { // Necesario para cálculo Centralita/Extensiones/Operadora
        return !!includedCentralita.value || !!selectedCentralitaId.value;
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
        return selectedPackage.value.addons.find(a => a.type === 'centralita_feature');
    });
     const mobileAddonInfo = computed(() => { // Necesario para cálculo Líneas Móviles
        if (!selectedPackage.value?.addons) return null;
        return selectedPackage.value.addons.find(a => a.type === 'mobile_line');
    });
     const availableO2oDiscounts = computed(() => { // Necesario para cálculo O2O
        if (!selectedPackage.value) return [];
        return selectedPackage.value.o2o_discounts || [];
    });
     const appliedDiscount = computed(() => {
        if (lines.value.length === 0 || !lines.value[0].is_portability || !selectedPackage.value) {
            return null;
        }
        const principalLine = lines.value[0];
        const packageName = selectedPackage.value.name;
        return props.discounts.find(d => {
            const conditions = d.conditions;
            if (conditions.package_names && !conditions.package_names.includes(packageName)) return false;
            if (conditions.requires_vap !== principalLine.has_vap) return false;
            if (conditions.excluded_operators?.includes(principalLine.source_operator)) return false;
            if (conditions.source_operators && !conditions.source_operators.includes(principalLine.source_operator)) return false;
            return true;
        });
    });
    // --- Fin Computeds auxiliares INTERNAS ---


    // --- CALCULATION SUMMARY ---
    const calculationSummary = computed(() => {
        if (!selectedPackage.value) {
            return { basePrice: 0, finalPrice: 0, summaryBreakdown: [], totalInitialPayment: 0, totalCommission: 0, teamCommission: 0, userCommission: 0, commissionDetails: {} };
        }

        // =================================================================
        // =========== CÓDIGO AÑADIDO / MODIFICADO AL PRINCIPIO ============
        // =================================================================
        const basePrice = parseFloat(selectedPackage.value.base_price) || 0;
        let price = basePrice;
        // La siguiente línea se ha modificado para que no la inicialice con el basePrice, ya que se añade abajo
        let summaryBreakdown = [{ description: `Paquete Base: ${selectedPackage.value.name}`, price: basePrice }];
        let commissionDetails = { Fibra: [], Televisión: [], Centralita: [], "Líneas Móviles": [], Terminales: [], Ajustes: [] };

        // >>>>> CÓDIGO NUEVO AÑADIDO <<<<<
        // Aplicar descuento de tarifa sobre el PRECIO BASE
        if (appliedDiscount.value) {
            // Se calcula sobre el 'basePrice', no sobre el 'price' acumulado
            const discountAmount = basePrice * (parseFloat(appliedDiscount.value.percentage) / 100);
            price -= discountAmount; // Restamos el descuento al total inicial
            summaryBreakdown.push({ description: `Descuento Tarifa (${appliedDiscount.value.percentage}%)`, price: -discountAmount });
        }
        // =================================================================


        if (selectedInternetAddonInfo.value) {
            const itemPrice = parseFloat(selectedInternetAddonInfo.value.pivot.price) || 0;
            price += itemPrice;
            if (itemPrice > 0) summaryBreakdown.push({ description: `Mejora Fibra (${selectedInternetAddonInfo.value.name})`, price: itemPrice });
            commissionDetails.Fibra.push({ description: `Fibra Principal (${selectedInternetAddonInfo.value.name})`, amount: parseFloat(selectedInternetAddonInfo.value.pivot.included_line_commission) || 0 });
        }

        additionalInternetLines.value.forEach((line, index) => {
            if (line.addon_id) {
                const addonInfo = props.additionalInternetAddons.find(a => a.id === line.addon_id);
                if (addonInfo) {
                    const itemPrice = parseFloat(addonInfo.price) || 0;
                    price += itemPrice;
                    summaryBreakdown.push({ description: `Internet Adicional ${index + 1} (${addonInfo.name})`, price: itemPrice });
                    commissionDetails.Fibra.push({ description: `Internet Adicional ${index + 1} (${addonInfo.name})`, amount: parseFloat(addonInfo.commission) || 0 });
                }
            }
        });

        // Lógica TV
        tvAddonOptions.value.forEach(addon => {
            if (selectedTvAddonIds.value.includes(addon.id)) {
                const itemPrice = parseFloat(addon.pivot.price) || 0;
                price += itemPrice;
                if (itemPrice > 0) summaryBreakdown.push({ description: `TV: ${addon.name}`, price: itemPrice });
                commissionDetails.Televisión.push({ description: addon.name, amount: parseFloat(addon.pivot.included_line_commission ?? addon.commission) || 0 });
            }
        });

        // Lógica Centralita
        if (includedCentralita.value) {
            commissionDetails.Centralita.push({ description: `Centralita Incluida (${includedCentralita.value.name})`, amount: parseFloat(includedCentralita.value.pivot.included_line_commission) || 0 });
        } else if (selectedCentralitaId.value) {
            const selectedCentralita = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
            if (selectedCentralita) {
                const itemPrice = parseFloat(selectedCentralita.pivot.price) || 0;
                price += itemPrice;
                summaryBreakdown.push({ description: `Centralita: ${selectedCentralita.name}`, price: itemPrice });
                commissionDetails.Centralita.push({ description: `Centralita Contratada (${selectedCentralita.name})`, amount: parseFloat(selectedCentralita.commission) || 0 });
            }
        }

        // Lógica Operadora Automática
        if (isCentralitaActive.value && operadoraAutomaticaInfo.value) {
            const commission = parseFloat(operadoraAutomaticaInfo.value.pivot.included_line_commission) || 0;
            if (operadoraAutomaticaInfo.value.pivot.is_included) {
                commissionDetails.Centralita.push({ description: 'Operadora Automática (Incluida)', amount: commission });
            } else if (isOperadoraAutomaticaSelected.value) {
                const itemPrice = parseFloat(operadoraAutomaticaInfo.value.pivot.price) || 0;
                price += itemPrice;
                summaryBreakdown.push({ description: 'Operadora Automática', price: itemPrice });
                commissionDetails.Centralita.push({ description: 'Operadora Automática (Contratada)', amount: commission });
            }
        }

        // Lógica Extensiones
        if (isCentralitaActive.value) {
            includedCentralitaExtensions.value.forEach(ext => {
                const commissionPerUnit = parseFloat(ext.pivot.included_line_commission) || 0;
                const quantity = ext.pivot.included_quantity || 0;
                if (quantity > 0) {
                    commissionDetails.Centralita.push({ description: `${quantity}x ${ext.name} (Incluidas)`, amount: quantity * commissionPerUnit });
                }
            });

            if (autoIncludedExtension.value) {
                commissionDetails.Centralita.push({ description: `1x ${autoIncludedExtension.value.name} (Por Centralita)`, amount: 0 });
            }

            for (const addonId in centralitaExtensionQuantities.value) {
                const quantity = centralitaExtensionQuantities.value[addonId];
                if (quantity > 0) {
                    const addonInfo = props.centralitaExtensions.find(ext => ext.id == addonId);
                    if (addonInfo) {
                        const itemPrice = quantity * (parseFloat(addonInfo.price) || 0);
                        price += itemPrice;
                        summaryBreakdown.push({ description: `${quantity}x ${addonInfo.name} (Adicional)`, price: itemPrice });
                        commissionDetails.Centralita.push({ description: `${quantity}x ${addonInfo.name} (Adicional)`, amount: quantity * (parseFloat(addonInfo.commission) || 0) });
                    }
                }
            }
        }

        // Lógica Líneas Móviles y Terminales
        const appliedO2oList = [];
        let totalTerminalFee = 0;
        let totalInitialPayment = 0;
        let extraLinesCost = 0;
        
        if (mobileAddonInfo.value) {
            const promoLimit = mobileAddonInfo.value.pivot.line_limit;
            const promoPrice = 8.22;
            const standardPrice = mobileAddonInfo.value.pivot.price;
            const includedCommission = parseFloat(mobileAddonInfo.value.pivot.included_line_commission) || 0;
            const additionalCommission = parseFloat(mobileAddonInfo.value.pivot.additional_line_commission) || 0;
            let extraLinesCounter = 0;

            lines.value.forEach((line, index) => {
                const lineName = index === 0 ? 'Línea Principal' : `Línea Adicional ${index+1}`;
                totalTerminalFee += parseFloat(line.monthly_cost || 0);
                totalInitialPayment += parseFloat(line.initial_cost || 0);

                if (line.is_extra) {
                    extraLinesCounter++;
                    const itemPrice = (extraLinesCounter <= promoLimit) ? promoPrice : parseFloat(standardPrice);
                    extraLinesCost += itemPrice;
                    summaryBreakdown.push({ description: `Línea Móvil Adicional ${extraLinesCounter}`, price: itemPrice });
                    commissionDetails["Líneas Móviles"].push({ description: `Comisión ${lineName}`, amount: additionalCommission });
                } else {
                    commissionDetails["Líneas Móviles"].push({ description: `Comisión ${lineName}`, amount: includedCommission });
                }

                if (line.is_portability) {
                    commissionDetails["Líneas Móviles"].push({ description: `Portabilidad ${lineName}`, amount: props.portabilityCommission });
                }

                if (line.terminal_pivot && line.selected_duration) {
                    const terminalTotalPrice = (parseFloat(line.initial_cost) || 0) + (parseFloat(line.monthly_cost || 0) * parseInt(line.selected_duration, 10));
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
                        summaryBreakdown.push({ description: `Subvención O2O (${o2o.name})`, price: -monthlyValue });
                        if (o2o.pivot && o2o.pivot.dho_payment) {
                            commissionDetails.Ajustes.push({ description: `Ajuste DHO ${lineName}`, amount: -parseFloat(o2o.pivot.dho_payment) });
                        }
                    }
                }
            });
        }

        price += totalTerminalFee; // Sumar cuotas terminal
        if(totalTerminalFee > 0) {
            summaryBreakdown.push({ description: 'Cuotas mensuales de Terminales', price: totalTerminalFee });
        }
        price += extraLinesCost; // Sumar coste líneas extra

        // =================================================================
        // =================== CÓDIGO QUITADO DEL FINAL ====================
        // =================================================================
        /* >>>>> CÓDIGO ANTIGUO ELIMINADO <<<<<
        // Aplicar descuento tarifa
        if (appliedDiscount.value) {
            const discountAmount = price * (parseFloat(appliedDiscount.value.percentage) / 100);
            price -= discountAmount;
            summaryBreakdown.push({ description: `Descuento Tarifa (${appliedDiscount.value.percentage}%)`, price: -discountAmount });
        }
        */
        // =================================================================

        // Limpiar categorías vacías
        Object.keys(commissionDetails).forEach(key => {
            if (commissionDetails[key].length === 0) {
                delete commissionDetails[key];
            }
        });

        // Calcular comisiones finales
        const totalCommission = Object.values(commissionDetails).flat().reduce((acc, item) => acc + item.amount, 0);
        const currentUser = props.auth.user;
        let teamCommission = 0;
        let userCommission = 0;

        if (currentUser.role === 'admin') {
            userCommission = totalCommission;
            teamCommission = totalCommission;
        } 
        else if (currentUser.team) {
            const teamPercentage = currentUser.team.commission_percentage || 0;
            teamCommission = totalCommission * (parseFloat(teamPercentage) / 100);
            if (currentUser.role === 'user') {
                const userPercentage = currentUser.commission_percentage || 0;
                userCommission = teamCommission * (parseFloat(userPercentage) / 100);
            } else { // team_lead
                userCommission = teamCommission;
            }
        }
        else { // user sin equipo
            const userPercentage = currentUser.commission_percentage || 0;
            userCommission = totalCommission * (parseFloat(userPercentage) / 100);
            teamCommission = 0;
        }

        // Devolver la misma estructura
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
    // --- FIN CALCULATION SUMMARY ---

    return {
        calculationSummary,
    };
}