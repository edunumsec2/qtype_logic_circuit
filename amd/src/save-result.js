/**
 * Saves the logic circuit editor state on form submission.
 *
 * @module    qtype_logiccircuit/save-result
 * @copyright  2025 Groupe Modulo
 * @license    CC BY-NC-SA
 */
define([], function () {
    const serialiseResponseValue = function (value) {
        if (typeof value === 'string') {
            return value;
        }

        return JSON.stringify(value);
    };

    return {
        init: function () {
            // console.log("initialising save-result.js");

            const showElements = (elements) => {
                for (const [name, element] of Object.entries(elements)) {
                    // console.log(`Element ${name}: `, element);
                }
            };
            
            const nextNavButton = document.querySelector('input[type="submit"]#mod_quiz-next-nav.btn');
            const parentDivs = document.querySelectorAll('.que.logiccircuit');
            const numParentDivs = parentDivs.length;

            const submittedByEditor = new Map();
            const updateNavButtonWithSubmittedState = (logicEditor, submittedState) => {
                if (nextNavButton) {
                    submittedByEditor.set(logicEditor, submittedState);
                    let totalSubmitted = 0;
                    for (const state of submittedByEditor.values()) {
                        if (state) totalSubmitted++;
                    }
                    // console.log(`Total submitted: ${totalSubmitted} / ${numParentDivs}`);
                    const allSubmitted = totalSubmitted === numParentDivs;
                    if (allSubmitted) {
                        nextNavButton.removeAttribute('disabled');
                    } else {
                        nextNavButton.setAttribute('disabled', 'true');
                    }
                }
            };

            parentDivs.forEach((parentDiv) => {
                // console.log("Configuting logic circuit editor: ", parentDiv);

                /** @type {any} */
                const logicEditor = parentDiv.querySelector('logic-editor');
                if (!logicEditor) {
                    console.error("Logic editor not found in parent div: ", parentDiv);
                    return;
                }

                const flagImage = parentDiv.querySelector('img.questionflagimage');
                const flagLink = flagImage?.closest('a');

                const isFlaggedInHTML = () => flagLink?.getAttribute('aria-pressed') === 'true';
                let flaggedByEditor = false;

                // This sets/clears the flag depending on the submitted state. If the user has flagged the question themselves, we don't want to unflag it.
                const manageFlaggedState = (flagged) => {
                    if (flagged) {
                        if (!isFlaggedInHTML()) {
                            flagLink?.click();
                            flaggedByEditor = true;
                        }
                    } else {
                        if (isFlaggedInHTML() && flaggedByEditor) {
                            flagLink?.click();
                            flaggedByEditor = false;
                        }
                    }
                }

                /** @type {HTMLElement} */
                const resultNotUploadedIcon = parentDiv.querySelector('.result_not_uploaded');
                /** @type {HTMLElement} */
                const newResultUploadedIcon = parentDiv.querySelector('.new_result_uploaded');

                /** @type {HTMLTextAreaElement} */
                const answerField = parentDiv.querySelector('.answer');

                /** @type {HTMLTextAreaElement} */
                const testResultsField = parentDiv.querySelector('.test-results');


                /** @type {function(boolean):void} */
                const setSubmittedState = (isSubmitted) => {
                    updateNavButtonWithSubmittedState(logicEditor, isSubmitted);
                    manageFlaggedState(!isSubmitted);
                    if (!isSubmitted) {
                        if (resultNotUploadedIcon) resultNotUploadedIcon.style.display = 'block';
                        if (newResultUploadedIcon) newResultUploadedIcon.style.display = 'none';
                    } else {
                        if (resultNotUploadedIcon) resultNotUploadedIcon.style.display = 'none';
                        if (newResultUploadedIcon) newResultUploadedIcon.style.display = 'block';
                    }
                };

                logicEditor.addEventListener('testsinvalidated', () => setSubmittedState(false));

                logicEditor.addEventListener('testsexecuted', (event) => {
                    if (answerField && testResultsField) {
                        try {
                            const userAnswer = event.detail.circuit;
                            const userAnswerString = serialiseResponseValue(userAnswer);
                            const testSuitesResults = event.detail.results;
                            const testSuitesResultsString = serialiseResponseValue(testSuitesResults);

                            // Update the input value here
                            answerField.value = userAnswerString;
                            testResultsField.value = testSuitesResultsString;
                        } catch (err) {
                            throw new Error(err);
                        }
                    }

                    setSubmittedState(true);
                });

                const testResults = (!testResultsField) ? "" : testResultsField.value;
                //console.log(testResults);
                const submitted = testResults !== undefined && testResults.trim().length > 0;
                setSubmittedState(submitted);

                const unlockCircuitButton = parentDiv.querySelector('.unlock-circuit-button');
                if (unlockCircuitButton) {
                    unlockCircuitButton.addEventListener('click', (evt) => {
                        logicEditor.setMode("full", false, true);
                        unlockCircuitButton.setAttribute('disabled', 'true');
                    });
                }

                const runTestButton = parentDiv.querySelector('.circuit-run-test-button');
                if (runTestButton) {
                    runTestButton.addEventListener('click', (evt) => {
                        logicEditor.runAllCircuitTestSuites();
                    });
                }


                /** @type {HTMLElement} */
                const resetButton = parentDiv.querySelector('.circuit-reset-button');
                if (resetButton) {
                    resetButton.addEventListener('click', () => {
                        const initState = resetButton.dataset.initState;
                        logicEditor.loadCircuitOrLibrary(initState);
                    });
                }


                showElements({ nextNavButton, flagLink, logicEditor, resultNotUploadedIcon, newResultUploadedIcon, testResultsField, answerField, unlockCircuitButton, runTestButton, resetButton });

            });

        }
    };
});
