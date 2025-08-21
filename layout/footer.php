<?php

?>
    </div> 

    <style>
        .custom-footer-v2 {
            background-color: #212529; 
            color: rgba(255, 255, 255, 0.75);
            padding-top: 2rem;
            padding-bottom: 2rem;
            font-size: 0.9em;
            border-top: 1px solid #343a40;
        }
        .custom-footer-v2 .container {
            max-width: 1140px; 
            margin-left: auto;
            margin-right: auto;
            padding-left: 15px;
            padding-right: 15px;
        }
        .custom-footer-v2 .row {
            display: flex;
            flex-wrap: wrap;
            margin-left: -15px; 
            margin-right: -15px; 
        }
        .custom-footer-v2 .col {
            padding-left: 15px;
            padding-right: 15px;
            margin-bottom: 1.5rem; 
            width: 100%;
        }

        @media (min-width: 768px) { 
            .custom-footer-v2 .col-md-6 { flex: 0 0 50%; max-width: 50%; }
            .custom-footer-v2 .col-md-12 { flex: 0 0 100%; max-width: 100%; }
        }
        @media (min-width: 992px) { 
            .custom-footer-v2 .col-lg-4 { flex: 0 0 33.33333%; max-width: 33.33333%; }
            .custom-footer-v2 .col-lg-2 { flex: 0 0 16.66667%; max-width: 16.66667%; }
            .custom-footer-v2 .col-lg-6 { flex: 0 0 50%; max-width: 50%; }
            .custom-footer-v2 .text-lg-start { text-align: left !important; }
            .custom-footer-v2 .text-lg-end { text-align: right !important; }
            .custom-footer-v2 .justify-content-lg-start { justify-content: flex-start !important; }
        }
        
        .custom-footer-v2 .text-center { text-align: center !important; }
        .custom-footer-v2 .d-flex { display: flex !important; }
        .custom-footer-v2 .align-items-center { align-items: center !important; }
        .custom-footer-v2 .justify-content-center { justify-content: center !important; }
        .custom-footer-v2 .mb-3 { margin-bottom: 1rem !important; }
        .custom-footer-v2 .me-3 { margin-right: 1rem !important; }
        .custom-footer-v2 .me-2 { margin-right: 0.5rem !important; }
        .custom-footer-v2 .mb-0 { margin-bottom: 0 !important; }
        .custom-footer-v2 .mb-2 { margin-bottom: 0.5rem !important; }
        .custom-footer-v2 .mt-1 { margin-top: 0.25rem !important; }
        .custom-footer-v2 .mt-3 { margin-top: 1rem !important; }
        .custom-footer-v2 .py-4 { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }


        .custom-footer-v2 .text-info { color: #0dcaf0 !important; }
        .custom-footer-v2 .text-muted { color: #6c757d !important; }
        .custom-footer-v2 .text-light { color: rgba(255, 255, 255, 0.75) !important; }
        .custom-footer-v2 .text-decoration-none { text-decoration: none !important; }
        .custom-footer-v2 .small { font-size: 0.875em; }
        .custom-footer-v2 .text-uppercase { text-transform: uppercase !important; }
        .custom-footer-v2 .font-monospace { font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        .custom-footer-v2 .display-6-custom { font-size: 2.5rem; font-weight: 300; line-height: 1.2; }


        .custom-footer-v2 .list-unstyled { list-style: none; padding-left: 0; }
        .custom-footer-v2 .fa-2x { font-size: 2em; }
        
        .custom-footer-v2 .badge-custom {
            display: inline-block;
            padding: .35em .65em;
            font-size: .75em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: .375rem; 
        }
        .custom-footer-v2 .bg-secondary-custom { background-color: #6c757d !important; }

        .custom-footer-v2 .progress-custom {
            display: flex;
            height: 8px; 
            overflow: hidden;
            font-size: .75rem;
            background-color: rgba(108,117,125,0.25); 
            border-radius: .375rem; 
            max-width: 250px; 
            margin-left: auto; 
            margin-right: auto; 
        }
        .custom-footer-v2 .progress-bar-custom {
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            background-color: #0dcaf0; 
            transition: width .6s ease;
            height: 100%;
            border-radius: .375rem;
        }

        .custom-footer-v2 hr { margin-top: 1.5rem; margin-bottom: 1.5rem; border: 0; border-top: 1px solid #343a40; }
        
        .custom-footer-v2 .btn-custom {
            display: inline-block;
            font-weight: 400;
            line-height: 1.5;
            color: #adb5bd; 
            text-align: center;
            text-decoration: none;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            background-color: transparent;
            border: 1px solid #6c757d; 
            padding: .25rem .5rem; 
            font-size: .875rem; 
            border-radius: .25rem; 
            transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }
        .custom-footer-v2 .btn-custom:hover { color: #fff; background-color: #6c757d; border-color: #6c757d;}


        .custom-modal-v2 {
            display: none; 
            position: fixed;
            z-index: 1050; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            outline: 0;
            background-color: rgba(0,0,0,0.5);
        }
        .custom-modal-dialog-v2 {
            position: relative;
            width: auto;
            margin: .5rem;
            pointer-events: none;
            max-width: 320px; 
        }
        @media (min-width: 576px) {
            .custom-modal-dialog-v2 { max-width: 320px; margin: 1.75rem auto; }
        }
        .custom-modal-content-v2 {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            pointer-events: auto;
            background-color: #212529; 
            background-clip: padding-box;
            border: 1px solid rgba(0,0,0,.2);
            border-radius: .5rem; 
            outline: 0;
            color: rgba(255,255,255,0.75);
        }
        .custom-modal-header-v2 {
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1rem;
            border-bottom: 1px solid #343a40; 
            border-top-left-radius: calc(.5rem - 1px);
            border-top-right-radius: calc(.5rem - 1px);
        }
        .custom-modal-title-v2 { margin-bottom: 0; line-height: 1.5; color: #0dcaf0; }
        .custom-btn-close-v2 {
            box-sizing: content-box;
            width: 1em;
            height: 1em;
            padding: .25em .25em;
            color: #fff;
            background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
            border: 0;
            border-radius: .375rem;
            opacity: .75;
            cursor: pointer;
        }
        .custom-btn-close-v2:hover { opacity: 1; }
        .custom-modal-body-v2 {
            position: relative;
            flex: 1 1 auto;
            padding: 1rem;
            background-color: rgba(108,117,125,0.1); 
        }
        .calc-display-area {
            background-color: #212529; 
            padding: .75rem; 
            border-radius: .375rem; 
            margin-bottom: 1rem; 
            text-align: right; 
            color: rgba(255,255,255,0.75);
            min-height: 90px; 
            overflow-x: auto; 
            word-wrap: break-word;
        }
        .calc-history { color: #6c757d; font-size: 0.8em; min-height: 1.2em; }
        .calc-display { font-size: 1.5rem; min-height: 1.5em; }

        .calculator-grid-v2 { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; }
        .calc-btn-v2 {
            padding: .5rem;
            font-size: 1rem;
            border-radius: .375rem;
            border: 1px solid transparent;
            cursor: pointer;
            color: #fff;
            background-color: #6c757d; 
            border-color: #6c757d;
            transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }
        .calc-btn-v2:hover { background-color: #5a6268; border-color: #545b62; }
        .calc-btn-v2.danger { background-color: #dc3545; border-color: #dc3545; }
        .calc-btn-v2.danger:hover { background-color: #c82333; border-color: #bd2130; }
        .calc-btn-v2.warning { background-color: #ffc107; border-color: #ffc107; color:#212529;}
        .calc-btn-v2.warning:hover { background-color: #e0a800; border-color: #d39e00; }
        .calc-btn-v2.primary { background-color: #0d6efd; border-color: #0d6efd; }
        .calc-btn-v2.primary:hover { background-color: #0b5ed7; border-color: #0a58ca; }

    </style>

    <footer class="custom-footer-v2">
        <div class="container">
            <div class="row">

                <div class="col col-lg-4 col-md-6 text-center text-lg-start">
                    <div class="d-flex justify-content-center justify-content-lg-start align-items-center mb-3">
                        <i class="fas fa-address-book fa-2x text-info me-3"></i>
                        <div>
                            <h5 class="text-info mb-0">Gestión Liconsa</h5>
                            <small class="text-muted">Sistema Operativo</small>
                        </div>
                    </div>
                    <p class="small text-muted">
                        Plataforma integral para la administración y seguimiento de recursos comunitarios.
                    </p>
                </div>

                <div class="col col-lg-2 col-md-6 text-center text-lg-start">
                    <h6 class="text-uppercase text-info mb-3">Accesos</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none small"><i class="fas fa-chart-line me-2"></i>Estado Sistema</a></li>
                        <li class="mb-2"><a href="#" class="text-light text-decoration-none small"><i class="fas fa-plus-circle me-2"></i>Nuevo Reporte</a></li>
                        <li><a href="#" class="text-light text-decoration-none small"><i class="fas fa-book me-2"></i>Documentación</a></li>
                    </ul>
                </div>

                <div class="col col-lg-6 col-md-12 text-center text-lg-end">
                     <div class="mb-3">
                         <span class="badge-custom bg-secondary-custom font-monospace me-2">UTC-6</span>
                         <span id="footer-real-time-date" class="small"></span>
                     </div>
                     <div id="footer-real-time-clock" class="display-6-custom text-info font-monospace">--:--:-- --</div>
                     <div class="progress-custom mt-3">
                         <div id="footer-system-usage-bar" class="progress-bar-custom" role="progressbar" style="width: 65%;" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                     </div>
                     <div class="text-muted small mt-1">Uso del Sistema: <span id="footer-system-usage-percentage">65%</span></div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col col-md-6 text-center text-md-start">
                    <p class="small text-muted mb-0">
                        &copy; <?php echo date("Y"); ?> Gestión Liconsa. Build <?php echo date('Y.m.d'); ?> v1.2.1
                    </p>
                </div>
                <div class="col col-md-6 text-center text-md-end">
                    <button class="btn-custom me-2" title="Cambiar Tema (No implementado)">
                        <i class="fas fa-sun"></i>
                    </button>
                     <button class="btn-custom" title="Notificaciones (No implementado)">
                        <i class="fas fa-bell"></i>
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <div id="calculatorModal" class="custom-modal-v2">
        <div class="custom-modal-dialog-v2">
            <div class="custom-modal-content-v2">
                <div class="custom-modal-header-v2">
                    <h5 class="custom-modal-title-v2"><i class="fas fa-calculator me-2"></i>Calculadora</h5>
                    <button type="button" class="custom-btn-close-v2" aria-label="Close" onclick="document.getElementById('calculatorModal').style.display='none'"></button>
                </div>
                <div class="custom-modal-body-v2">
                    <div class="calc-display-area">
                        <div id="calc-history" class="calc-history"></div>
                        <div id="calc-display" class="calc-display">0</div>
                    </div>
                    <div class="calculator-grid-v2">
                        <button class="calc-btn-v2 danger" data-action="clear-all">AC</button>
                        <button class="calc-btn-v2" data-action="negate">±</button>
                        <button class="calc-btn-v2" data-action="percentage">%</button>
                        <button class="calc-btn-v2 warning" data-operator="÷">÷</button>
                        <button class="calc-btn-v2" data-number="7">7</button>
                        <button class="calc-btn-v2" data-number="8">8</button>
                        <button class="calc-btn-v2" data-number="9">9</button>
                        <button class="calc-btn-v2 warning" data-operator="×">×</button>
                        <button class="calc-btn-v2" data-number="4">4</button>
                        <button class="calc-btn-v2" data-number="5">5</button>
                        <button class="calc-btn-v2" data-number="6">6</button>
                        <button class="calc-btn-v2 warning" data-operator="-">−</button>
                        <button class="calc-btn-v2" data-number="1">1</button>
                        <button class="calc-btn-v2" data-number="2">2</button>
                        <button class="calc-btn-v2" data-number="3">3</button>
                        <button class="calc-btn-v2 warning" data-operator="+">+</button>
                        <button class="calc-btn-v2" data-action="backspace"><i class="fas fa-backspace"></i></button>
                        <button class="calc-btn-v2" data-number="0">0</button>
                        <button class="calc-btn-v2" data-action="decimal">.</button>
                        <button class="calc-btn-v2 primary" data-action="equals">=</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            function updateDateTimeFooter() {
                const dateOptions = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
                const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
                const now = new Date();
                const dateElement = document.getElementById('footer-real-time-date');
                const clockElement = document.getElementById('footer-real-time-clock');
                if (dateElement) { dateElement.textContent = now.toLocaleDateString('es-MX', dateOptions).replace(/^\w/, c => c.toUpperCase()); }
                if (clockElement) { clockElement.textContent = now.toLocaleTimeString('es-MX', timeOptions).toUpperCase(); }
            }
            function updateSystemMetricsFooter() {
                const progressBar = document.getElementById('footer-system-usage-bar');
                const percentageText = document.getElementById('footer-system-usage-percentage');
                if (progressBar && percentageText) { const randomUsage = Math.floor(Math.random() * 20 + 60); progressBar.style.width = `${randomUsage}%`; percentageText.textContent = `${randomUsage}%`; }
            }

            if (document.getElementById('footer-real-time-date') && document.getElementById('footer-real-time-clock')) {
                setInterval(updateDateTimeFooter, 1000); updateDateTimeFooter();
            }
            if (document.getElementById('footer-system-usage-bar') && document.getElementById('footer-system-usage-percentage')) {
                setInterval(updateSystemMetricsFooter, 5000); updateSystemMetricsFooter();
            }
            
            const calcDisplay = document.getElementById('calc-display');
            const calcHistory = document.getElementById('calc-history');
            const calcButtons = document.querySelectorAll('.calc-btn-v2');

            let calcCurrentOperand = '0';
            let calcPreviousOperand = '';
            let calcOperation = undefined;
            let calcShouldResetScreen = false;
            const CALC_MAX_DISPLAY_LENGTH = 14;

            function calcClearAll() { 
                calcCurrentOperand = '0'; calcPreviousOperand = ''; calcOperation = undefined; calcShouldResetScreen = false; calcUpdateDisplay();
            }
            function calcBackspace() { 
                if (calcShouldResetScreen) { calcClearAll(); return; } calcCurrentOperand = calcCurrentOperand.length > 1 ? calcCurrentOperand.slice(0, -1) : '0'; calcUpdateDisplay();
            }
            function calcAppendNumber(number) { 
                if (calcShouldResetScreen) { calcCurrentOperand = ''; calcShouldResetScreen = false; } if (calcCurrentOperand === '0' && number !== '.') calcCurrentOperand = ''; if (calcCurrentOperand === '0' && number === '0') return; if (calcCurrentOperand.replace('.', '').length >= CALC_MAX_DISPLAY_LENGTH && number !== '.') return; calcCurrentOperand += number; calcUpdateDisplay();
            }
            function calcAppendDecimal() { 
                if (calcShouldResetScreen) { calcCurrentOperand = '0'; calcShouldResetScreen = false; } if (calcCurrentOperand.includes('.')) return; if (calcCurrentOperand === '') calcCurrentOperand = '0'; calcCurrentOperand += '.'; calcUpdateDisplay();
            }
            function calcChooseOperation(selectedOperation) { 
                if (calcCurrentOperand === '' && calcPreviousOperand === '') return; if (calcCurrentOperand === '' && calcOperation) { calcOperation = selectedOperation; calcUpdateDisplay(); return; } if (calcPreviousOperand !== '' && !calcShouldResetScreen) { calcCompute(); } calcOperation = selectedOperation; calcPreviousOperand = calcCurrentOperand; calcShouldResetScreen = true; calcUpdateDisplay();
            }
            function calcCompute() { 
                let computation; const prev = parseFloat(calcPreviousOperand); let current = parseFloat(calcCurrentOperand);
                if (isNaN(current) && calcOperation && !isNaN(prev)) { current = prev; } else if (isNaN(prev) || isNaN(current)) { return; }
                switch (calcOperation) {
                    case '+': computation = prev + current; break;
                    case '-': computation = prev - current; break;
                    case '×': computation = prev * current; break;
                    case '÷': if (current === 0) { calcShowError("Div/0"); return; } computation = prev / current; break;
                    default: return;
                }
                const historyText = `${calcFormatForDisplay(calcPreviousOperand)} ${calcOperation} ${calcFormatForDisplay(current)} =`;
                calcCurrentOperand = calcFormatComputationResult(computation); calcOperation = undefined; calcPreviousOperand = ''; calcShouldResetScreen = true; calcUpdateDisplay(historyText);
            }
            function calcFormatComputationResult(number) { 
                if (Math.abs(number) > 1e14 || (Math.abs(number) < 1e-7 && number !== 0)) { return number.toExponential(7); } const rounded = parseFloat(number.toPrecision(12)); return String(rounded);
            }
            function calcNegate() { 
                if (calcCurrentOperand === '0' || calcCurrentOperand === '') return; calcCurrentOperand = (parseFloat(calcCurrentOperand) * -1).toString(); calcUpdateDisplay();
            }
            function calcPercentage() { 
                if (calcCurrentOperand === '0' || calcCurrentOperand === '') return; let result;
                if (calcPreviousOperand && calcOperation) { const prev = parseFloat(calcPreviousOperand); result = prev * (parseFloat(calcCurrentOperand) / 100); } else { result = parseFloat(calcCurrentOperand) / 100; }
                calcCurrentOperand = calcFormatComputationResult(result); calcShouldResetScreen = true; calcUpdateDisplay();
            }
            function calcFormatForDisplay(operand) { 
                if (operand == null || operand === '') return '0'; let stringNumber = String(operand); if (stringNumber.toLowerCase().includes('e')) { return stringNumber; } let [integerPart, decimalPart] = stringNumber.split('.'); let formattedInteger = parseFloat(integerPart).toLocaleString('es-MX', { maximumFractionDigits: 0 }); let formattedNumber = formattedInteger; if (decimalPart != null) { formattedNumber += '.' + decimalPart; } if (formattedNumber.length > CALC_MAX_DISPLAY_LENGTH + 4) { return parseFloat(stringNumber).toExponential(7); } return formattedNumber;
            }
            function calcUpdateDisplay(historyText = null) { 
                if (!calcDisplay || !calcHistory) return; calcDisplay.textContent = calcFormatForDisplay(calcCurrentOperand);
                if (historyText) { calcHistory.textContent = historyText; } else if (calcOperation != null && calcPreviousOperand !== '') { calcHistory.textContent = `${calcFormatForDisplay(calcPreviousOperand)} ${calcOperation}`; } else { calcHistory.textContent = ''; }
            }
            function calcShowError(message) { 
                if (!calcDisplay || !calcHistory) return; calcDisplay.textContent = message; calcHistory.textContent = ''; calcShouldResetScreen = true; 
            }

            if (calcDisplay && calcHistory && calcButtons.length > 0) {
                calcButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        const action = button.dataset.action; const number = button.dataset.number; const op = button.dataset.operator;
                        if (number !== undefined) { calcAppendNumber(number); }
                        else if (op !== undefined) { calcChooseOperation(op); }
                        else if (action === 'decimal') { calcAppendDecimal(); }
                        else if (action === 'equals') { calcCompute(); }
                        else if (action === 'clear-all') { calcClearAll(); }
                        else if (action === 'backspace') { calcBackspace(); }
                        else if (action === 'negate') { calcNegate(); }
                        else if (action === 'percentage') { calcPercentage(); }
                    });
                });
                calcClearAll(); 
            }
        });
    </script>
</body>
</html>
