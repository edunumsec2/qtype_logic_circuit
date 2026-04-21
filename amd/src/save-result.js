/**
 * Saves the logic circuit editor state on form submission.
 *
 * @module    qtype_logiccircuit/save-result
 * @copyright  2025 Groupe Modulo
 * @license    CC BY-NC-SA
 */
define(['jquery'], function($) {
    return {
        init: function() {
            // Remove this as soon as the autosave to session storage is deactivated in the logic circuit editor
            sessionStorage.clear('logic/logic-editor');

            const nextNavButton = $('input[type="submit"]#mod_quiz-next-nav.btn');
            const resultNotUploadedIcon = $('span#result_not_uploaded');
            const newResultUploadedIcon = $('span#new_result_uploaded');

            const testResultsInput = $('input#test-results');
            const testResults = testResultsInput.attr('value');

            //console.log(testResults);

            // Block quiz progression until the user submits a result
            if(testResults === undefined || testResults.length === 0) {
                nextNavButton.prop('disabled', true);
                resultNotUploadedIcon.css('display', 'block');
                newResultUploadedIcon.css('display', 'none');
            } else {
                nextNavButton.prop('disabled', false);
                resultNotUploadedIcon.css('display', 'none');
                newResultUploadedIcon.css('display', 'block');
            }

            const runTestButton = $('button#circuit-run-test-button');

            runTestButton.on('click', () => {
                const logicEditor = $('logic-editor#logic-editor')[0];
                logicEditor.runAllCircuitTestSuites();
            });

            const resetButton = $('button#circuit-reset-button');

            resetButton.on('click', () => {
                const initState = resetButton.data('init-state');
                const logicEditor = $('logic-editor#logic-editor')[0];

                logicEditor.loadCircuitOrLibrary(initState);
            });

            const logicEditor = $('logic-editor#logic-editor')[0];

            logicEditor.addEventListener('testsinvalidated', () => {
                nextNavButton.prop('disabled', true);
                resultNotUploadedIcon.css('display', 'block');
                newResultUploadedIcon.css('display', 'none');
            });

            logicEditor.addEventListener('testsexecuted', (event) => {
                try {
                    const userAnswer = event.detail.circuit;
                    const userAnswerString = JSON.stringify(userAnswer);
                    const testSuitesResults = event.detail.results;
                    const testSuitesResultsString = JSON.stringify(testSuitesResults);

                    //console.log(userAnswer);
                    //console.log(testSuitesResults);

                    // Update the input value here
                    $('input#answer').attr('value', userAnswerString);
                    $('input#test-results').attr('value', testSuitesResultsString);
                } catch (err) {
                    throw new Error(err);
                }

                nextNavButton.prop('disabled', false);
                resultNotUploadedIcon.css('display', 'none');
                newResultUploadedIcon.css('display', 'block');
            });
        }
    };
});
