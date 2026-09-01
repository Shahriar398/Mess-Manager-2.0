document.addEventListener("DOMContentLoaded", function () {
    var inputs = document.querySelectorAll(".meal-count-input");

    inputs.forEach(function (input) {
        input.addEventListener("input", function () {
            updateRowTotal(input.getAttribute("data-member"));
        });
    });

    function updateRowTotal(memberId) {
        var breakfast = document.querySelector(
            'input[data-member="' + memberId + '"][data-type="breakfast"]'
        );
        var lunch = document.querySelector(
            'input[data-member="' + memberId + '"][data-type="lunch"]'
        );
        var dinner = document.querySelector(
            'input[data-member="' + memberId + '"][data-type="dinner"]'
        );
        var totalEl = document.getElementById("total-" + memberId);

        if (!breakfast || !lunch || !dinner || !totalEl) {
            return;
        }

        var b = parseFloat(breakfast.value) || 0;
        var l = parseFloat(lunch.value) || 0;
        var d = parseFloat(dinner.value) || 0;
        var total = Math.round((b + l + d) * 10) / 10;

        totalEl.textContent = total.toFixed(1);
        updateDayTotals();
    }

    function updateDayTotals() {
        var breakfastTotal = 0;
        var lunchTotal = 0;
        var dinnerTotal = 0;

        document.querySelectorAll('.meal-count-input[data-type="breakfast"]').forEach(function (el) {
            breakfastTotal += parseFloat(el.value) || 0;
        });
        document.querySelectorAll('.meal-count-input[data-type="lunch"]').forEach(function (el) {
            lunchTotal += parseFloat(el.value) || 0;
        });
        document.querySelectorAll('.meal-count-input[data-type="dinner"]').forEach(function (el) {
            dinnerTotal += parseFloat(el.value) || 0;
        });

        var grand = Math.round((breakfastTotal + lunchTotal + dinnerTotal) * 10) / 10;
        var bEl = document.getElementById("day-breakfast-total");
        var lEl = document.getElementById("day-lunch-total");
        var dEl = document.getElementById("day-dinner-total");
        var gEl = document.getElementById("day-grand-total");

        if (bEl) bEl.textContent = breakfastTotal.toFixed(1);
        if (lEl) lEl.textContent = lunchTotal.toFixed(1);
        if (dEl) dEl.textContent = dinnerTotal.toFixed(1);
        if (gEl) gEl.textContent = grand.toFixed(1);
    }
});
