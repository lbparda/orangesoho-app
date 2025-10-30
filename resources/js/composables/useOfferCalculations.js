import { computed } from 'vue';

// Recibe las props y refs necesarios *específicamente* para el cálculo.
export function useOfferCalculations(
    props, // Necesita: packages, discounts, portabilityCommission, auth, centralitaExtensions, additionalInternetAddons, fiberFeatures
    selectedPackageId, // ref
    lines, // ref
    selectedInternetAddonId, // ref
    additionalInternetLines, // ref <-- Ahora contiene objetos con { addon_id, has_ip_fija, selected_centralita_id }
    selectedCentralitaId, // ref
    centralitaExtensionQuantities, // ref
    isOperadoraAutomaticaSelected, // ref
    selectedTvAddonIds, // ref
    form // <-- Recibe el objeto 'form' completo (incluye form.is_ip_fija_selected para línea principal)
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
    const isCentralitaActive = computed(() => { // Necesario para cálculo Centralita/Extensiones/Operadora/IP Fija
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

    // --- Computed para IP Fija (Addon principal) ---
    const ipFijaAddonInfo = computed(() => {
        if (!props.fiberFeatures || props.fiberFeatures.length === 0) return null;
        // Asumimos que solo hay una, la primera que encuentre con el nombre exacto
        return props.fiberFeatures.find(f => f.name === 'IP Fija'); // Más seguro buscar por nombre
    });
    // --- FIN ---

    // =================================================================
    // =========== LÓGICA DE DESCUENTOS (VERSIÓN CON DEBUG) ============
    // =================================================================
    const appliedDiscount = computed(() => {
        // --- INICIO CÓDIGO RESTAURADO ---
        if (!lines.value || lines.value.length === 0 || !selectedPackage.value) { // Añadida comprobación para lines.value
            return null;
        }

        const principalLine = lines.value[0];
        // Comprobar si principalLine existe antes de acceder a sus propiedades
        if (!principalLine) return null;
        // --- FIN CÓDIGO RESTAURADO ---

        const packageName = selectedPackage.value.name;

        const hasTVBares = selectedTvAddonIds.value.some(id => {
            const tvAddon = tvAddonOptions.value.find(a => a.id === id);
            return tvAddon && tvAddon.name.includes('Futbol Bares');
        });

        // console.clear(); // Limpia la consola en cada recálculo - Puedes descomentar si quieres
        console.log("===== INICIO DEBUG DESCUENTOS =====");
        console.log(`Paquete: ${packageName}`);
        console.log(`Línea Principal:`, { is_portability: principalLine.is_portability, has_vap: principalLine.has_vap, source_operator: principalLine.source_operator });
        console.log(`¿Tiene TV Bares?: ${hasTVBares}`);
        console.log("-------------------------------------");

        // 1. Si se ha contratado TV Bares
        if (hasTVBares) {
            console.log("Modo: TV BARES. Buscando descuento prioritario...");
            const matchingTvDiscount = props.discounts.find(d => {
                const conditions = d.conditions;
                if (!conditions.requires_tv_bares) return false;

                const packageMatch = conditions.package_names.includes(packageName);
                const portabilityMatch = conditions.requires_portability === principalLine.is_portability;
                const vapMatch = conditions.requires_vap === principalLine.has_vap;

                if(packageMatch && portabilityMatch && vapMatch) {
                    console.log(`%c[MATCH] ${d.name}`, "color: green; font-weight: bold;");
                    return true;
                }
                return false;
            });
            console.log("===== FIN DEBUG =====");
            return matchingTvDiscount || null;
        }

        // 2. Si NO se ha contratado TV Bares
        else {
            console.log("Modo: GENERAL. Buscando descuentos aplicables...");
            const applicableGeneralDiscounts = props.discounts.filter(d => {
                const conditions = d.conditions;
                console.log(`\nEvaluando: "${d.name}"`);

                if (conditions.requires_tv_bares) {
                    console.log(` -> DESCARTADO: Es para TV Bares.`);
                    return false;
                }
                if (!conditions.package_names || !conditions.package_names.includes(packageName)) {
                    console.log(` -> DESCARTADO: No aplica a este paquete.`);
                    return false;
                }

                if (conditions.hasOwnProperty('requires_vap') && conditions.requires_vap !== principalLine.has_vap) {
                    console.log(` -> DESCARTADO: Condición de VAP no cumplida (Req: ${conditions.requires_vap}, Tiene: ${principalLine.has_vap})`);
                    return false;
                }

                if (conditions.hasOwnProperty('requires_portability') && conditions.requires_portability !== principalLine.is_portability) {
                    console.log(` -> DESCARTADO: Condición de Portabilidad no cumplida (Req: ${conditions.requires_portability}, Es: ${principalLine.is_portability})`);
                    return false;
                }

                // --- INICIO CÓDIGO RESTAURADO ---
                if (conditions.hasOwnProperty('source_operators') && conditions.source_operators && !conditions.source_operators.includes(principalLine.source_operator)) {
                    console.log(` -> DESCARTADO: Operador de origen no permitido (Req: ${conditions.source_operators.join(', ')}, Viene de: ${principalLine.source_operator})`);
                    return false;
                }

                if (conditions.hasOwnProperty('excluded_operators') && conditions.excluded_operators && conditions.excluded_operators.includes(principalLine.source_operator)) {
                    console.log(` -> DESCARTADO: Operador de origen EXCLUIDO (Excluye: ${conditions.excluded_operators.join(', ')}, Viene de: ${principalLine.source_operator})`);
                    return false;
                }
                // --- FIN CÓDIGO RESTAURADO ---


                console.log(` -> CUMPLE TODAS LAS CONDICIONES`);
                return true;
            });

            if (applicableGeneralDiscounts.length > 0) {
                console.log("\nDescuentos aplicables encontrados:", applicableGeneralDiscounts.map(d => d.name));
                const bestDiscount = applicableGeneralDiscounts.sort((a, b) => b.percentage - a.percentage)[0];
                console.log(`%c[SELECCIONADO] El mejor es: ${bestDiscount.name} (${bestDiscount.percentage}%)`, "color: blue; font-weight: bold;");
                console.log("===== FIN DEBUG =====");
                return bestDiscount;
            } else {
                console.log("\nNo se encontraron descuentos aplicables.");
                console.log("===== FIN DEBUG =====");
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
        let commissionDetails = { Fibra: [], Televisión: [], Centralita: [], "Líneas Móviles": [], Terminales: [], Ajustes: [] };

        if (appliedDiscount.value) {
            const discountAmount = basePrice * (parseFloat(appliedDiscount.value.percentage) / 100);
            price -= discountAmount;
            summaryBreakdown.push({ description: `Descuento Tarifa (${appliedDiscount.value.percentage}%)`, price: -discountAmount });
        }

        if (selectedInternetAddonInfo.value) {
            const itemPrice = parseFloat(selectedInternetAddonInfo.value.pivot.price) || 0;
            price += itemPrice;
            if (itemPrice > 0) summaryBreakdown.push({ description: `Mejora Fibra (${selectedInternetAddonInfo.value.name})`, price: itemPrice });
            commissionDetails.Fibra.push({ description: `Fibra Principal (${selectedInternetAddonInfo.value.name})`, amount: parseFloat(selectedInternetAddonInfo.value.pivot.included_line_commission) || 0 });
        }

        // --- INICIO CÓDIGO MODIFICADO: Añadir cálculo IP Fija adicional y Centralita Multisede ---
        additionalInternetLines.value.forEach((line, index) => {
            if (line.addon_id) {
                const addonInfo = props.additionalInternetAddons.find(a => a.id === line.addon_id);
                if (addonInfo) {
                    // Precio de la línea adicional
                    const linePrice = parseFloat(addonInfo.price) || 0;
                    price += linePrice;
                    summaryBreakdown.push({ description: `Internet Adicional ${index + 1} (${addonInfo.name})`, price: linePrice });
                    commissionDetails.Fibra.push({ description: `Internet Adicional ${index + 1} (${addonInfo.name})`, amount: parseFloat(addonInfo.commission) || 0 });

                    // --- INICIO LÓGICA MODIFICADA: IP Fija gratis si hay centralita multisede ---
                    // Precio/Comisión de la IP Fija para esta línea adicional
                    if (line.has_ip_fija && ipFijaAddonInfo.value) {
                        const isIncluded = !!line.selected_centralita_id; // <-- Gratis si hay centralita en ESTA línea
                        const ipFijaPrice = isIncluded ? 0 : (parseFloat(ipFijaAddonInfo.value.price) || 0); // <-- Precio 0 si está incluida
                        const description = isIncluded ? `IP Fija Adicional ${index + 1} (Incluida por Centralita)` : `IP Fija Adicional ${index + 1}`; // <-- Descripción dinámica
                        
                        const ipFijaCommission = parseFloat(ipFijaAddonInfo.value.commission) || 0;
                        price += ipFijaPrice;
                        summaryBreakdown.push({ description: description, price: ipFijaPrice });
                        
                        if (ipFijaCommission > 0) { // Solo añadir si hay comisión definida
                            commissionDetails.Fibra.push({ description: `IP Fija Adicional ${index + 1}`, amount: ipFijaCommission });
                        }
                    }
                    // --- FIN LÓGICA MODIFICADA ---

                    // --- INICIO CÓDIGO NUEVO: Cálculo Centralita Multisede ---
                    if (line.selected_centralita_id) {
                        const centralitaInfo = centralitaAddonOptions.value.find(c => c.id === line.selected_centralita_id);
                        if (centralitaInfo) {
                            // 1. Añadir precio y comisión de la centralita en sí
                            const itemPrice = parseFloat(centralitaInfo.pivot.price) || 0;
                            const commission = parseFloat(centralitaInfo.commission) || 0;
                            const decommission = parseFloat(centralitaInfo.decommission) || 0;
                            const totalAmount = commission + decommission;
                            
                            price += itemPrice;
                            summaryBreakdown.push({ description: `Centralita Multisede ${index + 1} (${centralitaInfo.name})`, price: itemPrice });
                            
                            commissionDetails.Centralita.push({ 
                                description: `Centralita Multisede ${index + 1} (${centralitaInfo.name})`, 
                                amount: totalAmount 
                            });

                            // 2. Añadir comisión de la extensión auto-incluida (precio 0)
                            const centralitaType = centralitaInfo.name.split(' ')[1]; // 'Básica', 'Inalámbrica', 'Avanzada'
                            if (centralitaType) {
                                const autoExt = props.centralitaExtensions.find(ext => ext.name.includes(centralitaType));
                                if (autoExt) {
                                    const extCommission = parseFloat(autoExt.commission) || 0;
                                    if (extCommission > 0) { // Solo añadir si hay comisión
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
        // Accedemos a form.is_ip_fija_selected directamente
        if (form.is_ip_fija_selected && ipFijaAddonInfo.value) {
            const isIncluded = isCentralitaActive.value;
            const itemPrice = isIncluded ? 0 : (parseFloat(ipFijaAddonInfo.value.price) || 0);
            const description = isIncluded ? 'IP Fija Principal (Incluida por Centralita)' : 'IP Fija Principal'; // Añadido 'Principal'
            const commission = parseFloat(ipFijaAddonInfo.value.commission) || 0;

            price += itemPrice;
            summaryBreakdown.push({ description: description, price: itemPrice });

            if (commission > 0) {
                 // Añadir comisión (si la definiste en el Seeder)
                commissionDetails.Fibra.push({ description: 'IP Fija Principal', amount: commission });
            }
        // Accedemos a form.is_ip_fija_selected directamente
        } else if (form.is_ip_fija_selected) {
             console.log("IP Fija principal seleccionada, pero ipFijaAddonInfo es null/undefined.");
        }
        // ===================================================
        // --- FIN LÓGICA IP FIJA PRINCIPAL ---
        // ===================================================

        tvAddonOptions.value.forEach(addon => {
            if (selectedTvAddonIds.value.includes(addon.id)) {
                const itemPrice = parseFloat(addon.pivot?.price ?? addon.price) || 0;
                price += itemPrice;
                if (itemPrice > 0) summaryBreakdown.push({ description: `TV: ${addon.name}`, price: itemPrice });
                commissionDetails.Televisión.push({ description: addon.name, amount: parseFloat(addon.pivot?.included_line_commission ?? addon.commission) || 0 });
            }
        });

        if (includedCentralita.value) {
            // 1. Obtenemos la comisión normal
            const commission = parseFloat(includedCentralita.value.pivot.included_line_commission) || 0;
            
            // 2. Obtenemos la NUEVA decomisión (que vendrá en negativo)
            const decommission = parseFloat(includedCentralita.value.pivot.included_line_decommission) || 0; // <-- ¡AQUÍ!

            // 3. Los sumamos para obtener el total
            const totalAmount = commission + decommission;

  //_MODIFIED       
             commissionDetails.Centralita.push({ 
                description: `Centralita Incluida (${includedCentralita.value.name})`, 
                amount: totalAmount // <-- Usamos el total
            });
        } else if (selectedCentralitaId.value) {
            const selectedCentralita = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
            if (selectedCentralita) {
                const itemPrice = parseFloat(selectedCentralita.pivot.price) || 0;
                price += itemPrice;
                summaryBreakdown.push({ description: `Centralita: ${selectedCentralita.name}`, price: itemPrice });
                const commission = parseFloat(selectedCentralita.commission) || 0;
                const decommission = parseFloat(selectedCentralita.decommission) || 0;
                const totalAmount = commission+decommission;
                commissionDetails.Centralita.push({ description: `Centralita Contratada (${selectedCentralita.name})`, amount: totalAmount  });
            }
        }

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

        if (isCentralitaActive.value) {
            includedCentralitaExtensions.value.forEach(ext => {
                const commissionPerUnit = parseFloat(ext.pivot.included_line_commission) || 0;
                const quantity = ext.pivot.included_quantity || 0;
                if (quantity > 0) {
                    commissionDetails.Centralita.push({ description: `${quantity}x ${ext.name} (Incluidas)`, amount: quantity * commissionPerUnit });
                }
            });

            if (autoIncludedExtension.value && !includedCentralita.value) { // Modificado para que no añada si ya está incluida la centralita
                const commission = parseFloat(autoIncludedExtension.value.commission) || 0;


                commissionDetails.Centralita.push({ description: `1x ${autoIncludedExtension.value.name} (Por Centralita)`, amount: commission });
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

        const appliedO2oList = [];
        let totalTerminalFee = 0;
        let totalInitialPayment = 0;
        let extraLinesCost = 0;

        if (mobileAddonInfo.value) {
            const promoLimit = mobileAddonInfo.value.pivot.line_limit ?? 0; // Añadido ?? 0
            const promoPrice = 8.22;
            const standardPrice = mobileAddonInfo.value.pivot.price ?? 0; // Añadido ?? 0
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
                    const exceptions = props.portabilityExceptions || [];
                    const isException = exceptions.includes(line.source_operator);
                    const commissionAmount = isException ? 0 : (parseFloat(props.portabilityCommission) || 0);
                    commissionDetails["Líneas Móviles"].push({
                        description: `Portabilidad ${lineName}`,
                        amount: commissionAmount
                    });
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
                        // Ajuste DHO ahora se busca en la relación pivote de o2o_discount_package
                        const packageO2oPivot = selectedPackage.value?.o2o_discounts?.find(d => d.id === o2o.id)?.pivot;
                        if (packageO2oPivot && packageO2oPivot.dho_payment) {
                             commissionDetails.Ajustes.push({ description: `Ajuste DHO ${lineName}`, amount: -parseFloat(packageO2oPivot.dho_payment) });
                         }
                    }
                }
            });
        }

        price += totalTerminalFee;
        if(totalTerminalFee > 0) {
            summaryBreakdown.push({ description: 'Cuotas mensuales de Terminales', price: totalTerminalFee });
        }
        price += extraLinesCost; // Este ya estaba

        Object.keys(commissionDetails).forEach(key => {
            if (commissionDetails[key].length === 0) {
                delete commissionDetails[key];
            }
        });

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
            } else { // team_lead o jefe de ventas
                userCommission = teamCommission;
            }
        }
        else { // user sin equipo
            const userPercentage = currentUser.commission_percentage || 0;
            userCommission = totalCommission * (parseFloat(userPercentage) / 100);
            teamCommission = 0; // O quizás la comisión bruta si no hay equipo? Depende de reglas de negocio
        }

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

    return {
        calculationSummary,
    };
}