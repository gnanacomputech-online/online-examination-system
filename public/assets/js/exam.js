(function () {
    'use strict';

    var data = window.EXAM_DATA;
    if (!data) return;

    var cards = Array.prototype.slice.call(document.querySelectorAll('.q-card'));
    var navDots = Array.prototype.slice.call(document.querySelectorAll('.nav-dot'));
    var current = 0;

    function showQuestion(index) {
        if (index < 0 || index >= cards.length) return;
        cards[current].hidden = true;
        navDots[current].classList.remove('active');
        current = index;
        cards[current].hidden = false;
        navDots[current].classList.add('active');
    }

    navDots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            showQuestion(parseInt(dot.getAttribute('data-index'), 10));
        });
    });

    var prevBtn = document.getElementById('prev-btn');
    var nextBtn = document.getElementById('next-btn');
    if (prevBtn) prevBtn.addEventListener('click', function () { showQuestion(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { showQuestion(current + 1); });

    function markAnswered(qid) {
        var dot = document.querySelector('.nav-dot[data-qid="' + qid + '"]');
        if (dot) dot.classList.add('answered');
    }

    function autosave(qid, selectedOptionIds, answerText) {
        markAnswered(qid);
        var body = new URLSearchParams();
        body.set('csrf_token', data.csrfToken);
        body.set('attempt_id', data.attemptId);
        body.set('question_id', qid);
        if (selectedOptionIds !== null) body.set('selected_option_ids', selectedOptionIds);
        if (answerText !== null) body.set('answer_text', answerText);

        fetch(data.autosaveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
            credentials: 'same-origin'
        }).then(function (res) { return res.json(); })
          .then(handleExpiry)
          .catch(function () { /* best-effort autosave */ });
    }

    // The server may report the attempt already expired (time ran out while the tab was
    // backgrounded); it has force-graded by then, so send the student to their result.
    function handleExpiry(json) {
        if (!json || !json.expired) return;
        window.removeEventListener('beforeunload', confirmLeave);
        window.location.href = '/index.php?route=student/results/view&attempt_id=' + data.attemptId;
    }

    cards.forEach(function (card) {
        var qid = card.getAttribute('data-qid');
        var type = card.getAttribute('data-type');

        if (type === 'single' || type === 'true_false') {
            card.querySelectorAll('input[type="radio"]').forEach(function (input) {
                input.addEventListener('change', function () {
                    autosave(qid, input.value, null);
                });
            });
        } else if (type === 'multi') {
            card.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
                input.addEventListener('change', function () {
                    var checked = Array.prototype.slice.call(card.querySelectorAll('input[type="checkbox"]:checked'))
                        .map(function (i) { return i.value; });
                    autosave(qid, checked.join(','), null);
                });
            });
        } else {
            var textInput = card.querySelector('.fill-blank-input');
            if (textInput) {
                var timer = null;
                textInput.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        autosave(qid, null, textInput.value);
                    }, 600);
                });
            }
        }
    });

    // Timer
    var remaining = data.remainingSeconds;
    var timerEl = document.getElementById('exam-timer');
    var submitForm = document.getElementById('submit-form');
    var submitBtn = document.getElementById('submit-btn');
    var submitted = false;

    // One submission per attempt: the student's own click and the timer hitting zero can
    // otherwise both POST. The server grades under a compare-and-swap anyway, but the
    // button should visibly stop responding rather than fire twice.
    function submitOnce() {
        if (submitted) return;
        submitted = true;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting…';
        window.removeEventListener('beforeunload', confirmLeave);
        submitForm.submit();
    }

    function renderTimer() {
        var m = Math.floor(remaining / 60);
        var s = remaining % 60;
        timerEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        timerEl.classList.toggle('timer-warning', remaining <= 60);
    }
    renderTimer();

    var interval = setInterval(function () {
        remaining -= 1;
        if (remaining <= 0) {
            clearInterval(interval);
            timerEl.textContent = '00:00';
            submitOnce();
            return;
        }
        renderTimer();
    }, 1000);

    // Submit
    submitBtn.addEventListener('click', function () {
        if (submitted) return;
        if (confirm('Submit the exam now? You will not be able to change your answers after submitting.')) {
            submitOnce();
        }
    });

    // Tab-switch / focus-loss integrity logging, with an in-page warning shown as soon as
    // the student comes back to the tab (they can't see anything while it's hidden), and a
    // hard forced end-of-exam once the violation count crosses the server-enforced threshold.
    var violationOverlay = document.getElementById('violation-overlay');
    var violationBox = document.getElementById('violation-box');
    var violationTitle = document.getElementById('violation-title');
    var violationMessage = document.getElementById('violation-message');
    var violationDismiss = document.getElementById('violation-dismiss');

    function showViolationWarning(count, threshold) {
        violationBox.classList.remove('disqualified');
        violationTitle.textContent = 'Tab switch detected (' + count + '/' + threshold + ')';
        violationMessage.textContent = 'We detected you switching away from this tab/screen. Do not do that — ' +
            'switching away is logged as a violation. If you reach ' + threshold + ' violations you will be ' +
            'automatically disqualified and the exam will end.';
        violationOverlay.classList.remove('hidden');
    }

    function showDisqualifiedOverlay() {
        submitBtn.disabled = true;
        cards.forEach(function (card) {
            card.querySelectorAll('input, textarea').forEach(function (el) { el.disabled = true; });
        });
        violationBox.classList.add('disqualified');
        violationTitle.textContent = 'Disqualified';
        violationMessage.textContent = 'You exceeded the allowed number of tab-switch violations. Your exam has ' +
            'been submitted as-is and the attempt is now closed. Redirecting to your result…';
        violationOverlay.classList.remove('hidden');
    }

    if (violationDismiss) {
        violationDismiss.addEventListener('click', function () {
            violationOverlay.classList.add('hidden');
        });
    }

    function handleViolationResponse(json) {
        if (!json) return;
        if (json.expired) { handleExpiry(json); return; }
        if (!json.ok) return;

        if (json.disqualified) {
            submitted = true;
            window.removeEventListener('beforeunload', confirmLeave);
            showDisqualifiedOverlay();
            setTimeout(function () {
                window.location.href = json.redirect || ('/index.php?route=student/results/view&attempt_id=' + data.attemptId);
            }, 2500);
            return;
        }

        showViolationWarning(json.violation_count, json.threshold);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            var body = new URLSearchParams();
            body.set('csrf_token', data.csrfToken);
            body.set('attempt_id', data.attemptId);
            fetch(data.violationUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
                credentials: 'same-origin'
            }).then(function (res) { return res.json(); })
              .then(handleViolationResponse)
              .catch(function () {});
        }
    });

    function confirmLeave(e) {
        e.preventDefault();
        e.returnValue = '';
    }
    window.addEventListener('beforeunload', confirmLeave);
})();
