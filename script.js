
// Navigation handling
document.getElementById('home-btn').addEventListener('click', function() {
    showContent('home-content');
    fetchLatestMatches();
});

document.getElementById('match-btn').addEventListener('click', function() {
    showContent('match-content');
    fetchCurrentlyPlaying();
    fetchAndDisplayMatchList();
});

document.getElementById('ticketing-btn').addEventListener('click', function() {
    showContent('schedule-content');
    fetchScheduleData();
});

// Bracketing
document.getElementById('bracketing-btn').addEventListener('click', function() {
    showContent('bracketing-content');
    fetchGameMatches();
});


document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('toggle-sidebar-btn');
    const wrapper = document.querySelector('.wrapper');

    // Toggle sidebar visibility on button click
    toggleBtn.addEventListener('click', () => {
        wrapper.classList.toggle('sidebar-open');
    });

    // Close sidebar when a link is clicked on mobile
    const sidebarLinks = document.querySelectorAll('.sidebar ul li a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                wrapper.classList.remove('sidebar-open');
            }
        });
    });
});

document.getElementById('ticketing-btn').addEventListener('click', function() {
    showContent('schedule-content');
});

document.getElementById('bracketing-btn').addEventListener('click', function() {
    showContent('bracketing-content');
});

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('toggle-sidebar-btn');
    const wrapper = document.querySelector('.wrapper');
    const homeBtn = document.getElementById('home-btn');
    const matchBtn = document.getElementById('match-btn');
    const ticketingBtn = document.getElementById('ticketing-btn');
    const bracketingBtn = document.getElementById('bracketing-btn');

    // Toggle sidebar visibility on mobile
    toggleBtn.addEventListener('click', () => {
        wrapper.classList.toggle('sidebar-open');
    });

    // Close sidebar when clicking a link on mobile
    const sidebarLinks = document.querySelectorAll('.sidebar ul li a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                wrapper.classList.remove('sidebar-open');
            }
        });
    });

    // Navigation functions
    homeBtn.addEventListener('click', () => showContent('home-content'));
    matchBtn.addEventListener('click', () => showContent('match-content'));
    ticketingBtn.addEventListener('click', () => showContent('schedule-content'));
    bracketingBtn.addEventListener('click', () => showContent('bracketing-content'));

});


function advanceTeam(selectedTeam) {
    // Get the text (team name) of the selected team
    const teamName = selectedTeam.textContent;

    // Determine the next round
    const currentMatch = selectedTeam.parentElement;
    const currentRound = currentMatch.parentElement;

    let nextRound;
    if (currentRound.id === 'quarter-finals') {
        nextRound = document.getElementById('semi-finals');
    } else if (currentRound.id === 'semi-finals') {
        nextRound = document.getElementById('finals');
    } else if (currentRound.id === 'finals') {
        nextRound = document.getElementById('champion');
    }

    // Find the next available slot in the next round
    if (nextRound) {
        const nextMatch = Array.from(nextRound.getElementsByClassName('team')).find(team => team.textContent === "");
        if (nextMatch) {
            nextMatch.textContent = teamName;
        }
    }
}

// Set up event listeners for sidebar navigation
document.getElementById('home-btn').addEventListener('click', () => showContent('home-content'));
document.getElementById('match-btn').addEventListener('click', () => showContent('match-content'));
document.getElementById('ticketing-btn').addEventListener('click', () => showContent('schedule-content'));
document.getElementById('bracketing-btn').addEventListener('click', () => showContent('bracketing-content'));


function showContent(contentId) {
    // Hide all contents
    const allContents = document.querySelectorAll('.content');
    allContents.forEach(content => content.style.display = 'none');

    // Show the selected content
    const selectedContent = document.getElementById(contentId);
    selectedContent.style.display = 'block';
}


// Sidebar toggle for mobile view
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('toggle-sidebar-btn');
    const wrapper = document.querySelector('.wrapper');

    toggleBtn.addEventListener('click', () => {
        wrapper.classList.toggle('sidebar-open');
    });

    const sidebarLinks = document.querySelectorAll('.sidebar ul li a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                wrapper.classList.remove('sidebar-open');
            }
        });
    });
});

// Event listener to navigate to seat selection on form submission
document.getElementById('purchase-form').addEventListener('submit', function(event) {
    event.preventDefault(); 
    goToSeatSelection(); 
});

// Function to go to seat selection and enable the "Next" button to proceed to payment
function goToSeatSelection() {
    // Show the seat selection content
    showContent('seat-selection-content');
}

// Select modal elements
const adminLoginIcon = document.getElementById("admin-login-icon");
const adminLoginModal = document.getElementById("admin-login-modal");
const closeModalButton = document.querySelector(".close");
const loginForm = document.getElementById("admin-login-form");
const loginMessage = document.getElementById("login-message");

// Show modal on icon click
adminLoginIcon.addEventListener("click", () => {
    adminLoginModal.style.display = "flex";
});

