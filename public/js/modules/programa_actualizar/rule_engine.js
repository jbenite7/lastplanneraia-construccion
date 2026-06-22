/**
 * Módulo de Reglas Progresivas para Asociación de Actividades
 * Arquitectura CSS 2026 - LPS
 * 
 * Motor de inferencia que sugiere procesos basado en:
 * 1. Token discriminante (frecuencia inversa)
 * 2. Vecindad (actividades adyacentes)
 * 3. Memoria del proyecto (sessionStorage)
 * 4. Similitud de texto (Jaccard)
 */

window.RuleEngine = (function() {
    'use strict';

    // ==========================================
    // REGISTRO LOCAL DE PROCESOS CONOCIDOS
    // ==========================================
    var REGISTRO_PROCESOS = [
        'Impermeabilización', 'Enfierradura', 'Habilitación', 'Instalación',
        'Demolición', 'Limpieza', 'Excavación', 'Concreto', 'Mampostería',
        'Carpintería', 'Plomería', 'Electricidad', 'Pintura', 'Acabados',
        'Techos', 'Pisos', 'Puertas', 'Ventanas', 'Baños', 'Cocinas',
        'Ascensores', 'Escaleras', 'Barandas', 'Cielo rasos',
        'Aislamiento', 'Drywall', 'Enchape', 'Baldosas', 'Azulejos',
        'Cielos', 'Fachada', 'Muros', 'Columnas', 'Vigas', 'Losas',
        'Fundaciones', 'Estructura', 'Cimentación', 'Taludes',
        'Drenaje', 'Acueductos', 'Alcantarillado', 'Agua potable',
        'Gas', 'Telecomunicaciones', 'Incendios', 'Aire acondicionado',
        'Ascensor', 'Montacargas', 'Señalización', 'Jardinería',
        'Paisajismo', 'Luminarias', 'Iluminación', 'Seguridad',
        'Vigilancia', 'CCTV', 'Control de acceso'
    ];

    // ==========================================
    // FUNCIONES AUXILIARES PRIVADAS
    // ==========================================

    /**
     * Limpia y normaliza texto: elimina HTML, acentos, puntuación,
     * convierte a minúsculas y colapsa espacios.
     * @param {string} texto - Texto de entrada
     * @returns {string} Texto limpio
     */
    function limpiarTexto(texto) {
        if (!texto) return '';
        
        // Eliminar HTML
        var temporal = document.createElement('div');
        temporal.innerHTML = texto;
        var sinHtml = temporal.textContent || temporal.innerText || '';
        
        // Eliminar acentos y caracteres especiales
        var sinAcentos = sinHtml
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[ñ]/g, 'n')
            .replace(/[Ñ]/g, 'N');
        
        // Limpiar puntuación y espacios
        var limpio = sinAcentos
            .toLowerCase()
            .replace(/[^\w\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
        
        return limpio;
    }

    /**
     * Tokeniza un nombre de actividad en tokens limpios.
     * @param {string} nombre - Nombre de la actividad
     * @returns {string[]} Array de tokens
     */
    function tokenizar(nombre) {
        if (!nombre || typeof nombre !== 'string') return [];
        
        var limpio = limpiarTexto(nombre);
        if (!limpio) return [];
        
        return limpio.split(' ');
    }

    /**
     * Encuentra el token más discriminante (menos frecuente) en un array.
     * @param {string[]} tokens - Array de tokens
     * @returns {string|null} Token menos frecuente
     */
    function tokenMasDiscriminante(tokens) {
        if (!tokens || tokens.length === 0) return null;
        
        // Contar frecuencia de cada token
        var frecuencias = {};
        tokens.forEach(function(token) {
            frecuencias[token] = (frecuencias[token] || 0) + 1;
        });
        
        // Encontrar el token con menor frecuencia
        var tokenMenosFrecuente = null;
        var menorFrecuencia = Infinity;
        
        Object.keys(frecuencias).forEach(function(token) {
            if (frecuencias[token] < menorFrecuencia) {
                menorFrecuencia = frecuencias[token];
                tokenMenosFrecuente = token;
            }
        });
        
        return tokenMenosFrecuente;
    }

    /**
     * Analiza la vecindad de una actividad para encontrar solapamiento de tokens.
     * @param {object} actividad - Objeto actividad con vecinos
     * @returns {object|null} Resultado del análisis de vecindad
     */
    function clusterPorVecindad(actividad) {
        if (!actividad || !actividad.vecinos || !Array.isArray(actividad.vecinos)) {
            return null;
        }
        
        var tokensActividad = tokenizar(actividad.nombre);
        if (tokensActividad.length === 0) return null;
        
        var vecinos = actividad.vecinos.filter(function(v) {
            return v && typeof v === 'string';
        });
        
        if (vecinos.length === 0) return null;
        
        // Analizar solapamiento con cada vecino
        var mejorVecino = null;
        var mayorSolapamiento = 0;
        var totalSolapamiento = 0;
        
        vecinos.forEach(function(vecino) {
            var tokensVecino = tokenizar(vecino);
            var solapamiento = tokensActividad.filter(function(t) {
                return tokensVecino.indexOf(t) !== -1;
            }).length;
            
            totalSolapamiento += solapamiento;
            
            if (solapamiento > mayorSolapamiento) {
                mayorSolapamiento = solapamiento;
                mejorVecino = vecino;
            }
        });
        
        var confianza = totalSolapamiento / (tokensActividad.length * vecinos.length);
        
        return {
            mejorVecino: mejorVecino,
            confianza: Math.min(confianza, 1.0),
            totalVecinos: vecinos.length
        };
    }

    /**
     * Carga la memoria del proyecto desde sessionStorage.
     * @returns {object[]} Array de decisiones previas
     */
    function cargarMemoriaProyecto() {
        try {
            var memoria = sessionStorage.getItem('rule_engine_memoria');
            if (memoria) {
                return JSON.parse(memoria);
            }
        } catch (e) {
            console.error('[RuleEngine] Error cargando memoria:', e);
        }
        return [];
    }

    /**
     * Guarda una decisión en la memoria del proyecto (sessionStorage).
     * @param {object} actividad - Actividad original
     * @param {string} proceso - Proceso sugerido/aceptado
     */
    function guardarMemoriaProyecto(actividad, proceso) {
        if (!actividad || !proceso) return;
        
        try {
            var memoria = cargarMemoriaProyecto();
            
            // Verificar si ya existe una entrada similar
            var existente = memoria.find(function(item) {
                return item.nombre === actividad.nombre && item.proceso === proceso;
            });
            
            if (!existente) {
                memoria.push({
                    nombre: actividad.nombre,
                    tokens: tokenizar(actividad.nombre),
                    proceso: proceso,
                    fecha: new Date().toISOString(),
                    precision: 1.0
                });
                
                // Limitar a las últimas 100 entradas
                if (memoria.length > 100) {
                    memoria = memoria.slice(-100);
                }
                
                sessionStorage.setItem('rule_engine_memoria', JSON.stringify(memoria));
            }
        } catch (e) {
            console.error('[RuleEngine] Error guardando memoria:', e);
        }
    }

    /**
     * Calcula la similitud de Jaccard entre dos conjuntos de tokens.
     * @param {string[]} tokensA - Primer conjunto de tokens
     * @param {string[]} tokensB - Segundo conjunto de tokens
     * @returns {number} Coeficiente de Jaccard (0-1)
     */
    function similitudJaccard(tokensA, tokensB) {
        if (!tokensA || !tokensB || tokensA.length === 0 || tokensB.length === 0) {
            return 0;
        }
        
        var conjuntoA = new Set(tokensA);
        var conjuntoB = new Set(tokensB);
        
        var interseccion = new Set();
        conjuntoA.forEach(function(token) {
            if (conjuntoB.has(token)) {
                interseccion.add(token);
            }
        });
        
        var union = new Set();
        conjuntoA.forEach(function(token) { union.add(token); });
        conjuntoB.forEach(function(token) { union.add(token); });
        
        if (union.size === 0) return 0;
        
        return interseccion.size / union.size;
    }

    // ==========================================
    // REGLAS DE INFERENCIA
    // ==========================================

    /**
     * Regla 1: Token Discriminante
     * Encuentra el token menos frecuente y lo compara con el registro.
     */
    function reglaTokenDiscriminante(actividad) {
        if (!actividad || !actividad.nombre) return null;
        
        var tokens = tokenizar(actividad.nombre);
        if (tokens.length === 0) return null;
        
        var tokenDiscriminante = tokenMasDiscriminante(tokens);
        if (!tokenDiscriminante) return null;
        
        // Buscar coincidencia en el registro de procesos
        var mejorCoincidencia = null;
        var mejorPuntuacion = 0;
        
        REGISTRO_PROCESOS.forEach(function(proceso) {
            var tokensProceso = tokenizar(proceso);
            var tokensActividad = tokens;
            
            // Verificar si el token discriminante está en el proceso
            if (tokensProceso.indexOf(tokenDiscriminante) !== -1) {
                // Calcular puntuación basada en solapamiento total
                var solapamiento = tokensActividad.filter(function(t) {
                    return tokensProceso.indexOf(t) !== -1;
                }).length;
                
                var puntuacion = solapamiento / tokensProceso.length;
                
                if (puntuacion > mejorPuntuacion) {
                    mejorPuntuacion = puntuacion;
                    mejorCoincidencia = proceso;
                }
            }
        });
        
        if (mejorCoincidencia) {
            var confianza = 1.0 - (tokens.indexOf(tokenDiscriminante) / tokens.length);
            confianza = Math.max(0.3, Math.min(confianza, 1.0));
            
            return {
                proceso: mejorCoincidencia,
                confianza: confianza,
                explicacion: 'Token discriminante "' + tokenDiscriminante + '" coincide con proceso "' + mejorCoincidencia + '"',
                regla: 'token_discriminante'
            };
        }
        
        return null;
    }

    /**
     * Regla 2: Vecindad
     * Analiza las actividades adyacentes para encontrar procesos similares.
     */
    function reglaVecindad(actividad) {
        var analisis = clusterPorVecindad(actividad);
        if (!analisis || analisis.confianza < 0.1) return null;
        
        // Buscar el proceso más probable entre los vecinos
        var tokensVecino = tokenizar(analisis.mejorVecino);
        var mejorProceso = null;
        var mejorPuntuacion = 0;
        
        REGISTRO_PROCESOS.forEach(function(proceso) {
            var tokensProceso = tokenizar(proceso);
            var puntuacion = similitudJaccard(tokensVecino, tokensProceso);
            
            if (puntuacion > mejorPuntuacion) {
                mejorPuntuacion = puntuacion;
                mejorProceso = proceso;
            }
        });
        
        if (mejorProceso && analisis.confianza >= 0.2) {
            return {
                proceso: mejorProceso,
                confianza: analisis.confianza * mejorPuntuacion,
                explicacion: 'Vecino "' + analisis.mejorVecino + '" sugiere proceso "' + mejorProceso + '"',
                regla: 'vecindad'
            };
        }
        
        return null;
    }

    /**
     * Regla 3: Memoria del Proyecto
     * Consulta decisiones previas almacenadas en sessionStorage.
     */
    function reglaMemoriaProyecto(actividad) {
        if (!actividad || !actividad.nombre) return null;
        
        var memoria = cargarMemoriaProyecto();
        if (memoria.length === 0) return null;
        
        var tokensActividad = tokenizar(actividad.nombre);
        if (tokensActividad.length === 0) return null;
        
        var mejorMatch = null;
        var mejorSimilitud = 0;
        var mejorPrecision = 0;
        
        memoria.forEach(function(decision) {
            if (!decision.tokens || decision.tokens.length === 0) return;
            
            var similitud = similitudJaccard(tokensActividad, decision.tokens);
            
            if (similitud > mejorSimilitud && similitud > 0.3) {
                mejorSimilitud = similitud;
                mejorMatch = decision;
                mejorPrecision = decision.precision || 0.8;
            }
        });
        
        if (mejorMatch) {
            return {
                proceso: mejorMatch.proceso,
                confianza: mejorSimilitud * mejorPrecision,
                explicacion: 'Actividad similar ("' + mejorMatch.nombre + '") mapeada a "' + mejorMatch.proceso + '"',
                regla: 'memoria_proyecto'
            };
        }
        
        return null;
    }

    /**
     * Regla 4: Similitud de Texto (Fallback)
     * Compara el nombre completo con los procesos conocidos usando Jaccard.
     */
    function reglaSimilitudTexto(actividad) {
        if (!actividad || !actividad.nombre) return null;
        
        var tokensActividad = tokenizar(actividad.nombre);
        if (tokensActividad.length === 0) return null;
        
        var mejorProceso = null;
        var mejorSimilitud = 0;
        
        REGISTRO_PROCESOS.forEach(function(proceso) {
            var tokensProceso = tokenizar(proceso);
            var similitud = similitudJaccard(tokensActividad, tokensProceso);
            
            if (similitud > mejorSimilitud) {
                mejorSimilitud = similitud;
                mejorProceso = proceso;
            }
        });
        
        if (mejorProceso && mejorSimilitud > 0.2) {
            // Normalizar confianza: 0.2 → 0.1, 1.0 → 0.9
            var confianza = 0.1 + (mejorSimilitud * 0.8);
            
            return {
                proceso: mejorProceso,
                confianza: confianza,
                explicacion: 'Similitud textual con "' + mejorProceso + '" (' + (mejorSimilitud * 100).toFixed(1) + '%)',
                regla: 'similitud_texto'
            };
        }
        
        return null;
    }

    // ==========================================
    // INTERFAZ PÚBLICA
    // ==========================================

    /**
     * Sugiere un proceso para una actividad dada.
     * @param {object} actividad - Datos de la actividad
     * @param {string} actividad.nombre - Nombre de la actividad
     * @param {number} actividad.posicion_pg - Posición en el programa general
     * @param {string[]} actividad.vecinos - Nombres de actividades vecinas
     * @param {object[]} actividad.historial - Decisiones previas
     * @returns {object} Sugerencia con proceso, confianza y explicación
     */
    function sugerir(actividad) {
        // Validar entrada
        if (!actividad || typeof actividad !== 'object') {
            return {
                proceso: null,
                confianza: 0,
                explicacion: '',
                candidatos: [],
                engine: 'rule_engine'
            };
        }
        
        // Preparar actividad con valores por defecto
        var actividadPreparada = {
            nombre: actividad.nombre || '',
            posicion_pg: actividad.posicion_pg || 0,
            vecinos: Array.isArray(actividad.vecinos) ? actividad.vecinos : [],
            historial: Array.isArray(actividad.historial) ? actividad.historial : []
        };
        
        // Si no hay nombre, retornar vacío
        if (!actividadPreparada.nombre) {
            return {
                proceso: null,
                confianza: 0,
                explicacion: '',
                candidatos: [],
                engine: 'rule_engine'
            };
        }
        
        // Ejecutar reglas en orden de prioridad
        var reglas = [
            reglaTokenDiscriminante,
            reglaVecindad,
            reglaMemoriaProyecto,
            reglaSimilitudTexto
        ];
        
        var candidatos = [];
        
        for (var i = 0; i < reglas.length; i++) {
            var resultado = reglas[i](actividadPreparada);
            
            if (resultado) {
                candidatos.push(resultado);
                
                // Si la confianza es >= 0.7, retornar inmediatamente
                if (resultado.confianza >= 0.7) {
                    return {
                        proceso: resultado.proceso,
                        confianza: Math.round(resultado.confianza * 100) / 100,
                        explicacion: resultado.explicacion,
                        candidatos: candidatos,
                        engine: 'rule_engine'
                    };
                }
            }
        }
        
        // Si ninguna regla alcanzó confianza >= 0.7, retornar la mejor sugerencia
        if (candidatos.length > 0) {
            candidatos.sort(function(a, b) {
                return b.confianza - a.confianza;
            });
            
            var mejor = candidatos[0];
            
            return {
                proceso: mejor.proceso,
                confianza: Math.round(mejor.confianza * 100) / 100,
                explicacion: mejor.explicacion,
                candidatos: candidatos,
                engine: 'rule_engine'
            };
        }
        
        // No se encontró ninguna sugerencia
        return {
            proceso: null,
            confianza: 0,
            explicacion: '',
            candidatos: [],
            engine: 'rule_engine'
        };
    }

    // Exponer interfaz pública
    return {
        sugerir: sugerir,
        tokenizar: tokenizar
    };

})();
