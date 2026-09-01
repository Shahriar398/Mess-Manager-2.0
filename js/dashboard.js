document.addEventListener("DOMContentLoaded", function() {
    const initialView = document.getElementById("initial-view");
    const createMessView = document.getElementById("create-mess-view");
    const joinMessView = document.getElementById("join-mess-view");
    const successModal = document.getElementById("success-modal");
    
    // Create Mess Elements
    const btnCreateMess = document.getElementById("btn-create-mess");
    const btnBackCreate = document.getElementById("btn-back-create");
    const formCreateMess = document.getElementById("form-create-mess");
    const submitCreateBtn = document.getElementById("submit-create-btn");

    // Join Mess Elements
    const btnJoinMess = document.getElementById("btn-join-mess");
    const btnBackJoin = document.getElementById("btn-back-join");
    const formJoinMess = document.getElementById("form-join-mess");
    const submitJoinBtn = document.getElementById("submit-join-btn");

    const btnOkGreat = document.getElementById("btn-ok-great");

    // ---------------------------------
    // ১. Create Mess Logic
    // ---------------------------------
    if (btnCreateMess) {
        btnCreateMess.addEventListener("click", function() {
            initialView.style.display = "none";
            createMessView.style.display = "block";
        });
    }

    if (btnBackCreate) {
        btnBackCreate.addEventListener("click", function() {
            createMessView.style.display = "none";
            initialView.style.display = "block";
        });
    }

    if (formCreateMess) {
        formCreateMess.addEventListener("submit", function(e) {
            e.preventDefault(); 
            
            submitCreateBtn.innerText = "অপেক্ষা করুন...";
            submitCreateBtn.disabled = true;

            let formData = new FormData(formCreateMess);

            fetch("create_mess.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitCreateBtn.innerText = "হিসাব শুরু করুন";
                submitCreateBtn.disabled = false;

                if(data.status === "success") {
                    successModal.style.display = "flex"; 
                } else {
                    alert("Error: " + data.message); 
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("কোথাও সমস্যা হয়েছে। আবার চেষ্টা করুন।");
                submitCreateBtn.innerText = "হিসাব শুরু করুন";
                submitCreateBtn.disabled = false;
            });
        });
    }

    // ---------------------------------
    // ২. Join Mess Logic
    // ---------------------------------
    if (btnJoinMess) {
        btnJoinMess.addEventListener("click", function() {
            initialView.style.display = "none";
            joinMessView.style.display = "block";
        });
    }

    if (btnBackJoin) {
        btnBackJoin.addEventListener("click", function() {
            joinMessView.style.display = "none";
            initialView.style.display = "block";
        });
    }

    if (formJoinMess) {
        formJoinMess.addEventListener("submit", function(e) {
            e.preventDefault(); 
            
            submitJoinBtn.innerText = "অপেক্ষা করুন...";
            submitJoinBtn.disabled = true;

            let formData = new FormData(formJoinMess);

            fetch("join_mess.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitJoinBtn.innerText = "মেসে যুক্ত হোন";
                submitJoinBtn.disabled = false;

                if(data.status === "success") {
                    alert("আপনি সফলভাবে মেসে যুক্ত হয়েছেন!"); 
                    window.location.reload(); 
                } else {
                    alert("Error: " + data.message); 
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("কোথাও সমস্যা হয়েছে। আবার চেষ্টা করুন।");
                submitJoinBtn.innerText = "মেসে যুক্ত হোন";
                submitJoinBtn.disabled = false;
            });
        });
    }

    // ---------------------------------
    // ৩. Modal OK Button
    // ---------------------------------
    if (btnOkGreat) {
        btnOkGreat.addEventListener("click", function() {
            successModal.style.display = "none";
            window.location.reload(); 
        });
    }
});