// Hide modal on close button click
closeModalButton.addEventListener("click", () => {
    adminLoginModal.style.display = "none";
    loginMessage.textContent = ""; // Clear message
});

// Hide modal when clicking outside the modal content
window.addEventListener("click", (event) => {
    if (event.target === adminLoginModal) {
        adminLoginModal.style.display = "none";
        loginMessage.textContent = ""; // Clear message
    }
});

function proceedToPayment() {
    // Get selected schedule ID from the clicked list item
    const selectedSchedule = document.querySelector('.schedule-list .schedule-item.active');
    const schedId = selectedSchedule ? selectedSchedule.dataset.schedid : null;

    // Get input values
    const name = document.getElementById("name").value;
    const email = document.getElementById("email").value;
    const seatTypeElement = document.getElementById("seat-type");
    const fullSeatTypeText = seatTypeElement.options[seatTypeElement.selectedIndex].text;
    const seatTypeText = fullSeatTypeText.split(' - ')[0]; // Extract only the part before " - "
    const amount = parseInt(seatTypeElement.value, 10); // Amount based on seat type
    const total = parseInt(document.getElementById("total-amount").textContent.replace('P ', ''), 10);
    const quantity = document.getElementById("seat-quantity").value;

    console.log(schedId, name, email, seatTypeText, quantity, total);

    if (!schedId || !name || !email || !seatTypeText || !quantity || total <= 0) {
        alert("Please fill out all fields before proceeding.");
        return;
    }

    // Prepare data to send to the backend
    const postData = new URLSearchParams({
        actionType: 'saveBuyerTicket',
        name,
        email,
        schedId,
        seat: seatTypeText, // Only "Upperbox" or "Lowerbox"
        quantity,
        total
    });

    // Save data to the backend
    fetch('scheduleData.php', {
        method: 'POST',
        body: postData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                const referenceNo = data.referenceNo;
                console.log(referenceNo);
                // Show the QR Code content and generate the QR code
                showContent('qr-code-content');
                generateQRCode(referenceNo, quantity, seatTypeText, total);
                
            } else {
                alert("Failed to process the payment: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred. Please try again.");
        });
}

function generateQRCode(referenceNo, quantity, seat, total) {
    console.log(referenceNo);

    const qrCodeContainer = document.getElementById("qr-code-container");
    const qrDownloadButton = document.getElementById("qr-download-btn");

    if (!qrCodeContainer) {
        console.error("QR Code container not found in the DOM!");
        return;
    }

    // Clear any existing QR code or canvas
    qrCodeContainer.innerHTML = "";

    // Create a temporary QR code to render
    const qrCode = new QRCode(qrCodeContainer, {
        text: referenceNo,
        width: 200,
        height: 200,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H,
    });

    // Wait for the QR code to render before creating the combined image
    setTimeout(() => {
        // Extract the QR code canvas
        const qrCanvas = qrCodeContainer.querySelector("canvas");

        if (qrCanvas) {
            // Create a new canvas to combine QR code and text
            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");

            // Set canvas size to fit QR code and text
            canvas.width = 300;
            canvas.height = 400;

            // Fill the canvas with a white background
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            const qrOffsetY = 20;
            // Draw the QR code onto the new canvas
            ctx.drawImage(qrCanvas, 50, qrOffsetY);

            // Add text information below the QR code
            ctx.font = "16px Arial";
            ctx.textAlign = "center";
            ctx.fillStyle = "#000000";

            const textX = canvas.width / 2;
            ctx.fillText(`Reference No: ${referenceNo}`, textX, 250);
            ctx.fillText(`Quantity: ${quantity}`, textX, 280);
            ctx.fillText(`Seat: ${seat}`, textX, 310);
            ctx.fillText(`Total: P${total}`, textX, 340);
            ctx.fillText("PRESENT THIS AT TICKETING BOOTH FOR PAYMENT.", textX, 370);

            // Convert the combined canvas to a downloadable image
            const qrImage = canvas.toDataURL("image/png");

            // Update the download button functionality
            qrDownloadButton.style.display = "inline-block";
            qrDownloadButton.onclick = function () {
                const link = document.createElement("a");
                link.href = qrImage;
                link.download = `QRCode_${referenceNo}.png`;
                link.click();
            };
        }
    }, 500); // Delay to ensure QR code renders completely
}

loginForm.addEventListener("submit", (event) => {
    event.preventDefault();
    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;

    // Send data to PHP backend
    fetch("login.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ action: 'login', username: username, password: password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            loginMessage.textContent = data.message;
            loginMessage.style.color = "green";

            // Redirect to the URL returned in response
            setTimeout(() => {
                window.location.href = data.redirectUrl;
            }, 500);
        } else {
            loginMessage.textContent = data.message;
            loginMessage.style.color = "red";
        }
    })
    .catch(error => {
        console.error("Error:", error);
        loginMessage.textContent = "An error occurred. Please try again later.";
    });

    // Clear form fields
    loginForm.reset();
});



