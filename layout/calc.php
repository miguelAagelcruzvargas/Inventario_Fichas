<?php
// calc.php
// Este archivo simplemente sirve el HTML de la calculadora.
// No se requiere lógica PHP compleja para una calculadora del lado del cliente.
header("Content-Type: text/html; charset=utf-8"); // Asegurar UTF-8 para caracteres especiales
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora Moderna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            -webkit-tap-highlight-color: transparent; /* Eliminar resaltado de toque en móviles */
        }
        .calculator-body {
            background: linear-gradient(145deg, #2c3e50, #34495e); /* Gradiente oscuro para el cuerpo */
            box-shadow: 15px 15px 30px rgba(0,0,0,0.3), 
                        -15px -15px 30px rgba(255,255,255,0.03),
                        inset 0 0 0 transparent, /* Evitar doble inset si se usan sombras inset en hijos */
                        inset 0 0 0 transparent;
            transition: box-shadow 0.3s ease-in-out;
        }
        .calculator-body:hover { /* Sutil efecto al pasar el mouse por encima del cuerpo */
             box-shadow: 20px 20px 40px rgba(0,0,0,0.35), 
                        -20px -20px 40px rgba(255,255,255,0.04);
        }

        .calculator-display-container {
            background: rgba(0, 0, 0, 0.25); /* Fondo semitransparente para el display */
            border: 1px solid rgba(0,0,0,0.35);
            box-shadow: inset 3px 3px 6px #202b38, 
                        inset -3px -3px 6px #485f78;
            word-wrap: break-word;
            word-break: break-all; /* Para que números largos no rompan el layout */
        }
        #display-content { /* Contenedor interno para el texto del display */
            min-height: 1em; /* Asegurar altura mínima */
        }
        #history {
            opacity: 0.7;
            min-height: 1.2em; /* Espacio para el historial */
        }

        .calc-button {
            background: linear-gradient(145deg, #314152, #2a3745); /* Botones más oscuros */
            color: #ecf0f1; /* Texto claro */
            border: 1px solid rgba(0,0,0,0.2);
            transition: all 0.1s ease-in-out;
            box-shadow: 4px 4px 8px #222e3a, 
                        -4px -4px 8px #40546a;
            font-weight: 500;
        }
        .calc-button:hover {
            background: linear-gradient(145deg, #374a5d, #2d3c4b); /* Un poco más claro al pasar el mouse */
            border-color: rgba(0,0,0,0.3);
            color: #fff;
        }
        .calc-button:active {
            background: linear-gradient(145deg, #2a3745, #314152); /* Invertir gradiente al presionar */
            box-shadow: inset 3px 3px 6px #222e3a, 
                        inset -3px -3px 6px #40546a;
            transform: translateY(1px) translateX(1px); /* Efecto de presión */
        }

        /* Botones de operadores y de igual */
        .calc-button.operator, .calc-button.equals {
            background: linear-gradient(145deg, #e67e22, #d35400); /* Naranja distintivo */
            border-color: #b84d00;
             box-shadow: 4px 4px 8px #a14000, 
                        -4px -4px 8px #ff9b45;
            color: #ffffff;
        }
        .calc-button.operator:hover, .calc-button.equals:hover {
            background: linear-gradient(145deg, #f39c12, #e67e22);
        }
        .calc-button.operator:active, .calc-button.equals:active {
            background: linear-gradient(145deg, #d35400, #e67e22);
            box-shadow: inset 3px 3px 6px #a14000, 
                        inset -3px -3px 6px #ff9b45;
        }

        /* Botones de limpieza */
        .calc-button.clear, .calc-button.backspace {
             background: linear-gradient(145deg, #c0392b, #a52a1a); /* Rojizo */
             border-color: #8c2015;
             box-shadow: 4px 4px 8px #7e1b10, 
                        -4px -4px 8px #ec5a49;
             color: #ffffff;
        }
        .calc-button.clear:hover, .calc-button.backspace:hover {
            background: linear-gradient(145deg, #e74c3c, #c0392b);
        }
        .calc-button.clear:active, .calc-button.backspace:active {
            background: linear-gradient(145deg, #a52a1a, #c0392b);
            box-shadow: inset 3px 3px 6px #7e1b10, 
                        inset -3px -3px 6px #ec5a49;
        }

        /* Scrollbar personalizado para el display (opcional) */
        .calculator-display-container::-webkit-scrollbar {
            height: 4px; /* Scrollbar horizontal */
        }
        .calculator-display-container::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .calculator-display-container::-webkit-scrollbar-thumb {
            background: #e67e22; 
            border-radius: 10px;
        }
        .calculator-display-container::-webkit-scrollbar-thumb:hover {
            background: #f39c12;
        }
        .calc-button i { /* Centrar íconos verticalmente */
            vertical-align: middle;
        }
    </style>
</head>
<body class="bg-gray-800 flex items-center justify-center min-h-screen p-4 select-none">

    <div class="calculator-body w-full max-w-xs sm:max-w-sm md:max-w-[360px] rounded-3xl p-4 sm:p-5">
        <div class="calculator-display-container h-28 sm:h-32 text-right text-white p-3 sm:p-4 mb-4 rounded-xl overflow-hidden flex flex-col justify-end">
            <div id="history" class="text-sm sm:text-base text-gray-400 h-1/3 truncate"></div>
            <div id="current-operand" class="text-3xl sm:text-4xl font-medium h-2/3 truncate flex items-end justify-end">
                <span id="display-content">0</span>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-2 sm:gap-3">
            <button data-action="clear-all" class="calc-button clear text-lg sm:text-xl py-3 sm:py-4 rounded-lg">AC</button>
            <button data-action="negate" class="calc-button text-lg sm:text-xl py-3 sm:py-4 rounded-lg">±</button>
            <button data-action="percentage" class="calc-button text-lg sm:text-xl py-3 sm:py-4 rounded-lg">%</button>
            <button data-operator="÷" class="calc-button operator text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">÷</button>

            <button data-number="7" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">7</button>
            <button data-number="8" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">8</button>
            <button data-number="9" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">9</button>
            <button data-operator="×" class="calc-button operator text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">×</button>

            <button data-number="4" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">4</button>
            <button data-number="5" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">5</button>
            <button data-number="6" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">6</button>
            <button data-operator="-" class="calc-button operator text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">−</button>

            <button data-number="1" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">1</button>
            <button data-number="2" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">2</button>
            <button data-number="3" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">3</button>
            <button data-operator="+" class="calc-button operator text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">+</button>

            <button data-action="backspace" class="calc-button backspace text-lg sm:text-xl py-3 sm:py-4 rounded-lg"><i class="fas fa-backspace"></i></button>
            <button data-number="0" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">0</button>
            <button data-action="decimal" class="calc-button text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">.</button>
            <button data-action="equals" class="calc-button equals text-xl sm:text-2xl py-3 sm:py-4 rounded-lg">=</button>
        </div>
    </div>

    <script>
        const displayContent = document.getElementById('display-content');
        const displayHistory = document.getElementById('history');
        const buttons = document.querySelectorAll('.calc-button');

        let currentOperand = '0';
        let previousOperand = '';
        let operation = undefined;
        let shouldResetScreen = false;
        const MAX_DISPLAY_LENGTH = 14; // Longitud máxima para el display principal

        function clearAll() {
            currentOperand = '0';
            previousOperand = '';
            operation = undefined;
            shouldResetScreen = false;
            updateDisplay();
        }
        
        function backspace() {
            if (shouldResetScreen) { // Si se debe resetear, AC es más apropiado o limpiar el resultado
                clearAll(); // Opcional: o simplemente currentOperand = '0'; shouldResetScreen = false;
                return;
            }
            if (currentOperand.length > 1) {
                currentOperand = currentOperand.slice(0, -1);
            } else {
                currentOperand = '0';
            }
            updateDisplay();
        }

        function appendNumber(number) {
            if (shouldResetScreen) {
                currentOperand = '';
                shouldResetScreen = false;
            }
            if (currentOperand === '0' && number !== '.') currentOperand = ''; // Evitar 01, 02, etc.
            
            // Prevenir múltiples ceros al inicio si no hay decimal
            if (currentOperand === '0' && number === '0') return;


            if (currentOperand.replace('.', '').length >= MAX_DISPLAY_LENGTH && number !== '.') return;

            currentOperand += number;
            updateDisplay();
        }
        
        function chooseOperation(selectedOperation) {
            if (currentOperand === '' && previousOperand === '') return;

            if (currentOperand === '' && operation) { // Cambiar operador si no hay nueva entrada
                operation = selectedOperation;
                updateDisplay();
                return;
            }
            
            if (previousOperand !== '' && !shouldResetScreen) { // Si hay operando anterior y no se acaba de calcular
                compute();
            }
            
            operation = selectedOperation;
            previousOperand = currentOperand;
            shouldResetScreen = true; // La próxima entrada de número debe iniciar un nuevo operando
            // currentOperand no se limpia aquí, se mostrará como resultado parcial hasta nueva entrada
            updateDisplay();
        }

        function compute() {
            let computation;
            const prev = parseFloat(previousOperand);
            const current = parseFloat(currentOperand);

            if (isNaN(prev) || (operation && isNaN(current))) { // Si no hay current y hay operación, usar prev como current
                 if (operation && isNaN(current) && !isNaN(prev)) { // ej. 5 * = (5*5)
                    currentOperand = previousOperand;
                 } else {
                    return;
                 }
            }
            const currentVal = parseFloat(currentOperand); // Re-parse currentOperand

            switch (operation) {
                case '+': computation = prev + currentVal; break;
                case '-': computation = prev - currentVal; break;
                case '×': computation = prev * currentVal; break;
                case '÷':
                    if (currentVal === 0) {
                        showError("División por cero");
                        return;
                    }
                    computation = prev / currentVal;
                    break;
                default: return;
            }
            currentOperand = formatComputationResult(computation);
            operation = undefined; // Operación completada
            // previousOperand se queda con el valor anterior para el historial de "equals"
            shouldResetScreen = true; 
        }
        
        function formatComputationResult(number) {
            // Limitar la precisión para evitar números excesivamente largos
            // y manejar notación científica para números muy grandes o pequeños
            if (Math.abs(number) > 1e14 || (Math.abs(number) < 1e-7 && number !== 0) ) {
                return number.toExponential(7); // 7 decimales en notación científica
            }
            // Redondear a un número razonable de decimales si es necesario
            const rounded = parseFloat(number.toPrecision(12)); // Precisión general
            return String(rounded);
        }

        function negate() {
            if (currentOperand === '0' || currentOperand === '') return;
            currentOperand = (parseFloat(currentOperand) * -1).toString();
            updateDisplay();
        }
        
        function percentage() {
            if (currentOperand === '0' || currentOperand === '') return;
            if (previousOperand && operation) {
                // Calcular porcentaje del operando anterior: ej. 100 + 10% (de 100)
                const prev = parseFloat(previousOperand);
                currentOperand = (prev * (parseFloat(currentOperand) / 100)).toString();
            } else {
                // Calcular porcentaje del número actual: ej. 50% (0.5)
                currentOperand = (parseFloat(currentOperand) / 100).toString();
            }
            shouldResetScreen = true; // El resultado del % es final para esta entrada
            updateDisplay();
        }

        function appendDecimal() {
            if (shouldResetScreen) {
                currentOperand = '0'; // Iniciar nuevo número con 0.
                shouldResetScreen = false;
            }
            if (currentOperand.includes('.')) return;
            if (currentOperand === '') currentOperand = '0'; // Si está vacío, empezar con "0."
            currentOperand += '.';
            updateDisplay();
        }

        function formatForDisplay(operand) {
            if (operand == null || operand === '') return '0';
            let stringNumber = String(operand);

            // Manejar notación científica
            if (stringNumber.toLowerCase().includes('e')) {
                return stringNumber; // Mostrar tal cual
            }

            let [integerPart, decimalPart] = stringNumber.split('.');
            
            // Formatear parte entera con comas
            integerPart = parseFloat(integerPart).toLocaleString('es-MX', {maximumFractionDigits: 0});
            
            let formattedNumber = integerPart;
            if (decimalPart != null) {
                formattedNumber += '.' + decimalPart.substring(0, MAX_DISPLAY_LENGTH - integerPart.length -1);
            }
            
            // Truncar si es demasiado largo, pero priorizar mostrar el inicio del número
            if (formattedNumber.length > MAX_DISPLAY_LENGTH + 5) { // +5 para comas y punto
                 if (parseFloat(stringNumber) > 1e14 || (parseFloat(stringNumber) < 1e-7 && parseFloat(stringNumber) !== 0)) {
                    return parseFloat(stringNumber).toExponential(7);
                 }
                 return parseFloat(stringNumber).toPrecision(MAX_DISPLAY_LENGTH - 5); // Ajustar precisión
            }
            return formattedNumber;
        }
        
        function updateDisplay() {
            displayContent.textContent = formatForDisplay(currentOperand);
            
            if (operation != null && previousOperand !== '') {
                displayHistory.textContent = `${formatForDisplay(previousOperand)} ${operation}`;
            } else if (shouldResetScreen && previousOperand !== '' && currentOperand !== '') { 
                // Después de un =, mostrar la operación completa si no hay nueva operación
                 displayHistory.textContent = `${formatForDisplay(previousOperand)} ${operation || ''} ${formatForDisplay(currentOperand)} =`;
            }
             else {
                displayHistory.textContent = '';
            }
        }

        function showError(message) {
            displayContent.textContent = message;
            displayHistory.textContent = '';
            // No resetear operandos aquí para que el usuario vea el contexto del error
            shouldResetScreen = true; // Próxima entrada numérica limpiará el error
            setTimeout(() => { 
                if (displayContent.textContent === message) { 
                   clearAll(); // Limpiar completamente después de un tiempo si el error persiste
                }
            }, 2500);
        }

        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const action = button.dataset.action;
                const number = button.dataset.number;
                const op = button.dataset.operator; // Renombrado para evitar conflicto con 'operation' global

                if (number !== undefined) {
                    appendNumber(number);
                } else if (op !== undefined) {
                    chooseOperation(op);
                } else if (action === 'decimal') {
                    appendDecimal();
                } else if (action === 'equals') {
                    if (!operation || previousOperand === '' || (currentOperand === '' && !shouldResetScreen)) return;
                    const tempHistory = `${formatForDisplay(previousOperand)} ${operation} ${formatForDisplay(currentOperand)} =`;
                    compute();
                    displayHistory.textContent = tempHistory; // Mostrar la operación completa
                    // currentOperand ahora tiene el resultado, operation es undefined.
                } else if (action === 'clear-all') {
                    clearAll();
                } else if (action === 'backspace') {
                    backspace();
                } else if (action === 'negate') {
                    negate();
                } else if (action === 'percentage') {
                    percentage();
                }
                // updateDisplay() se llama dentro de la mayoría de las funciones de acción
            });
        });

        clearAll(); // Inicializar la calculadora
    </script>

</body>
</html>
