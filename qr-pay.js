// ===== EDIT THIS SECTION =====
const BUSINESS_NAME = "My Shop";
const PAY_TYPE = "till";      // "till" or "paybill"
const NUMBER = "123456";      // your Till number or Paybill business number
const ACCOUNT = "";           // Paybill account number (leave "" for Till)
const STK_ENDPOINT = "";      // optional: your server URL for PIN prompts, e.g. "/api/pay"
// =============================

const $ = (id) => document.getElementById(id);

// Fill in the page
$("business-name").textContent = BUSINESS_NAME;
$("pay-number").textContent = NUMBER;
$("pay-label").textContent = PAY_TYPE === "till" ? "Till Number" : "Paybill Business Number";

if (PAY_TYPE === "paybill" && ACCOUNT) {
  $("pay-account").hidden = false;
  $("pay-account").textContent = "Account number: " + ACCOUNT;
}

const steps =
  PAY_TYPE === "till"
    ? [
        "Open M-Pesa (app or *334#)",
        "Choose Lipa na M-Pesa, then Buy Goods and Services",
        "Enter the Till number above",
        "Enter the amount",
        "Enter your M-Pesa PIN and confirm"
      ]
    : [
        "Open M-Pesa (app or *334#)",
        "Choose Lipa na M-Pesa, then Pay Bill",
        "Enter the business number above",
        "Enter the account number" + (ACCOUNT ? " (" + ACCOUNT + ")" : ""),
        "Enter the amount, then your M-Pesa PIN"
      ];

steps.forEach((text) => {
  const li = document.createElement("li");
  li.textContent = text;
  $("steps").appendChild(li);
});

function showMessage(text, type) {
  $("message").textContent = text;
  $("message").className = type || "";
}

// Copy the till / paybill number
$("copy-btn").addEventListener("click", async () => {
  try {
    await navigator.clipboard.writeText(NUMBER);
    showMessage("Number copied. Paste it in M-Pesa.", "ok");
  } catch (error) {
    showMessage("Could not copy. Please type the number.", "err");
  }
});

// Optional: PIN prompt via your own server (needs a backend)
if (STK_ENDPOINT) {
  $("stk-box").hidden = false;

  $("stk-btn").addEventListener("click", async () => {
    const phone = $("phone").value.trim();
    const amount = $("amount").value;

    if (!phone || !amount) {
      showMessage("Enter your phone number and the amount.", "err");
      return;
    }

    showMessage("Sending request...");
    try {
      const res = await fetch(STK_ENDPOINT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ phone, amount })
      });
      const data = await res.json();
      if (res.ok) {
        showMessage("Check your phone and enter your M-Pesa PIN.", "ok");
      } else {
        showMessage(data.error || "Request failed. Try again.", "err");
      }
    } catch (error) {
      showMessage("Could not reach the server.", "err");
    }
  });
}

// ===== QR code generator (for the business owner) =====
$("qr-url").value = location.href.startsWith("http") ? location.href : "";

$("qr-btn").addEventListener("click", () => {
  const url = $("qr-url").value.trim();
  if (!url) {
    showMessage("Paste the link of your payment page first.", "err");
    return;
  }

  $("qr-output").innerHTML = "";
  new QRCode($("qr-output"), { text: url, width: 220, height: 220 });

  // Give the QR a moment to draw, then enable download
  setTimeout(() => {
    const canvas = $("qr-output").querySelector("canvas");
    const img = $("qr-output").querySelector("img");
    const link = $("qr-download");
    link.href = canvas ? canvas.toDataURL("image/png") : img.src;
    link.download = "payment-qr.png";
    link.hidden = false;
  }, 200);
});