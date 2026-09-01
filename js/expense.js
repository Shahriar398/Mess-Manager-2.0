// =====================================
// SHOW MEAL EXPENSE
// =====================================

function showMealExpense() {

    document
        .getElementById("mealExpenseTab")
        .classList.add("active");

    document
        .getElementById("otherExpenseTab")
        .classList.remove("active");


    document
        .getElementById("mealExpenseForm")
        .classList.add("active-form");

    document
        .getElementById("otherExpenseForm")
        .classList.remove("active-form");

}


// =====================================
// SHOW OTHER EXPENSE
// =====================================

function showOtherExpense() {

    document
        .getElementById("otherExpenseTab")
        .classList.add("active");

    document
        .getElementById("mealExpenseTab")
        .classList.remove("active");


    document
        .getElementById("otherExpenseForm")
        .classList.add("active-form");

    document
        .getElementById("mealExpenseForm")
        .classList.remove("active-form");

}


// =====================================
// UPDATE OTHER EXPENSE AMOUNT
// =====================================

function updateOtherExpenseAmount() {

    const amountInput =
        document.getElementById("otherExpenseAmount");

    const selectedAmount =
        document.getElementById("selectedExpenseAmount");

    const checkboxes =
        document.querySelectorAll(
            ".expense-member-checkbox:checked"
        );


    // Get total expense

    const totalAmount =
        parseFloat(amountInput.value) || 0;


    // Count selected members

    const memberCount =
        checkboxes.length;


    // No member selected

    if (memberCount === 0) {

        selectedAmount.innerText =
            "0.00 ৳";

        return;

    }


    // Calculate amount for each selected member

    const perMemberAmount =
        totalAmount / memberCount;


    // Display amount

    selectedAmount.innerText =
        perMemberAmount.toFixed(2) + " ৳";

}


// =====================================
// CHECKBOX CHANGE EVENT
// =====================================

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const checkboxes =
            document.querySelectorAll(
                ".expense-member-checkbox"
            );


        checkboxes.forEach(
            function (checkbox) {

                checkbox.addEventListener(
                    "change",
                    updateOtherExpenseAmount
                );

            }
        );

    }
);